<?php

namespace App\Console\Commands;

use App\Http\Controllers\Admin\SettingsController;
use App\Models\Setting;
use App\Services\NasService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class AutoBackupToNas extends Command
{
    protected $signature   = 'backup:auto-nas-sync';
    protected $description = 'Build a full system backup (DB + files) and push it to the NAS, pruning old backups beyond the retention limit';

    public function handle(SettingsController $settings, NasService $nas): int
    {
        if (Setting::get('auto_backup_enabled', '0') !== '1') {
            $this->info('Automatic backup is disabled in Settings — skipping.');

            return self::SUCCESS;
        }

        if (!$nas->isEnabled()) {
            $this->info('NAS is not configured — skipping automatic backup.');

            return self::SUCCESS;
        }

        ['zipPath' => $zipPath, 'tmpDir' => $tmpDir, 'filename' => $filename] = $settings->buildFullBackupZipFile();

        $ok = $nas->saveBackupToNas($zipPath, $filename);
        $settings->cleanupBackupTmp($tmpDir);

        if (!$ok) {
            $this->error("Automatic backup failed to upload {$filename} to NAS.");
            Log::warning('Automatic NAS backup failed', ['filename' => $filename]);

            return self::FAILURE;
        }

        $this->info("Backup {$filename} uploaded to NAS.");

        $retain  = max(1, (int) Setting::get('auto_backup_retain', 14));
        $backups = collect($nas->listNasBackups())
            ->filter(fn ($b) => str_ends_with($b['name'], '.zip'))
            ->sortByDesc('name')
            ->values();

        foreach ($backups->slice($retain) as $old) {
            if ($nas->deleteNasFile($old['path'])) {
                $this->info("Pruned old backup: {$old['name']}");
            } else {
                Log::warning('Failed to prune old NAS backup', ['path' => $old['path']]);
            }
        }

        return self::SUCCESS;
    }
}
