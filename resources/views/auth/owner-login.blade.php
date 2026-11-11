@php
    $hub = (bool) ($hub ?? false);
    $libraryTenant = $libraryTenant ?? null;
    $socialProviders = \App\Support\Tenancy\SocialLogin::providers();
    if (! $hub && ! $libraryTenant) {
        $socialProviders = [];
    }
    $formAction = route('admin.login.store');
    $forgotUrl = route('password.request', ['from' => 'admin']);
    $signinError = match (request()->query('signin')) {
        'cancelled' => 'Sign-in was cancelled.',
        'failed' => "We couldn't sign you in. Please try again.",
        'no-email' => "That account didn't share an email address. Please log in with your email and password.",
        'not-owner' => "That account isn't the owner of this library. Please log in with your email and password.",
        default => null,
    };
@endphp
<x-layouts.auth-owner-split title="Admin log in" active-tab="login">
    <h1 class="lc-owner-hello">Hello again!</h1>
    <p class="lc-owner-hello-sub">
        {{ $hub
            ? 'Log in as the library owner to open your LibControl workspace.'
            : 'Library owner log in — manage branches, seats and billing.' }}
    </p>

    <x-auth-session-status class="mb-3" :status="session('status')" />
    @if ($signinError)
        <div class="mb-3 rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700" role="alert">{{ $signinError }}</div>
    @endif

    <form method="POST" action="{{ $formAction }}" class="lc-owner-form">
        @csrf

        <div class="lc-owner-field-block">
            <label for="email" class="lc-owner-label">Email</label>
            <div class="lc-owner-field">
                <svg class="lc-owner-field__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                <input
                    id="email"
                    class="lc-owner-input"
                    type="email"
                    name="email"
                    value="{{ old('email') }}"
                    required
                    autofocus
                    autocomplete="username"
                    placeholder="you@library.com"
                >
            </div>
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="lc-owner-field-block">
            <div class="lc-owner-label-row">
                <label for="password" class="lc-owner-label">Password</label>
                <a href="{{ $forgotUrl }}" class="text-xs font-semibold text-[#2563eb] hover:underline">Recovery password</a>
            </div>
            <div class="lc-owner-field" x-data="{ show: false }">
                <svg class="lc-owner-field__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                <input
                    id="password"
                    class="lc-owner-input !pr-11"
                    :type="show ? 'text' : 'password'"
                    name="password"
                    required
                    autocomplete="current-password"
                    placeholder="Enter your password"
                >
                <button
                    type="button"
                    class="absolute inset-y-0 right-0 z-[1] flex items-center px-3 text-slate-400 hover:text-slate-700"
                    @click="show = ! show"
                    :aria-label="show ? 'Hide password' : 'Show password'"
                >
                    <svg x-show="! show" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    <svg x-show="show" x-cloak class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                </button>
            </div>
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <label for="remember_me" class="inline-flex items-center gap-2.5 text-sm text-slate-600">
            <input id="remember_me" type="checkbox" class="rounded border-gray-300 text-[#2563eb] shadow-sm focus:ring-[#2563eb]" name="remember">
            Remember me
        </label>

        <button type="submit" class="lc-owner-btn">
            Sign in
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.4" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
        </button>
    </form>

    @if ($socialProviders !== [])
        <div class="lc-owner-social-block" data-library-social-login>
            <div class="lc-owner-social-divider">
                <span class="h-px flex-1 bg-slate-200"></span>
                Or continue with
                <span class="h-px flex-1 bg-slate-200"></span>
            </div>
            <div class="flex items-center justify-center gap-2.5">
                @foreach ($socialProviders as $provider => $label)
                    <a
                        href="{{ \App\Support\Tenancy\SocialLogin::redirectUrl($provider, $libraryTenant?->subdomain) }}"
                        class="lc-owner-social"
                        title="Continue with {{ $label }}"
                        aria-label="Continue with {{ $label }}"
                        data-social-provider="{{ $provider }}"
                    >
                        @include('trials.partials.social-icon', ['provider' => $provider])
                    </a>
                @endforeach
            </div>
        </div>
    @endif

    <p class="lc-owner-foot">
        Branch staff?
        <a href="{{ route('login') }}" class="font-semibold text-[#2563eb] hover:underline">Use branch login</a>
    </p>
</x-layouts.auth-owner-split>
