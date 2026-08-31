<?php

namespace App\Console\Commands;

use App\Support\Runtime\SyncCoordinator;
use Illuminate\Console\Command;

class SyncRuntimeMetrics extends Command
{
    protected $signature = 'app:sync-runtime-metrics';

    protected $description = 'Synchronize runtime metrics with the remote coordinator';

    public function handle(SyncCoordinator $coordinator): int
    {
        $coordinator->sync();

        return self::SUCCESS;
    }
}
