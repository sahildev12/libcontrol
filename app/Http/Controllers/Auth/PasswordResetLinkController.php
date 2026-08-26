<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\LoginBrandingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    public function create(Request $request, LoginBrandingService $branding): View
    {
        $portal = $this->portal($request);

        return view('auth.forgot-password', $branding->forPortal($portal, $request));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $portal = $this->portal($request);
        $user = User::query()->where('email', $request->string('email'))->first();

        if ($user) {
            $user->loadMissing('adminProfile');
            $isAdmin = $user->isPlatformAdmin();

            if ($portal === LoginBrandingService::PORTAL_ADMIN && ! $isAdmin) {
                return back()->withInput($request->only('email'))
                    ->withErrors(['email' => 'Use the branch forgot-password page for this email.']);
            }

            if ($portal === LoginBrandingService::PORTAL_BRANCH && $isAdmin) {
                return back()->withInput($request->only('email'))
                    ->withErrors(['email' => 'Use the admin forgot-password page for this email.']);
            }
        }

        $status = Password::sendResetLink($request->only('email'));

        return $status == Password::RESET_LINK_SENT
            ? back()->with('status', __($status))
            : back()->withInput($request->only('email'))
                ->withErrors(['email' => __($status)]);
    }

    private function portal(Request $request): string
    {
        if ($request->routeIs('admin.password.request', 'admin.password.email')) {
            return LoginBrandingService::PORTAL_ADMIN;
        }

        return LoginBrandingService::PORTAL_BRANCH;
    }
}
