<?php

namespace App\Notifications;

use App\Models\Subscription;
use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SubscriptionRemoved extends Notification
{
    public function __construct(
        public Subscription $subscription,
        public User $removedBy
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title'   => 'Subscription Access Removed',
            'message' => "Your access to {$this->subscription->name} has been revoked",
            'url'     => route('admin.subscriptions.index'),
            'icon'    => 'fa-ban',
            'color'   => 'red',
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Access removed: {$this->subscription->name}")
            ->greeting("Hello {$notifiable->name},")
            ->line("{$this->removedBy->name} has removed your access to the **{$this->subscription->name}** subscription.")
            ->line('If you believe this is a mistake, please contact your administrator.');
    }
}
