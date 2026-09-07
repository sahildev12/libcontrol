<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\PlatformSetting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DeveloperInstallSeeder extends Seeder
{
    public function run(): void
    {
        $productName = (string) (config('libcontrol.install.product_name') ?: config('app.name') ?: 'LibControl');

        PlatformSetting::current()->update([
            'display_name' => $productName,
            'student_code_prefix' => PlatformSetting::current()->student_code_prefix ?: 'LIB',
        ]);

        $this->createDeveloperAdmin();
        $this->createClientAdmin();
    }

    private function createDeveloperAdmin(): void
    {
        $email = (string) config('libcontrol.install.developer_email');
        $password = (string) config('libcontrol.install.developer_password');

        if ($email === '' || $password === '') {
            throw new \RuntimeException('Hidden developer admin credentials are missing.');
        }

        $user = User::query()->updateOrCreate(
            ['email' => $email],
            [
                'branch_id' => null,
                'name' => 'Admin',
                'email_verified_at' => now(),
                'password' => Hash::make($password),
            ],
        );

        Admin::query()->updateOrCreate(
            ['user_id' => $user->id],
            ['admin_type' => Admin::TYPE_DEVELOPER],
        );
    }

    private function createClientAdmin(): void
    {
        $email = (string) config('libcontrol.install.admin_email');
        $password = (string) config('libcontrol.install.admin_password');

        if ($email === '' || $password === '') {
            throw new \RuntimeException('Client admin email and password are required.');
        }

        $user = User::query()->updateOrCreate(
            ['email' => $email],
            [
                'branch_id' => null,
                'name' => 'Admin',
                'email_verified_at' => now(),
                'password' => Hash::make($password),
            ],
        );

        Admin::query()->updateOrCreate(
            ['user_id' => $user->id],
            ['admin_type' => Admin::TYPE_CLIENT],
        );
    }
}
