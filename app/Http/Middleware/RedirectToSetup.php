<?php

namespace App\Http\Middleware;

use App\Support\InstallState;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectToSetup
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        InstallState::configureRuntimeForInstallation();

        if (InstallState::isInstalled()) {
            return $next($request);
        }

        if (InstallState::allowsInstallerRequest($request->path())) {
            return $next($request);
        }

        return redirect()->route('setup.show');
    }
}
