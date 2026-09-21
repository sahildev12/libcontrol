<?php

namespace App\Support;

use Illuminate\Support\Facades\File;

class InstallState
{
    public static function lockPath(): string
    {
        return storage_path('app/install.lock');
    }

    public static function isInstalled(): bool
    {
        return File::exists(self::lockPath());
    }

    public static function needsInstallation(): bool
    {
        return ! self::isInstalled();
    }

    public static function isHub(): bool
    {
        return (bool) config('libcontrol.license_server.enabled');
    }

    public static function configureRuntimeForInstallation(): void
    {
        if (self::isInstalled()) {
            return;
        }

        config([
            'session.driver' => 'file',
            'cache.default' => 'file',
            'queue.default' => 'sync',
        ]);
    }

    public static function allowsInstallerRequest(?string $path): bool
    {
        $path = '/'.ltrim((string) $path, '/');

        foreach (['/setup', '/install', '/up', '/build'] as $prefix) {
            if ($path === $prefix || str_starts_with($path, $prefix.'/')) {
                return true;
            }
        }

        return false;
    }
}
