<?php

namespace App\Support\Tenancy;

use App\Models\Tenant;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Response;

/**
 * Session cookies are scoped to each library's host, so the main site cannot see who is
 * logged in. This cookie on the shared parent domain names the library the browser is
 * signed in to, letting signup pages send that visitor to their dashboard instead.
 */
class LibrarySession
{
    public const COOKIE = 'lc_library';

    public static function sync(Request $request, Response $response, Tenant $tenant, bool $signedIn): void
    {
        $domain = self::cookieDomain($request);
        if ($domain === null) {
            return;
        }

        if ($signedIn) {
            $response->headers->setCookie(self::make($tenant->subdomain, (int) config('session.lifetime', 120), $domain));
        } elseif ($request->cookie(self::COOKIE) === $tenant->subdomain) {
            $response->headers->setCookie(self::make('', -2628000, $domain));
        }
    }

    public static function activeTenant(Request $request): ?Tenant
    {
        $subdomain = $request->cookie(self::COOKIE);
        if (! is_string($subdomain) || $subdomain === '') {
            return null;
        }

        return Tenant::query()
            ->where('subdomain', $subdomain)
            ->where('status', Tenant::STATUS_READY)
            ->first();
    }

    public static function dashboardUrl(Request $request, Tenant $tenant): string
    {
        $port = in_array($request->getPort(), [80, 443], true) ? '' : ':'.$request->getPort();

        return $request->getScheme().'://'.$tenant->host().$port.'/dashboard';
    }

    private static function cookieDomain(Request $request): ?string
    {
        $base = (string) config('libcontrol.tenancy.base_domain');

        return $base !== '' && str_ends_with($request->getHost(), '.'.$base) ? '.'.$base : null;
    }

    private static function make(string $value, int $minutes, string $domain): Cookie
    {
        return cookie(self::COOKIE, $value, $minutes, '/', $domain, config('session.secure'), true, false, 'lax');
    }
}
