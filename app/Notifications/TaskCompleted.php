<?php

namespace App\Notifications;

use App\Models\Task;
use Illuminate\Notifications\Notification;

class TaskCompleted extends Notification
{
    public function __construct(public Task $task) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title'   => 'Task Completed',
            'message' => ($this->task->assignee->name ?? 'A user') . ' completed: ' . $this->task->title,
            'url'     => route('admin.projects.show', $this->task->project_id),
            'icon'    => 'fa-circle-check',
            'color'   => 'green',
        ];
    }
}
