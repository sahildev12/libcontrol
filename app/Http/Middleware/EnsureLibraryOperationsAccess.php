<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureLibraryOperationsAccess
{
    /**
     * Developer accounts are limited to Phenomit support tools, not day-to-day library operations.
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user?->isDeveloperAdmin()) {
            return $next($request);
        }

        if ($request->is(
            'logout',
            'settings',
            'settings/*',
            'developer',
            'developer/*',
            'profile',
            'profile/*',
        )) {
            return $next($request);
        }

        $redirect = \Illuminate\Support\Facades\Route::has('developer.deployments.index')
            ? route('developer.deployments.index')
            : route('settings.index', ['tab' => 'developer']);

        return redirect()->to($redirect);
    }
}
