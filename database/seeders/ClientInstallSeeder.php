<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class ClientInstallSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(PlatformSettingsSeeder::class);

        $email = (string) config('libspace.install.admin_email');
        $password = (string) config('libspace.install.admin_password');
        $name = (string) config('libspace.install.admin_name', 'Library Admin');

        if ($email === '' || $password === '') {
            return;
        }

        $user = User::query()->firstOrCreate(
            ['email' => $email],
            [
                'branch_id' => null,
                'name' => $name,
                'email_verified_at' => now(),
                'password' => Hash::make($password),
            ],
        );

        Admin::query()->firstOrCreate(
            ['user_id' => $user->id],
            ['admin_type' => Admin::TYPE_CLIENT],
        );
    }
}
