<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Notifications\Notification;

class TaskTransferred extends Notification
{
    public function __construct(public int $count, public User $fromUser) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title'   => 'Tasks Transferred to You',
            'message' => $this->count . ' ' . ($this->count === 1 ? 'task' : 'tasks') . ' from ' . $this->fromUser->name . ' have been transferred to you.',
            'url'     => route('user.tasks.index'),
            'icon'    => 'fa-right-left',
            'color'   => 'amber',
        ];
    }
}
