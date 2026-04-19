<?php

namespace App\Notifications;

use App\Models\Task;
use App\Models\User;
use Illuminate\Notifications\Notification;

class TaskViewed extends Notification
{
    public function __construct(public Task $task, public User $viewer) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title'   => 'Task Opened',
            'message' => ($this->viewer->name ?? 'A user') . ' opened their task: ' . $this->task->title,
            'url'     => route('admin.tasks.show', $this->task->id),
            'icon'    => 'fa-eye',
            'color'   => 'amber',
        ];
    }
}
