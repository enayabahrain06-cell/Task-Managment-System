<?php
namespace App\Notifications;

use App\Models\DeadlineExtensionRequest;
use App\Models\Task;
use Illuminate\Notifications\Notification;

class DeadlineExtensionResponded extends Notification
{
    public function __construct(public Task $task, public DeadlineExtensionRequest $extension) {}

    public function via(object $notifiable): array { return ['database']; }

    public function toDatabase(object $notifiable): array
    {
        $approved = $this->extension->status === 'approved';
        return [
            'title'   => $approved ? 'Deadline Extension Approved' : 'Deadline Extension Rejected',
            'message' => $approved
                ? 'Your deadline extension for "' . $this->task->title . '" was approved. New deadline: ' . $this->extension->requested_deadline->format('M d, Y')
                : 'Your deadline extension for "' . $this->task->title . '" was rejected.' . ($this->extension->admin_note ? ' Note: ' . $this->extension->admin_note : ''),
            'url'     => route('user.tasks.show', $this->task->id),
            'icon'    => $approved ? 'fa-calendar-check' : 'fa-calendar-xmark',
            'color'   => $approved ? 'green' : 'red',
        ];
    }
}
