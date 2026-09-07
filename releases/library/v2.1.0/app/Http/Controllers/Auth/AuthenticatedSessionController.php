<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\ActivityLogger;
use App\Services\LoginBrandingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function create(Request $request, LoginBrandingService $branding): View
    {
        $portal = $this->portal($request);

        return view('auth.login', $branding->forPortal($portal, $request));
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();
        $request->session()->put('login_portal', $request->portal());

        if ($request->user()?->isPlatformAdmin()) {
            $request->session()->put('active_branch_id', 'all');
        }

        app(ActivityLogger::class)->record(
            $request->user(),
            'auth.login',
            "{$request->user()->name} signed in.",
            $request->user(),
            $request->user()->branch_id,
            ['portal' => $request->portal()],
            $request,
        );

        return redirect()->intended(route('dashboard', absolute: false));
    }

    public function destroy(Request $request): RedirectResponse
    {
        $user = $request->user();
        $loginRoute = $user?->isPlatformAdmin() ? 'admin.login' : 'login';

        app(ActivityLogger::class)->record(
            $user,
            'auth.logout',
            "{$user?->name} signed out.",
            $user,
            $user?->branch_id,
            [],
            $request,
        );

        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route($loginRoute);
    }

    private function portal(Request $request): string
    {
        if ($request->routeIs('admin.login', 'admin.login.store')) {
            return LoginBrandingService::PORTAL_ADMIN;
        }

        return LoginBrandingService::PORTAL_BRANCH;
    }
}
