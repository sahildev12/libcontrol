<?php

namespace App\Console\Commands;

use App\Services\StudentDataPurgeService;
use Database\Seeders\MobileAppTestSeeder;
use Illuminate\Console\Command;

class ResetStudentsForAppTesting extends Command
{
    protected $signature = 'libcontrol:reset-students-for-app-testing {--force : Required on production}';

    protected $description = 'Delete all students and recreate mobile app test accounts (PIN, seats, payments, attendance)';

    public function handle(StudentDataPurgeService $purge): int
    {
        if (app()->environment('production') && ! $this->option('force')) {
            $this->error('Add --force to run this on production.');

            return self::FAILURE;
        }

        if (! $this->option('force') && ! $this->confirm('Delete ALL students and related bookings, fees, and attendance?')) {
            return self::SUCCESS;
        }

        $removed = $purge->purgeAll();
        $this->warn("Removed {$removed} student(s).");

        $this->call(MobileAppTestSeeder::class);

        return self::SUCCESS;
    }
}
