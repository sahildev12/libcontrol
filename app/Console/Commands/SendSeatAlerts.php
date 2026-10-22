<?php

namespace App\Console\Commands;

use App\Services\EmailAutomation\SeatAlertService;
use Illuminate\Console\Command;

class SendSeatAlerts extends Command
{
    protected $signature = 'libcontrol:send-seat-alerts {--branch= : Limit to a branch ID}';

    protected $description = 'Send seat expiry sequence emails and owner vacant/high-occupancy digests';

    public function handle(SeatAlertService $seatAlerts): int
    {
        $branchId = $this->option('branch') ? (int) $this->option('branch') : null;
        $sent = $seatAlerts->sendScheduledAlerts($branchId);
        $this->info("Sent {$sent} seat alert email(s).");

        return self::SUCCESS;
    }
}
