<?php

namespace App\Console\Commands;

use App\Services\EmailAutomation\FeeAlertService;
use Illuminate\Console\Command;

class SendFeeAlerts extends Command
{
    protected $signature = 'libcontrol:send-fee-alerts {--branch= : Limit to a branch ID}';

    protected $description = 'Send automated fee due and overdue reminder emails to students';

    public function handle(FeeAlertService $feeAlerts): int
    {
        $branchId = $this->option('branch') ? (int) $this->option('branch') : null;
        $sent = $feeAlerts->sendScheduledAlerts($branchId);
        $this->info("Sent {$sent} fee alert email(s).");

        return self::SUCCESS;
    }
}
