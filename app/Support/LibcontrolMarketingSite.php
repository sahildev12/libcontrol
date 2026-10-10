<?php

namespace App\Support;

use App\Support\Tenancy\TenantContext;
use Illuminate\Http\Request;

class LibcontrolMarketingSite
{
    public static function rootPath(): string
    {
        $configured = config('libcontrol.marketing_site.path');

        return is_string($configured) && $configured !== ''
            ? $configured
            : base_path('libcontrol-website');
    }

    public static function enabled(): bool
    {
        return (bool) config('libcontrol.marketing_site.enabled', true);
    }

    public static function isMarketingHost(string $host): bool
    {
        $host = strtolower($host);
        $hosts = collect(config('libcontrol.marketing_site.hosts', []))
            ->map(fn (string $value) => strtolower(trim($value)))
            ->filter()
            ->all();

        return in_array($host, $hosts, true);
    }

    public static function shouldServe(Request $request): bool
    {
        if (! static::enabled()) {
            return false;
        }

        if (TenantContext::isTenantRequest()) {
            return false;
        }

        $host = strtolower($request->getHost());

        if (static::isMarketingHost($host)) {
            return true;
        }

        return config('libcontrol.tenancy.enabled') && TenantContext::isLandlordRequest();
    }

    public static function resolveFile(string $relative): ?string
    {
        $relative = ltrim(str_replace(['\\', "\0"], ['/', ''], $relative), '/');
        if ($relative === '' || str_contains($relative, '..')) {
            return null;
        }

        $root = realpath(static::rootPath());
        if ($root === false) {
            return null;
        }

        $full = realpath($root.DIRECTORY_SEPARATOR.$relative);
        if ($full === false || ! str_starts_with($full, $root) || ! is_file($full)) {
            return null;
        }

        return $full;
    }

    public static function mimeType(string $absolutePath): string
    {
        return match (strtolower(pathinfo($absolutePath, PATHINFO_EXTENSION))) {
            'html' => 'text/html; charset=UTF-8',
            'css' => 'text/css; charset=UTF-8',
            'js' => 'application/javascript; charset=UTF-8',
            'json' => 'application/json; charset=UTF-8',
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            'webp' => 'image/webp',
            'ico' => 'image/x-icon',
            'svg' => 'image/svg+xml',
            default => 'application/octet-stream',
        };
    }
}
