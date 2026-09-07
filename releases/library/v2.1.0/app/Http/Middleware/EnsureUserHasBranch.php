<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasBranch
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(403, 'Authentication required.');
        }

        if ($user->branch_id) {
            return $next($request);
        }

        if ($user->isPlatformAdmin()) {
            if (! $request->session()->has('active_branch_id')) {
                $request->session()->put('active_branch_id', 'all');
            }

            return $next($request);
        }

        abort(403, 'Your account is not assigned to a branch.');
    }
}
