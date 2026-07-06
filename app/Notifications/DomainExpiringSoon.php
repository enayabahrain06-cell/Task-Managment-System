<?php

namespace App\Notifications;

use App\Models\Domain;
use Illuminate\Notifications\Notification;

class DomainExpiringSoon extends Notification
{
    public function __construct(
        public Domain $domain,
        public int $daysLeft
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $label = $this->daysLeft === 0 ? 'today' : "in {$this->daysLeft} day" . ($this->daysLeft === 1 ? '' : 's');

        return [
            'title'   => 'Domain Expiring Soon',
            'message' => "{$this->domain->domain} expires {$label}" . ($this->domain->expires_at ? ' — ' . $this->domain->expires_at->format('d M Y') : ''),
            'url'     => route('user.domains.show', $this->domain->id),
            'icon'    => 'fa-triangle-exclamation',
            'color'   => $this->daysLeft <= 7 ? 'red' : 'amber',
        ];
    }
}
