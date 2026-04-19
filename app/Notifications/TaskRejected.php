<?php

namespace App\Notifications;

use App\Models\Task;
use Illuminate\Notifications\Notification;

class TaskRejected extends Notification
{
    public function __construct(public Task $task, public string $note) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title'   => 'Submission Needs Revision',
            'message' => '"' . $this->task->title . '" was sent back: ' . $this->note,
            'url'     => route('user.tasks.show', $this->task->id),
            'icon'    => 'fa-rotate-left',
            'color'   => 'red',
        ];
    }
}
