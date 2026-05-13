<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Setting;
use App\Models\Task;
use Illuminate\Support\Str;

class NasService
{
    /**
     * Create the standard folder structure for a new customer on the NAS.
     * Called automatically when a customer is created.
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

    public function isEnabled(): bool
    {
        return Setting::get('storage_omv_enabled', '0') === '1'
            && Setting::get('storage_omv_protocol', '') === 'smb'
            && Setting::get('storage_omv_host', '') !== ''
            && Setting::get('storage_omv_share', '') !== '';
    }

    /**
     * Copy a comment file or task attachment to Customers/{company}/References/{year}/{month}/.
     * Gives easy top-level access to all files exchanged on a customer's tasks.
     */
    public function copyToNasReference(Task $task, string $localPath, string $originalFilename): bool
    {
        if (!$this->isEnabled()) return false;

        $localFull = storage_path('app/public/' . $localPath);
        if (!file_exists($localFull)) return false;

        $cfg      = $this->cfg();
        $root     = $cfg['root'];
        $year     = $task->created_at->format('Y');
        $month    = $task->created_at->format('Y-m');
        $customer = $task->customer ?? $task->project?->customer;

        if (!$customer) return false;

        $company = $this->slug($customer->name);
        $nasDir  = "{$root}/Customers/{$company}/References/{$year}/{$month}";

        $this->ensureFolders($cfg, $nasDir);

        $ext     = pathinfo($localFull, PATHINFO_EXTENSION);
        $tmpName = 'nas_' . uniqid() . ($ext ? ".{$ext}" : '');
        $tmpFull = sys_get_temp_dir() . '/' . $tmpName;
        copy($localFull, $tmpFull);

        $target = escapeshellarg("//{$cfg['host']}/{$cfg['share']}");
        $cred   = escapeshellarg("{$cfg['user']}%{$cfg['pass']}");
        $remote = preg_replace('/[\\\\\/\'"<>|*?]/', '_', $originalFilename);

        exec("smbclient {$target} -U {$cred} -c " .
            escapeshellarg("lcd \"" . sys_get_temp_dir() . "\"; cd \"{$nasDir}\"; put \"{$tmpName}\" \"{$remote}\"") .
            ' 2>&1', $out, $code);

        @unlink($tmpFull);

        return $code === 0;
    }

    /**
     * Copy the delivered file to Customers/{company}/Deliverables/{year}/{month}/.
     * Called when a task is marked as Delivered.
     */
    public function copyToNasDeliverable(Task $task, string $localPath, string $originalFilename): bool
    {
        if (!$this->isEnabled()) return false;

        $localFull = storage_path('app/public/' . $localPath);
        if (!file_exists($localFull)) return false;

        $cfg      = $this->cfg();
        $root     = $cfg['root'];
        $year     = $task->created_at->format('Y');
        $month    = $task->created_at->format('Y-m');
        $customer = $task->customer ?? $task->project?->customer;

        if (!$customer) return false;

        $company = $this->slug($customer->name);
        $nasDir  = "{$root}/Customers/{$company}/Deliverables/{$year}/{$month}";

        $this->ensureFolders($cfg, $nasDir);

        $ext     = pathinfo($localFull, PATHINFO_EXTENSION);
        $tmpName = 'nas_' . uniqid() . ($ext ? ".{$ext}" : '');
        $tmpFull = sys_get_temp_dir() . '/' . $tmpName;
        copy($localFull, $tmpFull);

        $target = escapeshellarg("//{$cfg['host']}/{$cfg['share']}");
        $cred   = escapeshellarg("{$cfg['user']}%{$cfg['pass']}");
        $remote = preg_replace('/[\\\\\/\'"<>|*?]/', '_', $originalFilename);

        exec("smbclient {$target} -U {$cred} -c " .
            escapeshellarg("lcd \"" . sys_get_temp_dir() . "\"; cd \"{$nasDir}\"; put \"{$tmpName}\" \"{$remote}\"") .
            ' 2>&1', $out, $code);

        @unlink($tmpFull);

        return $code === 0;
    }

