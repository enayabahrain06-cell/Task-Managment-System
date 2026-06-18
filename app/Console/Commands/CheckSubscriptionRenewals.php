<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use App\Models\User;
use App\Notifications\SubscriptionRenewalReminder;
use Illuminate\Console\Command;

class CheckSubscriptionRenewals extends Command
{
    protected $signature   = 'subscriptions:check-renewals';
    protected $description = 'Send renewal reminder notifications for subscriptions due soon';

    public function handle(): void
    {
        $subscriptions = Subscription::whereNotNull('renewal_date')->get();

        foreach ($subscriptions as $subscription) {
            $daysLeft = $subscription->days_until_renewal;

            if ($daysLeft === null || $daysLeft < 0) continue;

            $notifyDays = $subscription->notify_days;
            if (! in_array($daysLeft, $notifyDays)) continue;

            $adminsAndManagers = User::whereIn('role', ['admin', 'manager'])
                ->where('status', 'active')
                ->get();

            foreach ($adminsAndManagers as $user) {
                $user->notify(new SubscriptionRenewalReminder($subscription, $daysLeft));
            }

            $this->info("Notified {$adminsAndManagers->count()} users about: {$subscription->name} (expires in {$daysLeft} days)");
        }
    }
}
