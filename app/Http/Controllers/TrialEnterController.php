<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

class TrialEnterController extends Controller
{
    public function __invoke(Request $request, string $token): RedirectResponse
    {
        $tenant = TenantContext::current();
        abort_unless($tenant, 404);

        $user = $tenant->consumeLoginToken($token)
            ? User::query()->where('email', $tenant->owner_email)->first()
            : null;

        if (! $user) {
            return redirect()->route('admin.login')
                ->with('status', 'That sign-in link has expired. Please log in with your email and password.');
        }

        Auth::login($user, remember: true);
        $request->session()->regenerate();
        $request->session()->put('login_portal', 'admin');
        $request->session()->put('active_branch_id', 'all');

        return redirect()->route(Route::has('profile-completion.index') ? 'profile-completion.index' : 'dashboard');
    }
}