    /**
     * Copy an approved task file + a post_info.txt to the Social_Media NAS folder.
     * Called when a task is marked as posted to social media.
     *
     * @param array $postInfo  ['platform','posted_by','posted_at','post_url','note','task_title','company']
     */
    public function copyToNasSocial(Task $task, string $localPath, string $originalFilename, string $platform, array $postInfo = []): bool
    {
        if (!$this->isEnabled()) return false;

        $localFull = storage_path('app/public/' . $localPath);
        if (!file_exists($localFull)) return false;

        $cfg    = $this->cfg();
        $nasDir = $this->nasDirSocial($task, $platform);

        $this->ensureFolders($cfg, $nasDir);

        $target = escapeshellarg("//{$cfg['host']}/{$cfg['share']}");
        $cred   = escapeshellarg("{$cfg['user']}%{$cfg['pass']}");
        $tmpDir = sys_get_temp_dir();

        // Upload the design file
        $ext         = pathinfo($localFull, PATHINFO_EXTENSION);
        $tmpDesign   = 'nas_' . uniqid() . ($ext ? ".{$ext}" : '');
        copy($localFull, $tmpDir . '/' . $tmpDesign);
        $remote = preg_replace('/[\\\\\/\'"<>|*?]/', '_', $originalFilename);

        exec("smbclient {$target} -U {$cred} -c " .
            escapeshellarg("lcd \"{$tmpDir}\"; cd \"{$nasDir}\"; put \"{$tmpDesign}\" \"{$remote}\"") .
            ' 2>&1', $out, $code);
        @unlink($tmpDir . '/' . $tmpDesign);

        // Write the post info text file
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

        $infoRemote = '_post_info.txt';
        exec("smbclient {$target} -U {$cred} -c " .
            escapeshellarg("lcd \"{$tmpDir}\"; cd \"{$nasDir}\"; put \"{$tmpInfo}\" \"{$infoRemote}\"") .
            ' 2>&1');
        @unlink($tmpDir . '/' . $tmpInfo);

        return $code === 0;
    }

    /**
     * Copy a locally stored file to the correct NAS folder for the given task and stage.
     * $localPath is relative to the public disk (e.g. "task-attachments/5/file.pdf").
     * $stage is one of: 03_Working | 04_Review | 05_Approved | 06_Rejected | 07_Delivered
     */
    public function copyToNas(Task $task, string $localPath, string $originalFilename, string $stage = '03_Working', int $version = 0): bool
    {
        if (!$this->isEnabled()) return false;

        $localFull = storage_path('app/public/' . $localPath);
        if (!file_exists($localFull)) return false;

        $cfg    = $this->cfg();
        $nasDir = $this->nasDir($task, $stage);
        if ($version > 0) {
            $nasDir .= '/V' . $version;
        }

        $this->ensureFolders($cfg, $nasDir);

        // Copy to a temp file with a safe name to avoid nested shell quoting issues
        $ext     = pathinfo($localFull, PATHINFO_EXTENSION);
        $tmpName = 'nas_' . uniqid() . ($ext ? ".{$ext}" : '');
        $tmpFull = sys_get_temp_dir() . '/' . $tmpName;
        copy($localFull, $tmpFull);

        $target = escapeshellarg("//{$cfg['host']}/{$cfg['share']}");
        $cred   = escapeshellarg("{$cfg['user']}%{$cfg['pass']}");
        $remote = preg_replace('/[\\\\\/\'"<>|*?]/', '_', $originalFilename);

        exec("smbclient {$target} -U {$cred} -c " .
            escapeshellarg("lcd \"" . sys_get_temp_dir() . "\"; cd \"{$nasDir}\"; put \"{$tmpName}\" \"{$remote}\"") .
            ' 2>&1', $out, $code);

        @unlink($tmpFull);

        return $code === 0;
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

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
            $title = $this->slug($task->title);

            // Quick tasks with a customer go under Customers/{company}/Quick_Tasks/
            $customer = $task->customer ?? $task->project?->customer;
            if ($customer) {
                $company = $this->slug($customer->name);
                return "{$root}/Customers/{$company}/Quick_Tasks/{$year}/{$month}/{$title}/{$stage}";
            }

            // No customer — root Quick_Tasks bucket
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
        $target = escapeshellarg("//{$cfg['host']}/{$cfg['share']}");
        $cred   = escapeshellarg("{$cfg['user']}%{$cfg['pass']}");
        $parts  = explode('/', $path);
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
