<?php

namespace App\Notifications;

use App\Models\Task;
use App\Models\User;
use Illuminate\Notifications\Notification;

class SocialMediaAssigned extends Notification
{
    public function __construct(public Task $task, public User $assignedBy) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title'   => 'Social Media Post Assigned',
            'message' => $this->assignedBy->name . ' assigned you to post "' . $this->task->title . '" on social media.',
            'url'     => route('social.show', $this->task->id),
            'icon'    => 'fa-share-nodes',
            'color'   => 'blue',
        ];
    }
}
