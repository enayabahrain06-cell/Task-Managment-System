<?php
namespace App\Notifications;

use App\Models\DeadlineExtensionRequest;
use App\Models\Task;
use App\Models\User;
use Illuminate\Notifications\Notification;

class DeadlineExtensionRequested extends Notification
{
    public function __construct(public Task $task, public DeadlineExtensionRequest $extension, public User $requester) {}

    public function via(object $notifiable): array { return ['database']; }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title'   => 'Deadline Extension Requested',
            'message' => $this->requester->name . ' requested a deadline extension for "' . $this->task->title . '" — new date: ' . $this->extension->requested_deadline->format('M d, Y'),
            'url'     => route('admin.tasks.show', $this->task->id),
            'icon'    => 'fa-calendar-plus',
            'color'   => 'amber',
        ];
    }
}
