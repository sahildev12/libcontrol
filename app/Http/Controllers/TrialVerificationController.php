<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Services\Tenancy\TrialLifecycle;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TrialVerificationController extends Controller
{
    public function __construct(private TrialLifecycle $lifecycle) {}

    public function verify(Request $request, Tenant $tenant, string $hash): View
    {
        $valid = $tenant->is_trial
            && $request->hasValidRelativeSignature()
            && hash_equals(sha1((string) $tenant->owner_email), $hash);

        if ($valid) {
            $this->lifecycle->markVerified($tenant);
        }

        return view('trials.verified', [
            'valid' => $valid,
            'tenant' => $tenant,
            'libraryUrl' => $tenant->url().'/dashboard',
        ]);
    }

    public function resend(Request $request): RedirectResponse
    {
        $tenant = TenantContext::current();
        abort_unless($tenant?->is_trial, 404);

        if ($tenant->owner_email_verified_at !== null) {
            return back()->with('trial_notice', 'Your email is already verified.');
        }

        $sent = $this->lifecycle->sendVerification($tenant);

        return back()->with('trial_notice', $sent
            ? "We sent a new verification link to {$tenant->owner_email}."
            : 'We could not send the email right now. Please try again in a few minutes.');
    }
}
