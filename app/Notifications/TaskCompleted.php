<?php

namespace App\Notifications;

use App\Models\Task;
use Illuminate\Notifications\Notification;

class TaskCompleted extends Notification
{
    public function __construct(public Task $task, public bool $hasFile = false) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $name   = $this->task->assignee->name ?? 'A user';
        $action = $this->hasFile ? 'submitted artwork for review' : 'submitted work for review';

        return [
            'title'   => 'Submitted for Review',
            'message' => $name . ' ' . $action . ': ' . $this->task->title,
            'url'     => route('admin.tasks.show', $this->task->id),
            'icon'    => $this->hasFile ? 'fa-file-circle-check' : 'fa-hourglass-half',
            'color'   => 'amber',
        ];
    }
}
