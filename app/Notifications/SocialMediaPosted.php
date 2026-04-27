<?php

namespace App\Notifications;

use App\Models\Task;
use App\Models\User;
use Illuminate\Notifications\Notification;

class SocialMediaPosted extends Notification
{
    public function __construct(public Task $task, public User $postedBy) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title'   => 'Social Media Post Published',
            'message' => '"' . $this->task->title . '" has been posted on social media by ' . $this->postedBy->name . '.',
            'url'     => route('admin.tasks.show', $this->task->id),
            'icon'    => 'fa-circle-check',
            'color'   => 'green',
        ];
    }
}
