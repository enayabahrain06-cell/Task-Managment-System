<?php

namespace App\Notifications;

use App\Models\Subscription;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SubscriptionRenewalReminder extends Notification
{
    public function __construct(
        public Subscription $subscription,
        public int $daysLeft
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toDatabase(object $notifiable): array
    {
        $label = $this->daysLeft === 0 ? 'today' : "in {$this->daysLeft} day" . ($this->daysLeft === 1 ? '' : 's');
        return [
            'title'   => 'Subscription Renewal Due',
            'message' => "{$this->subscription->name} renews {$label} — {$this->subscription->currency} {$this->subscription->cost}",
            'url'     => route('admin.subscriptions.show', $this->subscription->id),
            'icon'    => 'fa-rotate',
            'color'   => $this->daysLeft <= 7 ? 'red' : 'amber',
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $label       = $this->daysLeft === 0 ? 'today' : "in {$this->daysLeft} day" . ($this->daysLeft === 1 ? '' : 's');
        $renewalDate = $this->subscription->renewal_date?->format('d M Y') ?? '—';

        return (new MailMessage)
            ->subject("⚠ Subscription Renewal Reminder: {$this->subscription->name}")
            ->greeting("Hello {$notifiable->name},")
            ->line("The subscription **{$this->subscription->name}** is due for renewal **{$label}**.")
            ->line("**Renewal Date:** {$renewalDate}")
            ->line("**Cost:** {$this->subscription->currency} {$this->subscription->cost} / {$this->subscription->billing_cycle}")
            ->action('View Subscription', route('admin.subscriptions.show', $this->subscription->id))
            ->line('Please renew promptly to avoid service interruption.');
    }
}
