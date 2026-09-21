<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\ActivityLogger;
use App\Services\LoginBrandingService;
use App\Support\InstallState;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
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

        if ($request->user()?->isAnyAdmin()) {
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

        if ($request->user()?->isDeveloperAdmin()) {
            $target = Route::has('developer.deployments.index')
                ? route('developer.deployments.index', absolute: false)
                : route('settings.index', ['tab' => 'developer'], absolute: false);

            return redirect()->intended($target);
        }

        return redirect()->intended(route('dashboard', absolute: false));
    }

    public function destroy(Request $request): RedirectResponse
    {
        $user = $request->user();
        $portal = (string) $request->session()->get('login_portal', '');
        $loginRoute = match ($portal) {
            LoginBrandingService::PORTAL_DEVELOPER => Route::has('developer.login') ? 'developer.login' : 'admin.login',
            LoginBrandingService::PORTAL_ADMIN => 'admin.login',
            default => 'login',
        };

        if ($portal === '' && $user?->isDeveloperAdmin() && Route::has('developer.login')) {
            $loginRoute = 'developer.login';
        } elseif ($portal === '' && $user?->isAnyAdmin()) {
            $loginRoute = 'admin.login';
        }

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
        if ($request->routeIs('developer.login', 'developer.login.store')) {
            return LoginBrandingService::PORTAL_DEVELOPER;
        }

        if ($request->routeIs('admin.login', 'admin.login.store')) {
            return LoginBrandingService::PORTAL_ADMIN;
        }

        if ($request->routeIs('login', 'login.store')) {
            return LoginBrandingService::PORTAL_BRANCH;
        }

        return LoginBrandingService::PORTAL_BRANCH;
    }
}
