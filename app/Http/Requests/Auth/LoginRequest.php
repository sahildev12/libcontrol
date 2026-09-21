<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use App\Services\LoginBrandingService;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    public function portal(): string
    {
        if ($this->routeIs('developer.login', 'developer.login.store')) {
            return LoginBrandingService::PORTAL_DEVELOPER;
        }

        if ($this->routeIs('admin.login', 'admin.login.store')) {
            return LoginBrandingService::PORTAL_ADMIN;
        }

        return LoginBrandingService::PORTAL_BRANCH;
    }

    /**
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $credentials = $this->only('email', 'password');

        if (! Auth::attempt($credentials, $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        /** @var User $user */
        $user = Auth::user();
        $user->loadMissing('adminProfile');

        $portal = $this->portal();

        if ($portal === LoginBrandingService::PORTAL_DEVELOPER) {
            if (! $user->isDeveloperAdmin()) {
                Auth::logout();
                RateLimiter::hit($this->throttleKey());

                throw ValidationException::withMessages([
                    'email' => 'Use the admin or branch login page for this account.',
                ]);
            }
        } elseif ($portal === LoginBrandingService::PORTAL_ADMIN) {
            if (! $user->isClientAdmin()) {
                Auth::logout();
                RateLimiter::hit($this->throttleKey());

                throw ValidationException::withMessages([
                    'email' => $user->isDeveloperAdmin()
                        ? 'Use the developer login page for this account.'
                        : 'Use the branch login page for this account.',
                ]);
            }
        } elseif ($user->isAnyAdmin()) {
            Auth::logout();
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => $user->isDeveloperAdmin()
                    ? 'Use the developer login page for this account.'
                    : 'Use the admin login page for this account.',
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')).'|'.$this->portal().'|'.$this->ip());
    }
}
