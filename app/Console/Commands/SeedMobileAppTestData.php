<?php

namespace App\Console\Commands;

use Database\Seeders\LandingDemoSeeder;
use Database\Seeders\MobileAppTestSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class SeedMobileAppTestData extends Command
{
    protected $signature = 'libcontrol:seed-mobile-app
                            {--with-demo : Run libcontrol:seed-demo first (refreshes demo branches and seats)}
                            {--demo-only : Only run demo seed, skip mobile test accounts}';

    protected $description = 'Seed AIMS/client data for mobile app testing (all seat/payment/PIN scenarios)';

    public function handle(): int
    {
        if ($this->option('with-demo')) {
            $this->info('Seeding full demo library data…');
            Artisan::call('libcontrol:seed-demo');
            $this->line(trim(Artisan::output()));
        }

        if ($this->option('demo-only')) {
            $this->call(LandingDemoSeeder::class);

            return self::SUCCESS;
        }

        $this->info('Creating mobile test students (safe to re-run)…');
        $this->call(MobileAppTestSeeder::class);

        return self::SUCCESS;
    }
}
