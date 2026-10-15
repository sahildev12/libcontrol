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
        $packages = self::filterPackages(config('growth.packages', []));

        if (isset($packages['payment_test'])) {
            $test = $packages['payment_test'];
            unset($packages['payment_test']);

            return ['payment_test' => $test] + $packages;
        }

        return $packages;
    }

    /**
     * @param  array<string, array<string, mixed>>  $packages
     * @return array<string, array<string, mixed>>
     */
    public static function filterPackages(array $packages): array
    {
        if (self::includesDemoOnlyPackages()) {
            return $packages;
        }

        return array_filter(
            $packages,
            static fn (array $package): bool => empty($package['demo_only'])
        );
    }

    /**
     * ₹1 PhonePe test pack — demo install, demo host, or explicit env on hub.
     */
    public static function includesDemoOnlyPackages(): bool
    {
        if (self::isDemoInstall()) {
            return true;
        }

        $host = LicensedDeployment::normalizeDomain((string) request()->getHost());
        if ($host !== '' && self::isDemoDeploymentDomain($host)) {
            return true;
        }

        return (bool) config('growth.payment_test_visible', false);
    }

    public static function paymentTestCheckoutAllowed(?string $domain): bool
    {
        $domain = LicensedDeployment::normalizeDomain((string) $domain);
        if ($domain !== '' && self::isDemoDeploymentDomain($domain)) {
            return true;
        }

        if (! config('growth.payment_test_visible', false)) {
            return false;
        }

        $hosts = config('growth.payment_test_hosts', []);

        return $domain !== '' && in_array($domain, $hosts, true);
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

        if (! empty($package['demo_only']) && ! self::includesDemoOnlyPackages()) {
            abort(422, 'This package is only available on the public demo site.');
        }
    }
}
