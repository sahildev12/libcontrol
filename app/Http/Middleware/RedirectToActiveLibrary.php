<?php

namespace App\Http\Middleware;

use App\Support\Tenancy\LibrarySession;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectToActiveLibrary
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($tenant = LibrarySession::activeTenant($request)) {
            return redirect()->away(LibrarySession::dashboardUrl($request, $tenant));
        }

        return $next($request);
    }
}
