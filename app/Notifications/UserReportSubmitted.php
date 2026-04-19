<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Notifications\Notification;

class UserReportSubmitted extends Notification
{
    public function __construct(
        public User   $reporter,
        public string $report
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title'   => 'Progress Report — ' . $this->reporter->name,
            'message' => $this->report,
            'url'     => route('admin.dashboard'),
            'icon'    => 'fa-file-lines',
            'color'   => 'indigo',
        ];
    }
}
