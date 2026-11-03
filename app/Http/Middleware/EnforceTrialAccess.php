<?php

namespace App\Http\Middleware;

use App\Support\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnforceTrialAccess
{
    /**
     * Requests that must keep working in a paused or read-only trial so the owner can sign in, out, or verify.
     */
    private const ALWAYS_ALLOWED = [
        'login', 'login.store', 'admin.login', 'admin.login.store', 'logout',
        'password.request', 'password.email', 'password.reset', 'password.store',
        'trials.resend-verification', 'plan-limit.dismiss', 'active-branch.switch',
    ];

    /**
     * Unverified trials are paused after the verification window; ended trials are read-only.
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = TenantContext::current();

        if (! $tenant?->is_trial || $request->routeIs(...self::ALWAYS_ALLOWED)) {
            return $next($request);
        }

        if ($tenant->needsEmailVerification() && $request->user()) {
            $message = "Verify your email to keep using this library. We sent the link to {$tenant->owner_email}.";

            return $request->expectsJson()
                ? response()->json(['message' => $message], 403)
                : response()->view('trials.verify-required', ['tenant' => $tenant], 403);
        }

        if ($tenant->isReadOnly() && ! $request->isMethodSafe()) {
            $message = 'Your free trial has ended, so this library is read-only. Upgrade to make changes.';

            return $request->expectsJson()
                ? response()->json(['message' => $message], 423)
                : back()->with('trial_read_only', $message);
        }

        return $next($request);
    }
}
