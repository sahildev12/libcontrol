<?php

namespace App\Http\Middleware;

use App\Support\Tenancy\LibrarySession;
use App\Support\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ShareLibrarySession
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($tenant = TenantContext::current()) {
            LibrarySession::sync($request, $response, $tenant, Auth::check());
        }

        return $response;
    }
}
