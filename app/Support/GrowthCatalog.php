<?php

namespace App\Support;

use App\Models\LicensedDeployment;

class GrowthCatalog
{
    /**
     * @return array<string, array<string, mixed>>
     */
    public static function packagesForUi(): array
    {
        return self::filterPackages(config('growth.packages', []));
    }

    /**
     * @param  array<string, array<string, mixed>>  $packages
     * @return array<string, array<string, mixed>>
     */
    public static function filterPackages(array $packages): array
    {
        if (self::isDemoInstall()) {
            return $packages;
        }

        return array_filter(
            $packages,
            static fn (array $package): bool => empty($package['demo_only'])
        );
    }

    public static function isDemoInstall(): bool
    {
        return (bool) config('libcontrol.demo.enabled', false);
    }

    public static function isDemoDeploymentDomain(?string $domain): bool
    {
        $demoHost = LicensedDeployment::normalizeDomain(
            (string) config('library-website.demo_host', 'demo.libcontrol.in')
        );
        $domain = LicensedDeployment::normalizeDomain((string) $domain);

        return $demoHost !== '' && $domain === $demoHost;
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function package(string $key): ?array
    {
        $package = config("growth.packages.{$key}");

        return is_array($package) ? $package : null;
    }

    public static function assertPackageAllowedOnInstall(string $key): void
    {
        $package = self::package($key);
        abort_unless($package, 422, 'Unknown growth package.');

        if (! empty($package['demo_only']) && ! self::isDemoInstall()) {
            abort(422, 'This package is only available on the public demo site.');
        }
    }
}
