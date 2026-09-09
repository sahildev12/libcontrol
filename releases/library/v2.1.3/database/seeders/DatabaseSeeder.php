<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            AdminSeeder::class,
            PlatformSettingsSeeder::class,
            BranchSeeder::class,
            HallSeeder::class,
        ]);

        if (filter_var(env('LIBCONTROL_SEED_DEMO', false), FILTER_VALIDATE_BOOLEAN)) {
            $this->call(LandingDemoSeeder::class);
        }
    }
}
