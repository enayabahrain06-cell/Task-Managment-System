<?php

namespace App\Notifications;

use App\Models\ActivityReply;
use App\Models\Task;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class TaskTimelineReply extends Notification
{
    public function __construct(public Task $task, public ActivityReply $reply) {}

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
            'title'      => '💬 Timeline reply: ' . Str::limit($this->task->title, 40),
            'message'    => ($this->reply->user->name ?? 'Someone') . ': ' . Str::limit($this->reply->body, 80),
            'url'        => $url,
            'icon'       => 'fa-comments',
            'color'      => 'indigo',
            'notif_type' => 'timeline_reply',
        ];
    }
}
