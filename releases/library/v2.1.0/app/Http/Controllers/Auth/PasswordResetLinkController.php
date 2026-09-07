<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\LoginBrandingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    public function create(Request $request, LoginBrandingService $branding): View
    {
        return view('auth.forgot-password', array_merge(
            $branding->forPasswordReset(),
            ['loginReturn' => $this->loginReturnRoute($request)],
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $status = Password::sendResetLink($request->only('email'));

        return $status == Password::RESET_LINK_SENT
            ? back()->with('status', __($status))
            : back()->withInput($request->only('email'))
                ->withErrors(['email' => __($status)]);
    }

    private function loginReturnRoute(Request $request): string
    {
        return $request->query('from') === 'admin'
            ? route('admin.login')
            : route('login');
    }
}
