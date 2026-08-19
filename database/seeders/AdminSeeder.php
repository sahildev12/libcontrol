<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $admins = [
            [
                'admin_type' => Admin::TYPE_DEVELOPER,
                'name' => 'Developer Admin',
                'email' => 'developer@libspace.dev',
            ],
            [
                'admin_type' => Admin::TYPE_CLIENT,
                'name' => 'Client Admin',
                'email' => 'client@libspace.test',
            ],
        ];

        foreach ($admins as $adminData) {
            $user = User::query()->firstOrCreate(
                ['email' => $adminData['email']],
                [
                    'branch_id' => null,
                    'name' => $adminData['name'],
                    'email_verified_at' => now(),
                    'password' => Hash::make('password'),
                ],
            );

            Admin::query()->firstOrCreate(
                ['user_id' => $user->id],
                ['admin_type' => $adminData['admin_type']],
            );
        }
    }
}
