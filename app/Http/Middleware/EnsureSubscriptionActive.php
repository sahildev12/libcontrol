<?php

namespace App\Http\Middleware;

use App\Support\Subscription;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSubscriptionActive
{
    /**
     * Once the subscription (plus grace) has ended, library users can only renew, contact support, or log out.
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || $user->isDeveloperAdmin() || ! $this->isPanelRoute($request) || ! Subscription::isLocked()) {
            return $next($request);
        }

        if ($request->is('logout') || ($user->isClientAdmin() && $request->is('billing', 'billing/*', 'help-support', 'help-support/*'))) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Your LibControl subscription has ended. Renew it from Subscription & Billing to continue.',
            ], 402);
        }

        if ($user->isClientAdmin()) {
            return redirect()->route('billing.index');
        }

        return response()->view('errors.subscription-expired', [
            'paidUntil' => Subscription::status()['paid_until'],
        ], 402);
    }

    private function isPanelRoute(Request $request): bool
    {
        return in_array('auth', $request->route()?->gatherMiddleware() ?? [], true);
    }
}
