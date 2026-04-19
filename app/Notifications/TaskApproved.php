<?php

namespace App\Notifications;

use App\Models\Task;
use Illuminate\Notifications\Notification;

class TaskApproved extends Notification
{
    public function __construct(public Task $task, public ?string $note = null) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title'   => 'Task Approved',
            'message' => 'Your submission for "' . $this->task->title . '" was approved!' . ($this->note ? ' Note: ' . $this->note : ''),
            'url'     => route('user.tasks.show', $this->task->id),
            'icon'    => 'fa-circle-check',
            'color'   => 'green',
        ];
    }
}
