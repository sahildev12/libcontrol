<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class BranchSeeder extends Seeder
{
    /**
     * Seed sample branches and branch admin users.
     */
    public function run(): void
    {
        $branches = [
            [
                'name' => 'Main Library Center',
                'user' => [
                    'name' => 'Main Center Admin',
                    'email' => 'admin@main.libspace.test',
                ],
            ],
            [
                'name' => 'North Branch Center',
                'user' => [
                    'name' => 'North Branch Admin',
                    'email' => 'admin@north.libspace.test',
                ],
            ],
        ];

        foreach ($branches as $branchData) {
            $branch = Branch::create([
                'name' => $branchData['name'],
            ]);

            User::create([
                'branch_id' => $branch->id,
                'name' => $branchData['user']['name'],
                'email' => $branchData['user']['email'],
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
            ]);
        }
    }
}
