<?php

namespace App\Notifications;

use App\Models\Task;
use App\Models\TaskComment;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class TaskCommentPosted extends Notification
{
    public function __construct(public Task $task, public TaskComment $comment) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $isAdmin = in_array($notifiable->role, ['admin', 'manager']);
        $url = $isAdmin
            ? route('admin.tasks.show', $this->task->id)
            : route('user.tasks.show', $this->task->id);

        return [
            'title'   => 'New Comment: ' . Str::limit($this->task->title, 40),
            'message' => ($this->comment->user->name ?? 'Someone') . ': ' . Str::limit($this->comment->body, 80),
            'url'     => $url,
            'icon'    => 'fa-comment',
            'color'   => 'indigo',
        ];
    }
}
