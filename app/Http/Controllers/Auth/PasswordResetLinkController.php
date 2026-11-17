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
    public function create(Request $request, LoginBrandingService $branding): View|RedirectResponse
    {
        if ($request->query() !== []) {
            return redirect()->route('password.request', status: 301);
        }

        $host = strtolower($request->getHost());

        return view('auth.forgot-password', array_merge(
            $branding->forPasswordReset(),
            [
                'resetIntro' => $this->resetIntro($host),
            ],
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

    private function resetIntro(string $host): string
    {
        if (str_ends_with($host, '.libcontrol.in') && $host !== 'www.libcontrol.in') {
            return 'Reset a password for the library at '.$host.'. This login is separate from the LibControl marketing site.';
        }

        return 'Reset a LibControl account password for the main site at '.$host.'.';
    }
}
