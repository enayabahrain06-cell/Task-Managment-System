<?php

namespace App\Notifications;

use App\Models\Domain;
use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DomainAssigned extends Notification
{
    public function __construct(
        public Domain $domain,
        public User $assignedBy
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $expiry = $this->domain->expires_at
            ? $this->domain->expires_at->format('d M Y')
            : 'No expiry set';

        return [
            'title'   => 'Domain Responsibility Assigned',
            'message' => "You are now responsible for the domain: {$this->domain->domain}",
            'url'     => route('user.domains.index'),
            'icon'    => 'fa-globe',
            'color'   => 'indigo',
            'meta'    => [
                'domain'     => $this->domain->domain,
                'registrar'  => $this->domain->registrar,
                'expires_at' => $expiry,
                'assigned_by'=> $this->assignedBy->name,
            ],
        ];
    }
}
