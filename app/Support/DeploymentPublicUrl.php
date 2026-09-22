<?php

namespace App\Support;

use App\Models\LicensedDeployment;

class DeploymentPublicUrl
{
    public static function isLocalhost(string $url): bool
    {
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));

        return in_array($host, ['localhost', '127.0.0.1', '::1'], true);
    }

    /**
     * URL the student mobile app should call (reachable from phones, not loopback).
     */
    public static function forMobile(?string $configuredPublicUrl, ?string $appUrl, ?string $fallbackDomain = null): string
    {
        $public = trim((string) $configuredPublicUrl);
        $url = $public !== '' ? rtrim($public, '/') : rtrim((string) $appUrl, '/');

        if ($url === '') {
            $domain = LicensedDeployment::normalizeDomain((string) $fallbackDomain);

            return $domain !== '' ? 'https://'.$domain : '';
        }

        if (self::isLocalhost($url)) {
            $domain = LicensedDeployment::normalizeDomain((string) $fallbackDomain);
            if ($domain !== '') {
                return 'https://'.$domain;
            }
        }

        return $url;
    }
}
