<?php

namespace App\Notifications;

use App\Models\Subscription;
use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SubscriptionAssigned extends Notification
{
    public function __construct(
        public Subscription $subscription,
        public User $assignedBy
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title'   => 'Subscription Access Granted',
            'message' => "You have been given access to: {$this->subscription->name}",
            'url'     => route('admin.subscriptions.show', $this->subscription->id),
            'icon'    => 'fa-key',
            'color'   => 'indigo',
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("You've been added to: {$this->subscription->name}")
            ->greeting("Hello {$notifiable->name},")
            ->line("{$this->assignedBy->name} has granted you access to the **{$this->subscription->name}** subscription.")
            ->line("**Vendor:** " . ($this->subscription->vendor ?? '—'))
            ->line("**Category:** " . ucfirst($this->subscription->category))
            ->action('View Subscription', route('admin.subscriptions.show', $this->subscription->id));
    }
}
