<?php

namespace App\Services\Tenancy;

use App\Models\Tenant;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class TenantConnectionManager
{
    public function useLandlord(): void
    {
        $landlord = (string) config('libcontrol.tenancy.landlord_connection', 'mysql');

        Config::set('database.default', $landlord);
        DB::purge($landlord);
        DB::reconnect($landlord);
    }

    public function useTenant(Tenant $tenant): void
    {
        $landlord = (string) config('libcontrol.tenancy.landlord_connection', 'mysql');
        $base = config("database.connections.{$landlord}");

        if (! is_array($base)) {
            throw new \RuntimeException('Landlord database connection is not configured.');
        }

        Config::set('database.connections.tenant', array_merge($base, [
            'database' => $tenant->database_name,
        ]));

        Config::set('database.default', 'tenant');
        DB::purge('tenant');
        DB::reconnect('tenant');
    }

    public function runOnTenant(Tenant $tenant, callable $callback): mixed
    {
        $previousDefault = config('database.default');
        $this->useTenant($tenant);

        try {
            return $callback();
        } finally {
            Config::set('database.default', $previousDefault);
            DB::purge('tenant');
            if (is_string($previousDefault)) {
                DB::reconnect($previousDefault);
            }
        }
    }
}
