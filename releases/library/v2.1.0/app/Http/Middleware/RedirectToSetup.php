<?php

namespace App\Http\Middleware;

use App\Services\Setup\SetupInstaller;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectToSetup
{
    public function __construct(
        private SetupInstaller $installer,
    ) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (app()->runningUnitTests()) {
            return $next($request);
        }

        if ($this->installer->isInstalled()) {
            return $next($request);
        }

        if ($request->is('setup', 'setup/*', 'up', 'build/*')) {
            return $next($request);
        }

        return redirect()->route('setup.show');
    }
}
