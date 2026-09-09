<?php

namespace App\Console\Commands;

use Database\Seeders\LandingDemoSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

class SeedDemoData extends Command
{
    protected $signature = 'libcontrol:seed-demo {--fresh-admins : Re-run DeveloperInstallSeeder before demo data}';

    protected $description = 'Seed LibControl with demo branches, students, seats, fees, and expenses';

    public function handle(): int
    {
        File::ensureDirectoryExists(storage_path('app'));

        if (! File::exists(storage_path('app/install.lock'))) {
            File::put(storage_path('app/install.lock'), now()->toIso8601String());
            $this->info('Created storage/app/install.lock so the app skips the setup wizard.');
        }

        if ($this->option('fresh-admins')) {
            Artisan::call('db:seed', ['--class' => 'DeveloperInstallSeeder', '--force' => true]);
            $this->line(trim(Artisan::output()));
        }

        $this->call(LandingDemoSeeder::class);

        $this->newLine();
        $this->info('Demo data ready.');
        $this->line('Platform logins: use your LIBCONTROL_DEVELOPER_* and LIBCONTROL_ADMIN_* credentials from .env');
        $this->line('Branch logins: admin@main.LibControl.test / password and admin@north.LibControl.test / password');

        return self::SUCCESS;
    }
}
