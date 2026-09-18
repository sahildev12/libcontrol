<?php

namespace App\Console\Commands;

use App\Models\Admin;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class CreateClientAdmin extends Command
{
    protected $signature = 'libcontrol:create-client-admin
                            {email : Client admin login email}
                            {password : Client admin password (min 8 characters)}
                            {--name=Library Admin : Display name for the admin user}';

    protected $description = 'Create or update a platform client admin account';

    public function handle(): int
    {
        $email = strtolower(trim((string) $this->argument('email')));
        $password = (string) $this->argument('password');
        $name = trim((string) $this->option('name'));

        $validator = Validator::make([
            'email' => $email,
            'password' => $password,
            'name' => $name,
        ], [
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:8'],
            'name' => ['required', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) {
                $this->error($message);
            }

            return self::FAILURE;
        }

        $user = User::query()->updateOrCreate(
            ['email' => $email],
            [
                'branch_id' => null,
                'name' => $name,
                'email_verified_at' => now(),
                'password' => Hash::make($password),
            ],
        );

        Admin::query()->updateOrCreate(
            ['user_id' => $user->id],
            ['admin_type' => Admin::TYPE_CLIENT],
        );

        $this->info('Client admin ready.');
        $this->line("Email: {$email}");
        $this->line('Login URL: '.url('/admin/login'));

        return self::SUCCESS;
    }
}
