<?php

namespace App\Console\Commands;

use App\Models\Domain;
use App\Notifications\DomainExpiringSoon;
use Illuminate\Console\Command;

class CheckDomainRenewals extends Command
{
    protected $signature   = 'domains:check-renewals';
    protected $description = 'Send expiry reminder notifications to domain responsible persons';

    public function handle(): void
    {
        $domains = Domain::whereNotNull('expires_at')->with('responsibleUsers')->get();

        foreach ($domains as $domain) {
            $daysLeft = $domain->days_until_expiry;

            if ($daysLeft === null || $daysLeft < 0) continue;

            $notifyDays = $domain->notify_days;
            if (! in_array($daysLeft, $notifyDays)) continue;

            $responsibleUsers = $domain->responsibleUsers;

            foreach ($responsibleUsers as $user) {
                $user->notify(new DomainExpiringSoon($domain, $daysLeft));
            }

            $this->info("Notified {$responsibleUsers->count()} user(s) about: {$domain->domain} (expires in {$daysLeft} days)");
        }
    }
}
