<?php

namespace App\Http\Middleware;

use App\Services\Addons\AddonRegistry;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAddonEnabled
{
    public function __construct(
        private AddonRegistry $addons,
    ) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next, string $slug): Response
    {
        abort_unless($this->addons->isEnabled($slug), 404);

        return $next($request);
    }
}
