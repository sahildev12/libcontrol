<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\Branch;
use App\Models\PlatformSetting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Public demo install (demo.libcontrol.in): sample library data plus the
 * demo logins shown on the sign-in page. No developer account is created.
 *
 * php artisan db:seed --class=DemoSiteSeeder --force
 */
class DemoSiteSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            PlatformSettingsSeeder::class,
            LandingDemoSeeder::class,
            GrowthReferralDemoSeeder::class,
        ]);

        PlatformSetting::current()->update([
            'display_name' => 'LibControl Demo Library',
            'plan_tier' => 'pro',
            'website_enabled' => true,
            'website_tagline' => 'Peaceful study spaces for focused minds',
            'website_hero_title' => 'Your quiet corner to study, focus and succeed',
            'website_about' => 'Air-conditioned reading halls, high-speed Wi-Fi, comfortable seating and flexible shifts for students preparing for exams.',
        ]);

        $adminUser = $this->demoUser(
            (string) config('libcontrol.demo.admin_email'),
            (string) config('libcontrol.demo.admin_password'),
            'Demo Library Owner',
            null,
        );

        Admin::query()->firstOrCreate(
            ['user_id' => $adminUser->id],
            ['admin_type' => Admin::TYPE_CLIENT],
        );

        $mainBranch = Branch::query()->where('name', 'Main Library Center')->first()
            ?? Branch::query()->orderBy('id')->first();

        if ($mainBranch) {
            $this->demoUser(
                (string) config('libcontrol.demo.branch_email'),
                (string) config('libcontrol.demo.branch_password'),
                'Demo Branch Staff',
                $mainBranch->id,
            );
        }
    }

    private function demoUser(string $email, string $password, string $name, ?int $branchId): User
    {
        return User::query()->updateOrCreate(
            ['email' => $email],
            [
                'branch_id' => $branchId,
                'name' => $name,
                'email_verified_at' => now(),
                'password' => Hash::make($password),
            ],
        );
    }
}
