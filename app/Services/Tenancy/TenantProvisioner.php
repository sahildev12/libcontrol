<?php

namespace App\Services\Tenancy;

use App\Models\Admin;
use App\Models\PlatformSetting;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class TenantProvisioner
{
    public function __construct(
        private TenantConnectionManager $connections,
    ) {}

    public function provision(Tenant $tenant, string $adminEmail, string $adminPassword, string $adminName = 'Library Admin'): void
    {
        if (! $tenant->provisioned_at) {
            $this->migrateDatabase($tenant->database_name);
        }

        $this->connections->runOnTenant($tenant, function () use ($tenant, $adminEmail, $adminPassword, $adminName) {
            $settings = PlatformSetting::query()->firstOrCreate([], [
                'student_code_prefix' => 'LIB',
                'student_code_padding' => config('libcontrol.defaults.student_code_padding', 3),
                'plan_tier' => $tenant->planTier(),
                'max_seats_override' => $tenant->max_seats_override,
                'max_halls_override' => $tenant->max_halls_override,
                'max_branches_override' => $tenant->max_branches_override,
                'display_name' => $tenant->client_name,
            ]);

            $settings->update([
                'plan_tier' => $tenant->planTier(),
                'max_seats_override' => $tenant->max_seats_override,
                'max_halls_override' => $tenant->max_halls_override,
                'max_branches_override' => $tenant->max_branches_override,
                'display_name' => $tenant->client_name,
            ]);

            $user = User::query()->firstOrCreate(
                ['email' => $adminEmail],
                [
                    'branch_id' => null,
                    'name' => $adminName,
                    'email_verified_at' => now(),
                    'password' => Hash::make($adminPassword),
                ],
            );

            Admin::query()->firstOrCreate(
                ['user_id' => $user->id],
                ['admin_type' => Admin::TYPE_CLIENT],
            );
        });

        $tenant->update(['provisioned_at' => now()]);
    }

    public function syncPlan(Tenant $tenant): void
    {
        if (! $tenant->provisioned_at) {
            return;
        }

        $this->connections->runOnTenant($tenant, function () use ($tenant) {
            $settings = PlatformSetting::current();
            $settings->update([
                'plan_tier' => $tenant->planTier(),
                'max_seats_override' => $tenant->max_seats_override,
                'max_halls_override' => $tenant->max_halls_override,
                'max_branches_override' => $tenant->max_branches_override,
            ]);
        });
    }

    public function databaseExists(string $databaseName): bool
    {
        $connection = (string) config('libcontrol.tenancy.landlord_connection', 'mysql');
        $result = DB::connection($connection)->select(
            'SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = ?',
            [$databaseName],
        );

        return count($result) > 0;
    }

    public function migrateDatabase(string $databaseName): void
    {
        if (! $this->databaseExists($databaseName)) {
            throw new \RuntimeException('Database does not exist yet. Create it in your hosting panel first.');
        }

        $tenant = new Tenant(['database_name' => $databaseName]);

        $this->connections->runOnTenant($tenant, function () {
            Artisan::call('migrate', ['--force' => true]);
        });
    }

    public function isDatabaseMigrated(string $databaseName): bool
    {
        if (! $this->databaseExists($databaseName)) {
            return false;
        }

        try {
            $tenant = new Tenant(['database_name' => $databaseName]);

            return $this->connections->runOnTenant($tenant, function () {
                return DB::getSchemaBuilder()->hasTable('migrations');
            });
        } catch (\Throwable) {
            return false;
        }
    }
}
