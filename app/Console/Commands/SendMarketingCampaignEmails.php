<?php

namespace App\Console\Commands;

use App\Services\EmailAutomation\MarketingCampaignService;
use Illuminate\Console\Command;

class SendMarketingCampaignEmails extends Command
{
    protected $signature = 'libcontrol:send-marketing-campaign-emails';

    protected $description = 'Send opted-in marketing and product update emails to owners';

    public function handle(MarketingCampaignService $marketing): int
    {
        $sent = $marketing->sendScheduledCampaign();
        $this->info("Sent {$sent} marketing campaign email(s).");

        return self::SUCCESS;
    }
}
