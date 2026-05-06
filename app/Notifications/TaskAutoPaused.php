<?php

namespace App\Notifications;

use App\Models\Task;
use Illuminate\Notifications\Notification;

class TaskAutoPaused extends Notification
{
    public function __construct(
        public Task $task,
        public string $reason = 'auto', // 'task_switch' | 'end_of_day'
        public ?string $newTaskTitle = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $message = match ($this->reason) {
            'end_of_day'  => "Your timer on \"{$this->task->title}\" was auto-paused at end of work day. Resume tomorrow when you're ready.",
            'task_switch' => "Your timer on \"{$this->task->title}\" was paused because you started another task" .
                             ($this->newTaskTitle ? ": \"{$this->newTaskTitle}\"" : '') . '.',
            default       => "Your timer on \"{$this->task->title}\" was automatically paused.",
        };

        return [
            'title'   => 'Timer Auto-Paused',
            'message' => $message,
            'url'     => route('user.tasks.show', $this->task->id),
            'icon'    => 'fa-circle-pause',
            'color'   => 'amber',
        ];
    }
}
