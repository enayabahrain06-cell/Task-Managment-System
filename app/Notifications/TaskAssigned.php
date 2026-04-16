<?php

namespace App\Notifications;

use App\Models\Task;
use Illuminate\Notifications\Notification;

class TaskAssigned extends Notification
{
    public function __construct(public Task $task) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title'   => 'New Task Assigned',
            'message' => 'You have been assigned: ' . $this->task->title,
            'url'     => route('user.tasks.show', $this->task->id),
            'icon'    => 'fa-list-check',
            'color'   => 'indigo',
        ];
    }
}
