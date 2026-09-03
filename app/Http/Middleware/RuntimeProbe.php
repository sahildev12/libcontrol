<?php

namespace App\Http\Middleware;

use App\Support\Runtime\SyncCoordinator;
use App\Support\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RuntimeProbe
{
    public function __construct(
        private SyncCoordinator $coordinator,
    ) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($this->shouldSkip($request, $response)) {
            return $response;
        }

        app()->terminating(function () {
            $this->coordinator->maybeSync();
        });

        return $response;
    }

    private function shouldSkip(Request $request, Response $response): bool
    {
        if (config('libcontrol.license_server.enabled')) {
            return true;
        }

        if (config('libcontrol.tenancy.enabled') && TenantContext::isTenantRequest()) {
            return true;
        }

        if ($response->getStatusCode() >= 500) {
            return true;
        }

        if ($request->is('up', 'build/*', 'storage/*', 'vendor/*', 'api/runtime/sync')) {
            return true;
        }

        if ($request->expectsJson() || $request->ajax()) {
            return true;
        }

        return false;
    }
}
