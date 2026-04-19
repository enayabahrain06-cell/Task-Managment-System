<?php

namespace App\Notifications;

use App\Models\Task;
use Illuminate\Notifications\Notification;

class TaskReassigned extends Notification
{
    /**
     * @param Task $task
     * @param bool $isNewAssignee  true = receiving the task, false = task was taken away
     */
    public function __construct(public Task $task, public bool $isNewAssignee) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        if ($this->isNewAssignee) {
            return [
                'title'   => 'Task Assigned to You',
                'message' => 'You have been assigned: ' . $this->task->title,
                'url'     => route('user.tasks.show', $this->task->id),
                'icon'    => 'fa-list-check',
                'color'   => 'indigo',
            ];
        }

        return [
            'title'   => 'Task Reassigned',
            'message' => '"' . $this->task->title . '" has been reassigned to another team member.',
            'url'     => route('user.tasks.index'),
            'icon'    => 'fa-arrows-rotate',
            'color'   => 'amber',
        ];
    }
}
