<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Setting;
use App\Models\Task;
use App\Models\User;
use App\Notifications\NasSyncFailed;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class NasService
{
    /** Cached per-request so we don't hit the DB on every call */
    private ?array $cfgCache    = null;
    private ?bool  $enabledCache = null;

    public function isEnabled(): bool
    {
        if ($this->enabledCache !== null) return $this->enabledCache;
        $cfg = $this->cfg();
        return $this->enabledCache = (
            Setting::get('storage_omv_enabled', '0') === '1'
            && Setting::get('storage_omv_protocol', '') === 'smb'
            && $cfg['host'] !== ''
            && $cfg['share'] !== ''
        );
    }

    /** When NAS is enabled, files live ONLY on the NAS — no local retention. */
    public function isNetworkOnly(): bool
    {
        return $this->isEnabled();
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

        if ($customer) {
            $company = $this->slug($customer->name);
            $nasDir  = "{$root}/Customers/{$company}/References/{$year}/{$month}";
        } else {
            $title  = $this->slug($task->title);
            $nasDir = "{$root}/Quick_Tasks/{$year}/{$month}/{$title}/References";
        }

        $this->ensureFolders($cfg, $nasDir);

        $remote  = $this->uniqueRemoteName($cfg, $nasDir, preg_replace('/[\\\\\/\'":<>|*?()\s]/', '_', $originalFilename));
        $nasPath = $nasDir . '/' . $remote;
        $ok      = $this->smbPut($cfg, $localFull, $nasDir, $remote);

        if ($ok && $this->isNetworkOnly()) {
            $this->deleteLocal($localPath);
        } elseif (!$ok) {
            $this->reportSyncFailure($task, $originalFilename, 'Reference');
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

        if ($customer) {
            $company = $this->slug($customer->name);
            $nasDir  = "{$root}/Customers/{$company}/Deliverables/{$year}/{$month}";
        } else {
            $title  = $this->slug($task->title);
            $nasDir = "{$root}/Quick_Tasks/{$year}/{$month}/{$title}/Deliverables";
        }

        $this->ensureFolders($cfg, $nasDir);

        $remote  = $this->uniqueRemoteName($cfg, $nasDir, preg_replace('/[\\\\\/\'":<>|*?()\s]/', '_', $originalFilename));
        $nasPath = $nasDir . '/' . $remote;
        $ok      = $this->smbPut($cfg, $localFull, $nasDir, $remote);

        if ($ok && $this->isNetworkOnly()) {
            $this->deleteLocal($localPath);
        } elseif (!$ok) {
            $this->reportSyncFailure($task, $originalFilename, 'Deliverable');
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
        $remote = $this->uniqueRemoteName($cfg, $nasDir, preg_replace('/[\\\\\/\'":<>|*?()\s]/', '_', $originalFilename));

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
        } elseif ($code !== 0) {
            $this->reportSyncFailure($task, $originalFilename, 'Social Media');
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

        $remote  = $this->uniqueRemoteName($cfg, $nasDir, preg_replace('/[\\\\\/\'":<>|*?()\s]/', '_', $originalFilename));
        $nasPath = $nasDir . '/' . $remote;
        $ok      = $this->smbPut($cfg, $localFull, $nasDir, $remote);

        if ($ok && $this->isNetworkOnly()) {
            $this->deleteLocal($localPath);
        } elseif (!$ok) {
            $this->reportSyncFailure($task, $originalFilename, $stage);
        }

        return $ok ? $nasPath : null;
    }

    /**
     * Copy a generated user task-report PDF (spans multiple customers, so it has no
     * single customer folder) to Staff_Reports/{user}/ on the NAS.
     */
    public function copyToNasUserReport(User $user, string $localFull, string $originalFilename): ?string
    {
        if (!$this->isEnabled()) return null;
        if (!file_exists($localFull)) return null;

        $cfg    = $this->cfg();
        $nasDir = "{$cfg['root']}/Staff_Reports/" . $this->slug($user->name);

        $this->ensureFolders($cfg, $nasDir);

        $remote  = $this->uniqueRemoteName($cfg, $nasDir, preg_replace('/[\\\\\/\'":<>|*?()\s]/', '_', $originalFilename));
        $nasPath = $nasDir . '/' . $remote;
        $ok      = $this->smbPut($cfg, $localFull, $nasDir, $remote);

        if (!$ok) {
            Log::warning('NAS sync failed', ['user_id' => $user->id, 'file' => $originalFilename, 'stage' => 'Staff Report']);
        }

        return $ok ? $nasPath : null;
    }

    /**
     * Copy a generated company-wide summary report PDF to Reports/ at the NAS root
     * (not tied to any single customer or user).
     */
    public function copyToNasCompanyReport(string $localFull, string $originalFilename): ?string
    {
        if (!$this->isEnabled()) return null;
        if (!file_exists($localFull)) return null;

        $cfg    = $this->cfg();
        $nasDir = "{$cfg['root']}/Reports";

        $this->ensureFolders($cfg, $nasDir);

        $remote  = $this->uniqueRemoteName($cfg, $nasDir, preg_replace('/[\\\\\/\'":<>|*?()\s]/', '_', $originalFilename));
        $nasPath = $nasDir . '/' . $remote;
        $ok      = $this->smbPut($cfg, $localFull, $nasDir, $remote);

        if (!$ok) {
            Log::warning('NAS sync failed', ['file' => $originalFilename, 'stage' => 'Company Summary Report']);
        }

        return $ok ? $nasPath : null;
    }

    /**
     * Copy a generated customer-report PDF to Customers/{name}/Reports/ on the NAS.
     */
    public function copyToNasCustomerReport(Customer $customer, string $localFull, string $originalFilename): ?string
    {
        if (!$this->isEnabled()) return null;
        if (!file_exists($localFull)) return null;

        $cfg    = $this->cfg();
        $nasDir = "{$cfg['root']}/Customers/" . $this->slug($customer->name) . '/Reports';

        $this->ensureFolders($cfg, $nasDir);

        $remote  = $this->uniqueRemoteName($cfg, $nasDir, preg_replace('/[\\\\\/\'":<>|*?()\s]/', '_', $originalFilename));
        $nasPath = $nasDir . '/' . $remote;
        $ok      = $this->smbPut($cfg, $localFull, $nasDir, $remote);

        if (!$ok) {
            Log::warning('NAS sync failed', ['customer_id' => $customer->id, 'file' => $originalFilename, 'stage' => 'Customer Report']);
        }

        return $ok ? $nasPath : null;
    }

    /**
     * Scan the expected NAS location for a task file and return its path if found.
     * Checks the base sanitized name and up to 10 renamed variants (_1, _2 …).
     */
    public function findNasPath(Task $task, string $originalFilename, string $stage = '03_Working', int $version = 0): ?string
    {
        if (!$this->isEnabled()) return null;
        $cfg    = $this->cfg();
        $nasDir = $this->nasDir($task, $stage);
        if ($version > 0) $nasDir .= '/V' . $version;
        return $this->findInDir($cfg, $nasDir, $originalFilename);
    }

    /**
     * Scan the expected NAS reference location for a comment/attachment file.
     */
    public function findNasPathReference(Task $task, string $originalFilename): ?string
    {
        if (!$this->isEnabled()) return null;
        $cfg      = $this->cfg();
        $root     = $cfg['root'];
        $year     = $task->created_at->format('Y');
        $month    = $task->created_at->format('Y-m');
        $customer = $task->customer ?? $task->project?->customer;
        if ($customer) {
            $nasDir = "{$root}/Customers/{$this->slug($customer->name)}/References/{$year}/{$month}";
        } else {
            $nasDir = "{$root}/Quick_Tasks/{$year}/{$month}/{$this->slug($task->title)}/References";
        }
        return $this->findInDir($cfg, $nasDir, $originalFilename);
    }

    /** Check a directory for a sanitized filename or its _1/_2/… variants. */
    private function findInDir(array $cfg, string $nasDir, string $originalFilename): ?string
    {
        $sanitized = preg_replace('/[\\\\\/\'":<>|*?()\s]/', '_', $originalFilename);
        $ext       = pathinfo($sanitized, PATHINFO_EXTENSION);
        $base      = $ext ? substr($sanitized, 0, -(strlen($ext) + 1)) : $sanitized;

        // Check original filename first (files restored from recycle bin keep original names)
        if ($originalFilename !== $sanitized && $this->nasFileExists($cfg, $nasDir, $originalFilename)) {
            return $nasDir . '/' . $originalFilename;
        }
        // Check sanitized name
        if ($this->nasFileExists($cfg, $nasDir, $sanitized)) {
            return $nasDir . '/' . $sanitized;
        }
        // Check _N conflict variants
        for ($i = 1; $i <= 20; $i++) {
            $candidate = $ext ? "{$base}_{$i}.{$ext}" : "{$base}_{$i}";
            if ($this->nasFileExists($cfg, $nasDir, $candidate)) {
                return $nasDir . '/' . $candidate;
            }
        }
        return null;
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

    private function nasFileExists(array $cfg, string $nasDir, string $filename): bool
    {
        $target = escapeshellarg("//{$cfg['host']}/{$cfg['share']}");
        $cred   = escapeshellarg("{$cfg['user']}%{$cfg['pass']}");
        exec("smbclient {$target} -U {$cred} -c " .
            escapeshellarg("cd \"{$nasDir}\"; ls \"{$filename}\"") .
            ' 2>&1', $out, $code);
        foreach ($out as $line) {
            if (str_contains($line, $filename)) return true;
        }
        return false;
    }

    private function uniqueRemoteName(array $cfg, string $nasDir, string $remote): string
    {
        // Prepend a short unique token to guarantee uniqueness without extra SMB round-trips
        $ext  = pathinfo($remote, PATHINFO_EXTENSION);
        $base = $ext ? substr($remote, 0, -(strlen($ext) + 1)) : $remote;
        $uid  = substr(uniqid(), -6);
        return $ext ? "{$base}_{$uid}.{$ext}" : "{$base}_{$uid}";
    }

    private function smbPut(array $cfg, string $localFull, string $nasDir, string $remote): bool
    {
        $target  = escapeshellarg("//{$cfg['host']}/{$cfg['share']}");
        $cred    = escapeshellarg("{$cfg['user']}%{$cfg['pass']}");
        $ext     = pathinfo($localFull, PATHINFO_EXTENSION);
        $tmpName = 'nas_' . uniqid() . ($ext ? ".{$ext}" : '');
        $tmpFull = sys_get_temp_dir() . '/' . $tmpName;
        copy($localFull, $tmpFull);

        // Combine all mkdir + put into ONE smbclient session (one TCP connection)
        $parts  = explode('/', $nasDir);
        $cmds   = [];
        $current = '';
        foreach ($parts as $part) {
            $current = $current ? "{$current}/{$part}" : $part;
            $cmds[] = "mkdir \"{$current}\"";
        }
        $cmds[] = "lcd \"" . sys_get_temp_dir() . "\"";
        $cmds[] = "cd \"{$nasDir}\"";
        $cmds[] = "put \"{$tmpName}\" \"{$remote}\"";

        exec("smbclient {$target} -U {$cred} -c " .
            escapeshellarg(implode('; ', $cmds)) .
            ' 2>&1', $out, $code);

        @unlink($tmpFull);
        return $code === 0;
    }

    /**
     * Log a sync failure and alert admins (throttled to one alert per task+stage per 15 min,
     * so a burst of retries/re-uploads during a NAS outage doesn't flood notifications).
     */
    private function reportSyncFailure(Task $task, string $originalFilename, string $stage): void
    {
        Log::warning('NAS sync failed', [
            'task_id'  => $task->id,
            'file'     => $originalFilename,
            'stage'    => $stage,
        ]);

        $cacheKey = "nas_sync_failed_alert:{$task->id}:{$stage}";
        if (\Illuminate\Support\Facades\Cache::has($cacheKey)) {
            return;
        }
        \Illuminate\Support\Facades\Cache::put($cacheKey, true, now()->addMinutes(15));

        User::where('role', 'admin')->each(
            fn ($admin) => $admin->notify(new NasSyncFailed($task, $originalFilename, $stage))
        );
    }

    private function deleteLocal(string $localPath): void
    {
        Storage::disk('public')->delete($localPath);
    }

    private function cfg(): array
    {
        if ($this->cfgCache !== null) return $this->cfgCache;
        return $this->cfgCache = [
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
        // Build all mkdir commands in one smbclient session (one TCP connection)
        $target = escapeshellarg("//{$cfg['host']}/{$cfg['share']}");
        $cred   = escapeshellarg("{$cfg['user']}%{$cfg['pass']}");
        $parts  = explode('/', $path);
        $cmds   = [];
        $current = '';
        foreach ($parts as $part) {
            $current = $current ? "{$current}/{$part}" : $part;
            $cmds[] = "mkdir \"{$current}\"";
        }
        exec("smbclient {$target} -U {$cred} -c " . escapeshellarg(implode('; ', $cmds)) . ' 2>&1');
    }

    public function pullFromNas(string $nasPath, string $localDest): bool
    {
        $cfg     = $this->cfg();
        $target  = escapeshellarg("//{$cfg['host']}/{$cfg['share']}");
        $cred    = escapeshellarg("{$cfg['user']}%{$cfg['pass']}");
        $dir     = dirname($nasPath);
        $file    = basename($nasPath);
        $tmpDir  = dirname($localDest);
        $tmpName = basename($localDest);

        exec("smbclient {$target} -U {$cred} -c " .
            escapeshellarg("lcd \"{$tmpDir}\"; cd \"{$dir}\"; get \"{$file}\" \"{$tmpName}\"") .
            ' 2>&1', $out, $code);

        return $code === 0 && file_exists($localDest);
    }

    public function saveBackupToNas(string $localZipPath, string $filename): bool
    {
        $cfg    = $this->cfg();
        $root   = $cfg['root'];
        $year   = now()->format('Y');
        $month  = now()->format('Y-m');
        $nasDir = "{$root}/Backups/{$year}/{$month}";

        $this->ensureFolders($cfg, $nasDir);
        return $this->smbPut($cfg, $localZipPath, $nasDir, $filename);
    }

    public function deleteNasFile(string $nasPath): bool
    {
        $cfg    = $this->cfg();
        $target = escapeshellarg("//{$cfg['host']}/{$cfg['share']}");
        $cred   = escapeshellarg("{$cfg['user']}%{$cfg['pass']}");
        $dir    = dirname($nasPath);
        $file   = basename($nasPath);

        exec("smbclient {$target} -U {$cred} -c " .
            escapeshellarg("cd \"{$dir}\"; rm \"{$file}\"") .
            ' 2>&1', $out, $code);

        return $code === 0;
    }

    public function listNasBackups(): array
    {
        $cfg    = $this->cfg();
        $root   = $cfg['root'];
        $target = escapeshellarg("//{$cfg['host']}/{$cfg['share']}");
        $cred   = escapeshellarg("{$cfg['user']}%{$cfg['pass']}");

        $smbls = fn(string $dir) => tap([], function (&$out) use ($target, $cred, $dir) {
            exec("smbclient {$target} -U {$cred} -c " . escapeshellarg("cd \"{$dir}\"; ls") . ' 2>&1', $out);
        });

        $years = [];
        foreach ($smbls("{$root}/Backups") as $line) {
            if (preg_match('/^\s+(\d{4})\s+D/', $line, $m)) $years[] = $m[1];
        }

        $files = [];
        foreach ($years as $year) {
            foreach ($smbls("{$root}/Backups/{$year}") as $line) {
                if (!preg_match('/^\s+(\d{4}-\d{2})\s+D/', $line, $m)) continue;
                $monthDir = "{$root}/Backups/{$year}/{$m[1]}";
                foreach ($smbls($monthDir) as $entry) {
                    if (preg_match('/^\s+(backup_\S+\.(zip|sqlite))\s+[AN]\s+(\d+)/', $entry, $f)) {
                        $files[] = [
                            'name'  => $f[1],
                            'size'  => round((int)$f[3] / 1048576, 1),
                            'path'  => "{$monthDir}/{$f[1]}",
                            'month' => $m[1],
                        ];
                    }
                }
            }
        }

        usort($files, fn($a, $b) => strcmp($b['name'], $a['name']));
        return $files;
    }

    private function slug(string $value): string
    {
        return Str::slug($value, '_') ?: 'Unknown';
    }
}
