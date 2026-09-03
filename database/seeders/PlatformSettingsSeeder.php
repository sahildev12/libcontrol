<?php

namespace Database\Seeders;

use App\Models\PlatformSetting;
use Illuminate\Database\Seeder;

class PlatformSettingsSeeder extends Seeder
{
    public function run(): void
    {
        PlatformSetting::query()->firstOrCreate([], [
            'student_code_prefix' => 'LIB',
            'student_code_padding' => config('libcontrol.defaults.student_code_padding', 3),
            'plan_tier' => config('libcontrol.defaults.plan_tier', 'starter'),
        ]);
    }
}
