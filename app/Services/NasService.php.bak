<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Setting;
use App\Models\Task;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class NasService
{
    public function isEnabled(): bool
    {
        return Setting::get('storage_omv_enabled', '0') === '1'
            && Setting::get('storage_omv_protocol', '') === 'smb'
            && Setting::get('storage_omv_host', '') !== ''
            && Setting::get('storage_omv_share', '') !== '';
    }

    /** True when files should live ONLY on the NAS (no local retention). */
    public function isNetworkOnly(): bool
    {
        return $this->isEnabled() && Setting::get('storage_omv_only', '0') === '1';
    }

    /**
     * Create the standard folder structure for a new customer on the NAS.
     */
    public function createCustomerFolders(Customer $customer): void
    {
        if (!$this->isEnabled()) return;

        $cfg  = $this->cfg();
        $root = $cfg['root'];
        $slug = $this->slug($customer->name);

        $folders = [
            "{$root}/Customers/{$slug}",
            "{$root}/Customers/{$slug}/Projects",
            "{$root}/Customers/{$slug}/Quick_Tasks",
            "{$root}/Customers/{$slug}/Social_Media",
            "{$root}/Customers/{$slug}/Deliverables",
            "{$root}/Customers/{$slug}/Reports",
            "{$root}/Customers/{$slug}/References",
            "{$root}/Customers/{$slug}/Contracts",
        ];

        foreach ($folders as $path) {
            $this->ensureFolders($cfg, $path);
        }
    }

    /**
     * Copy a comment file or task attachment to Customers/{company}/References/{year}/{month}/.
     * Returns the NAS path on success, or null on failure.
     */
    public function copyToNasReference(Task $task, string $localPath, string $originalFilename): ?string
    {
        if (!$this->isEnabled()) return null;

        $localFull = storage_path('app/public/' . $localPath);
        if (!file_exists($localFull)) return null;

        $cfg      = $this->cfg();
        $root     = $cfg['root'];
        $year     = $task->created_at->format('Y');
        $month    = $task->created_at->format('Y-m');
        $customer = $task->customer ?? $task->project?->customer;

        if (!$customer) return null;

        $company = $this->slug($customer->name);
        $nasDir  = "{$root}/Customers/{$company}/References/{$year}/{$month}";

        $this->ensureFolders($cfg, $nasDir);

        $remote  = preg_replace('/[\\\\\/\'"<>|*?]/', '_', $originalFilename);
        $nasPath = $nasDir . '/' . $remote;
        $ok      = $this->smbPut($cfg, $localFull, $nasDir, $remote);

        if ($ok && $this->isNetworkOnly()) {
            $this->deleteLocal($localPath);
        }

        return $ok ? $nasPath : null;
    }

    /**
     * Copy the delivered file to Customers/{company}/Deliverables/{year}/{month}/.
     * Returns the NAS path on success, or null on failure.
     */
    public function copyToNasDeliverable(Task $task, string $localPath, string $originalFilename): ?string
    {
        if (!$this->isEnabled()) return null;

        $localFull = storage_path('app/public/' . $localPath);
        if (!file_exists($localFull)) return null;

        $cfg      = $this->cfg();
        $root     = $cfg['root'];
        $year     = $task->created_at->format('Y');
        $month    = $task->created_at->format('Y-m');
        $customer = $task->customer ?? $task->project?->customer;

        if (!$customer) return null;

        $company = $this->slug($customer->name);
        $nasDir  = "{$root}/Customers/{$company}/Deliverables/{$year}/{$month}";

        $this->ensureFolders($cfg, $nasDir);

        $remote  = preg_replace('/[\\\\\/\'"<>|*?]/', '_', $originalFilename);
        $nasPath = $nasDir . '/' . $remote;
        $ok      = $this->smbPut($cfg, $localFull, $nasDir, $remote);

        if ($ok && $this->isNetworkOnly()) {
            $this->deleteLocal($localPath);
        }

        return $ok ? $nasPath : null;
    }

    /**
     * Copy an approved task file to the Social_Media NAS folder.
     * Returns the NAS path on success, or null on failure.
     */
    public function copyToNasSocial(Task $task, string $localPath, string $originalFilename, string $platform, array $postInfo = []): ?string
    {
        if (!$this->isEnabled()) return null;

        $localFull = storage_path('app/public/' . $localPath);
        if (!file_exists($localFull)) return null;

        $cfg    = $this->cfg();
        $nasDir = $this->nasDirSocial($task, $platform);

        $this->ensureFolders($cfg, $nasDir);

        $target = escapeshellarg("//{$cfg['host']}/{$cfg['share']}");
        $cred   = escapeshellarg("{$cfg['user']}%{$cfg['pass']}");
        $tmpDir = sys_get_temp_dir();

        $ext       = pathinfo($localFull, PATHINFO_EXTENSION);
        $tmpDesign = 'nas_' . uniqid() . ($ext ? ".{$ext}" : '');
        copy($localFull, $tmpDir . '/' . $tmpDesign);
        $remote = preg_replace('/[\\\\\/\'"<>|*?]/', '_', $originalFilename);

        exec("smbclient {$target} -U {$cred} -c " .
            escapeshellarg("lcd \"{$tmpDir}\"; cd \"{$nasDir}\"; put \"{$tmpDesign}\" \"{$remote}\"") .
            ' 2>&1', $out, $code);
        @unlink($tmpDir . '/' . $tmpDesign);

        $platformLabels = [
            'facebook'  => 'Facebook',  'instagram' => 'Instagram', 'twitter'   => 'Twitter / X',
            'linkedin'  => 'LinkedIn',  'tiktok'    => 'TikTok',    'youtube'   => 'YouTube',
            'snapchat'  => 'Snapchat',  'other'     => 'Other',
        ];
        $lines = [
            '============================================================',
            '  SOCIAL MEDIA POST RECORD',
            '============================================================',
            '',
            'Task        : ' . ($postInfo['task_title'] ?? $task->title),
            'Company     : ' . ($postInfo['company']    ?? ($task->customer?->name ?? $task->project?->customer?->name ?? '—')),
            'Platform    : ' . ($platformLabels[$platform] ?? ucfirst($platform)),
            'Posted by   : ' . ($postInfo['posted_by']  ?? '—'),
            'Posted at   : ' . ($postInfo['posted_at']  ?? now()->format('D, d M Y H:i')),
            'Post URL    : ' . ($postInfo['post_url']   ?: '—'),
            'Note        : ' . ($postInfo['note']       ?: '—'),
            'Design file : ' . $remote,
            '',
            '============================================================',
        ];

        $infoContent = implode("\r\n", $lines) . "\r\n";
        $tmpInfo     = 'nas_info_' . uniqid() . '.txt';
        file_put_contents($tmpDir . '/' . $tmpInfo, $infoContent);

        exec("smbclient {$target} -U {$cred} -c " .
            escapeshellarg("lcd \"{$tmpDir}\"; cd \"{$nasDir}\"; put \"{$tmpInfo}\" \"_post_info.txt\"") .
            ' 2>&1');
        @unlink($tmpDir . '/' . $tmpInfo);

        $nasPath = $nasDir . '/' . $remote;

        if ($code === 0 && $this->isNetworkOnly()) {
            $this->deleteLocal($localPath);
        }

        return $code === 0 ? $nasPath : null;
    }

    /**
     * Copy a locally stored file to the correct NAS folder for the given task and stage.
     * Returns the NAS path on success, or null on failure.
     */
    public function copyToNas(Task $task, string $localPath, string $originalFilename, string $stage = '03_Working', int $version = 0): ?string
    {
        if (!$this->isEnabled()) return null;

        $localFull = storage_path('app/public/' . $localPath);
        if (!file_exists($localFull)) return null;

        $cfg    = $this->cfg();
        $nasDir = $this->nasDir($task, $stage);
        if ($version > 0) {
            $nasDir .= '/V' . $version;
        }

        $this->ensureFolders($cfg, $nasDir);

        $remote  = preg_replace('/[\\\\\/\'"<>|*?]/', '_', $originalFilename);
        $nasPath = $nasDir . '/' . $remote;
        $ok      = $this->smbPut($cfg, $localFull, $nasDir, $remote);

        if ($ok && $this->isNetworkOnly()) {
            $this->deleteLocal($localPath);
        }

        return $ok ? $nasPath : null;
    }

    /**
     * Serve a file from the NAS.
     * Pass $inline=true to serve inline (for browser preview); false forces a download.
     */
    public function downloadFromNas(string $nasPath, string $filename, bool $inline = false): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $cfg    = $this->cfg();
        $target = escapeshellarg("//{$cfg['host']}/{$cfg['share']}");
        $cred   = escapeshellarg("{$cfg['user']}%{$cfg['pass']}");

        $dir     = dirname($nasPath);
        $remote  = basename($nasPath);
        $ext     = pathinfo($remote, PATHINFO_EXTENSION);
        $tmpFile = sys_get_temp_dir() . '/nas_dl_' . uniqid() . ($ext ? ".{$ext}" : '');

        exec("smbclient {$target} -U {$cred} -c " .
            escapeshellarg("cd \"{$dir}\"; get \"{$remote}\" \"{$tmpFile}\"") .
            ' 2>&1', $out, $code);

        abort_if($code !== 0 || !file_exists($tmpFile), 404, 'File not available on network storage.');

        if ($inline) {
            $response = response()->file($tmpFile);
            $response->deleteFileAfterSend(true);
            return $response;
        }

        return response()->download($tmpFile, $filename)->deleteFileAfterSend(true);
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    private function smbPut(array $cfg, string $localFull, string $nasDir, string $remote): bool
    {
        $target  = escapeshellarg("//{$cfg['host']}/{$cfg['share']}");
        $cred    = escapeshellarg("{$cfg['user']}%{$cfg['pass']}");
        $ext     = pathinfo($localFull, PATHINFO_EXTENSION);
        $tmpName = 'nas_' . uniqid() . ($ext ? ".{$ext}" : '');
        $tmpFull = sys_get_temp_dir() . '/' . $tmpName;
        copy($localFull, $tmpFull);

        exec("smbclient {$target} -U {$cred} -c " .
            escapeshellarg("lcd \"" . sys_get_temp_dir() . "\"; cd \"{$nasDir}\"; put \"{$tmpName}\" \"{$remote}\"") .
            ' 2>&1', $out, $code);

        @unlink($tmpFull);
        return $code === 0;
    }

    private function deleteLocal(string $localPath): void
    {
        Storage::disk('public')->delete($localPath);
    }

    private function cfg(): array
    {
        return [
            'host'  => Setting::get('storage_omv_host', ''),
            'share' => trim(Setting::get('storage_omv_share', ''), '/'),
            'user'  => Setting::get('storage_omv_username', ''),
            'pass'  => Setting::get('storage_omv_password', ''),
            'root'  => trim(Setting::get('storage_root_path', 'Marketing_System_ms'), '/'),
        ];
    }

    private function nasDirSocial(Task $task, string $platform): string
    {
        $cfg      = $this->cfg();
        $root     = $cfg['root'];
        $year     = $task->created_at->format('Y');
        $month    = $task->created_at->format('Y-m');
        $title    = $this->slug($task->title);
        $platform = $this->slug($platform);

        $customer = $task->customer ?? $task->project?->customer;
        if ($customer) {
            $company = $this->slug($customer->name);
            return "{$root}/Customers/{$company}/Social_Media/{$year}/{$month}/{$platform}/{$title}";
        }

        return "{$root}/Social_Media/{$year}/{$month}/{$platform}/{$title}";
    }

    private function nasDir(Task $task, string $stage): string
    {
        $cfg   = $this->cfg();
        $root  = $cfg['root'];
        $year  = $task->created_at->format('Y');
        $month = $task->created_at->format('Y-m');

        if ($task->project?->is_quick ?? true) {
            $title    = $this->slug($task->title);
            $customer = $task->customer ?? $task->project?->customer;
            if ($customer) {
                $company = $this->slug($customer->name);
                return "{$root}/Customers/{$company}/Quick_Tasks/{$year}/{$month}/{$title}/{$stage}";
            }
            return "{$root}/Quick_Tasks/{$year}/{$month}/{$title}/{$stage}";
        }

        $project = $task->project;
        $company = $this->slug($project->customer?->name ?? $project->name);
        $prjId   = 'PRJ-' . str_pad($project->id, 3, '0', STR_PAD_LEFT);
        $prjName = $this->slug($project->name);

        return "{$root}/Customers/{$company}/Projects/{$year}/{$month}/{$prjId}_{$prjName}/{$stage}";
    }

    private function ensureFolders(array $cfg, string $path): void
    {
        $target  = escapeshellarg("//{$cfg['host']}/{$cfg['share']}");
        $cred    = escapeshellarg("{$cfg['user']}%{$cfg['pass']}");
        $parts   = explode('/', $path);
        $current = '';

        foreach ($parts as $part) {
            $current = $current ? "{$current}/{$part}" : $part;
            exec("smbclient {$target} -U {$cred} -c " . escapeshellarg("mkdir \"{$current}\"") . ' 2>&1');
        }
    }

    private function slug(string $value): string
    {
        return Str::slug($value, '_') ?: 'Unknown';
    }
}
