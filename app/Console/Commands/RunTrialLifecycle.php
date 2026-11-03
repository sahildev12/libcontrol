<?php

namespace App\Console\Commands;

use App\Services\Tenancy\TrialLifecycle;
use Illuminate\Console\Command;

class RunTrialLifecycle extends Command
{
    protected $signature = 'libcontrol:trial-lifecycle {--dry-run : List what would happen without sending, locking or deleting}';

    protected $description = 'Send trial owner reminders, lock ended trials and delete unpaid trials after the grace period';

    public function handle(TrialLifecycle $lifecycle): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $actions = $lifecycle->run($dryRun);

        foreach ($actions as $action) {
            $this->line(($dryRun ? '[dry run] ' : '').$action);
        }

        $this->info(count($actions).' action(s)'.($dryRun ? ' would run.' : ' done.'));

        return self::SUCCESS;
    }
}
