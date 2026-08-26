<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\LoginBrandingService;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class NewPasswordController extends Controller
{
    public function create(Request $request, LoginBrandingService $branding): View
    {
        $portal = $request->query('portal', LoginBrandingService::PORTAL_BRANCH);

        return view('auth.reset-password', array_merge(
            $branding->forPortal($portal, $request),
            ['request' => $request, 'portal' => $portal],
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'portal' => ['nullable', 'in:admin,branch'],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user) use ($request) {
                $user->forceFill([
                    'password' => $request->password,
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        $portal = $request->input('portal', LoginBrandingService::PORTAL_BRANCH);
        $loginRoute = $portal === LoginBrandingService::PORTAL_ADMIN ? 'admin.login' : 'login';

        return $status == Password::PASSWORD_RESET
            ? redirect()->route($loginRoute)->with('status', __($status))
            : back()->withInput($request->only('email'))
                ->withErrors(['email' => __($status)]);
    }
}
