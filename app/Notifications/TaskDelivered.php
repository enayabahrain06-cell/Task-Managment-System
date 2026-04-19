<?php

namespace App\Notifications;

use App\Models\Task;
use Illuminate\Notifications\Notification;

class TaskDelivered extends Notification
{
    public function __construct(public Task $task, public ?string $note = null) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title'   => 'Task Delivered',
            'message' => 'Your work on "' . $this->task->title . '" has been delivered!' . ($this->note ? ' — ' . $this->note : ''),
            'url'     => route('user.tasks.show', $this->task->id),
            'icon'    => 'fa-truck',
            'color'   => 'green',
        ];
    }
}
