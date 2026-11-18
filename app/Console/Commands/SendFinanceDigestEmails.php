<?php

namespace App\Console\Commands;

use App\Services\EmailAutomation\FinanceDigestService;
use Illuminate\Console\Command;

class SendFinanceDigestEmails extends Command
{
    protected $signature = 'libcontrol:send-finance-digest
                            {--branch= : Limit metrics to a branch ID}
                            {--dry-run : Count recipients without sending}';

    protected $description = 'Send weekly finance digest emails to client admins (IST, rate-limited)';

    public function handle(FinanceDigestService $digest): int
    {
        $branchId = $this->option('branch') ? (int) $this->option('branch') : null;
        $dryRun = (bool) $this->option('dry-run');
        $sent = $digest->sendWeeklyDigest($branchId, $dryRun);

        $this->info(($dryRun ? 'Would send' : 'Sent')." {$sent} finance digest email(s).");

        return self::SUCCESS;
    }
}
