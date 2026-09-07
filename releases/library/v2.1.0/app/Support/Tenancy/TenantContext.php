<?php

namespace App\Support\Tenancy;

use App\Models\Tenant;

class TenantContext
{
    private static ?Tenant $tenant = null;

    private static bool $landlord = false;

    public static function setLandlordMode(): void
    {
        self::$landlord = true;
        self::$tenant = null;
    }

    public static function activate(Tenant $tenant): void
    {
        self::$tenant = $tenant;
        self::$landlord = false;
    }

    public static function current(): ?Tenant
    {
        return self::$tenant;
    }

    public static function isLandlordRequest(): bool
    {
        return self::$landlord;
    }

    public static function isTenantRequest(): bool
    {
        return self::$tenant !== null;
    }

    public static function reset(): void
    {
        self::$tenant = null;
        self::$landlord = false;
    }
}
