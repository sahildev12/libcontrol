<?php

namespace App\Http\Middleware;

use App\Support\Runtime\DeploymentState;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureDeploymentLicensed
{
    public function __construct(
        private DeploymentState $state,
    ) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (config('libspace.license_server.enabled')) {
            return $next($request);
        }

        if ($this->shouldSkip($request)) {
            return $next($request);
        }

        if (! $this->state->isBlocked()) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'This installation is not licensed for this domain. Contact Phenomit for support.',
            ], 403);
        }

        return response()->view('errors.deployment-unlicensed', [], 403);
    }

    private function shouldSkip(Request $request): bool
    {
        return $request->is('up', 'api/runtime/sync');
    }
}
