<?php

namespace App\Console\Commands;

use App\Services\EmailAutomation\SubscriptionTrialService;
use Illuminate\Console\Command;

class SendSubscriptionTrialEmails extends Command
{
    protected $signature = 'libcontrol:send-subscription-trial-emails';

    protected $description = 'Send LibControl SaaS trial conversion emails to library owners';

    public function handle(SubscriptionTrialService $trials): int
    {
        $sent = $trials->sendScheduledAlerts();
        $this->info("Sent {$sent} subscription trial email(s).");

        return self::SUCCESS;
    }
}
