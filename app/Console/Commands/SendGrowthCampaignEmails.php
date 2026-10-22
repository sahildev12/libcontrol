<?php

namespace App\Console\Commands;

use App\Services\EmailAutomation\GrowthCampaignService;
use Illuminate\Console\Command;

class SendGrowthCampaignEmails extends Command
{
    protected $signature = 'libcontrol:send-growth-campaign-emails';

    protected $description = 'Send Grow Your Library lifecycle emails to opted-in owners';

    public function handle(GrowthCampaignService $growth): int
    {
        $sent = $growth->sendScheduledCampaign();
        $this->info("Sent {$sent} growth campaign email(s).");

        return self::SUCCESS;
    }
}
