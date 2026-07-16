<?php

namespace App\Notifications;

use App\Models\Task;
use Illuminate\Notifications\Notification;

class NasSyncFailed extends Notification
{
    public function __construct(
        public ?Task $task,
        public string $filename,
        public string $stage,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $taskLabel = $this->task ? "\"{$this->task->title}\"" : 'a file';

        return [
            'title'   => 'NAS Sync Failed',
            'message' => "Failed to copy {$this->filename} ({$this->stage}) to network storage for {$taskLabel}. It is only saved locally — use \"Migrate local files to NAS\" in Settings > Storage to retry.",
            'url'     => $this->task
                ? route('admin.tasks.show', $this->task->id)
                : route('admin.settings.index', ['tab' => 'storage']),
            'icon'    => 'fa-triangle-exclamation',
            'color'   => 'red',
        ];
    }
}
