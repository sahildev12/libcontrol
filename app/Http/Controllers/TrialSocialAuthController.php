<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Support\Tenancy\SocialLogin;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirectResponse;
use Throwable;

class TrialSocialAuthController extends Controller
{
    public const SESSION_KEY = 'trial_social';

    private const INTENT_KEY = 'trial_social_intent';

    public function redirect(Request $request, string $provider): SymfonyRedirectResponse
    {
        abort_unless(SocialLogin::enabled($provider), 404);

        $appHost = parse_url((string) config('app.url'), PHP_URL_HOST);
        if (is_string($appHost) && $appHost !== $request->getHost()) {
            return redirect()->away(rtrim((string) config('app.url'), '/').$request->getRequestUri());
        }

        $library = $request->filled('library') ? Tenant::normalizeSubdomain((string) $request->query('library')) : null;
        $request->session()->put(self::INTENT_KEY, $library);

        $driver = $this->driver($provider);
        if ($provider === 'google') {
            $driver->with(['prompt' => 'select_account']);
        }

        return $driver->redirect();
    }

    public function callback(Request $request, string $provider): RedirectResponse
    {
        abort_unless(SocialLogin::enabled($provider), 404);

        $library = $request->session()->pull(self::INTENT_KEY);
        $label = SocialLogin::label($provider);

        if ($request->filled('error') || ! $request->filled('code')) {
            return $this->fail($request, $library, 'cancelled', "{$label} sign-in was cancelled.");
        }

        try {
            $account = $this->driver($provider)->user();
        } catch (Throwable $e) {
            Log::info('Social sign-in failed', ['provider' => $provider, 'error' => $e->getMessage()]);

            return $this->fail($request, $library, 'failed', "We couldn't sign you in with {$label}. Please try again.");
        }

        $id = (string) $account->getId();
        $email = Str::lower(trim((string) $account->getEmail()));
        $emailVerified = $provider === 'google'
            && filter_var($account->getRaw()['email_verified'] ?? false, FILTER_VALIDATE_BOOLEAN);

        if ($id === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->fail($request, $library, 'no-email', "Your {$label} account didn't share an email address. Please sign up with your email instead.");
        }

        if ($library !== null) {
            $tenant = $this->ownedTenants($provider, $id, $email, $emailVerified)
                ->where('subdomain', $library)
                ->where('status', Tenant::STATUS_READY)
                ->first();

            return $tenant
                ? redirect()->away($this->tenantUrl($request, $tenant, '/trial/enter/'.$tenant->issueLoginToken()))
                : $this->fail($request, $library, 'not-owner', '');
        }

        $existing = $this->ownedTenants($provider, $id, $email, $emailVerified)
            ->where('status', '!=', Tenant::STATUS_FAILED)
            ->latest('id')
            ->first();

        if ($existing?->isReady()) {
            return redirect()->away($this->tenantUrl($request, $existing, '/trial/enter/'.$existing->issueLoginToken()));
        }

        if ($existing && $existing->is_trial && $existing->signup_token) {
            $request->session()->push('trial_signups', $existing->signup_token);

            return redirect()->route('trials.building', $existing->signup_token);
        }

        $request->session()->put(self::SESSION_KEY, [
            'provider' => $provider,
            'id' => $id,
            'email' => $email,
            'name' => trim((string) $account->getName()),
            'email_verified' => $emailVerified,
            'expires_at' => now()->addMinutes(30)->getTimestamp(),
        ]);

        return redirect()->route('trials.create');
    }

    public function forget(Request $request): RedirectResponse
    {
        $request->session()->forget(self::SESSION_KEY);

        return redirect()->route('trials.create');
    }

    /**
     * @return array{provider: string, id: string, email: string, name: string, email_verified: bool, expires_at: int}|null
     */
    public static function pending(Request $request): ?array
    {
        $social = $request->session()->get(self::SESSION_KEY);

        if (! is_array($social) || ($social['expires_at'] ?? 0) < now()->getTimestamp() || ! SocialLogin::enabled($social['provider'] ?? '')) {
            $request->session()->forget(self::SESSION_KEY);

            return null;
        }

        return $social;
    }

    /**
     * Tenants this account may sign in to. Facebook does not guarantee a verified email,
     * so only Google matches on email; otherwise the stored account id must match.
     */
    private function ownedTenants(string $provider, string $id, string $email, bool $emailVerified): Builder
    {
        return Tenant::query()->where(function (Builder $query) use ($provider, $id, $email, $emailVerified) {
            $query->where(fn (Builder $q) => $q->where('signup_source', $provider)->where('owner_social_id', $id));

            if ($emailVerified) {
                $query->orWhere('owner_email', $email);
            }
        });
    }

    private function fail(Request $request, ?string $library, string $code, string $message): RedirectResponse
    {
        if ($library !== null) {
            $tenant = Tenant::query()->where('subdomain', $library)->first();
            if ($tenant) {
                return redirect()->away($this->tenantUrl($request, $tenant, '/admin/login?signin='.$code));
            }
        }

        return redirect()->route('trials.create')->with('trial_social_error', $message);
    }

    private function driver(string $provider): Provider
    {
        $driver = Socialite::driver($provider);

        return $provider === 'facebook' ? $driver->scopes(['email']) : $driver;
    }

    private function tenantUrl(Request $request, Tenant $tenant, string $path): string
    {
        $port = in_array($request->getPort(), [80, 443], true) ? '' : ':'.$request->getPort();

        return $request->getScheme().'://'.$tenant->host().$port.$path;
    }
}
