@php
    $socialProviders = $socialProviders ?? \App\Support\Tenancy\SocialLogin::providers();
    $social = $social ?? null;
@endphp
<x-layouts.auth-owner-split title="Start free trial" active-tab="signup">
    <p class="lc-owner-lead">
        Free {{ $trialDays }}-day Pro trial · No card · Ready in about a minute
    </p>

    <form
        method="POST"
        action="{{ route('trials.store') }}"
        class="lc-owner-form"
        x-data="trialSignup(@js([
            'libraryName' => old('library_name', ''),
            'subdomain' => old('subdomain', ''),
            'checkUrl' => route('trials.check'),
            'baseDomain' => $baseDomain,
        ]))"
        x-on:submit="submitting = true"
        data-trial-signup
    >
        @csrf

        @if (session('trial_social_error'))
            <div class="rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700" role="alert">{{ session('trial_social_error') }}</div>
        @endif

        @if ($social)
            <div class="flex items-center justify-between gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2.5 text-sm" data-social-account>
                <div class="min-w-0">
                    <p class="font-semibold text-emerald-800">Signed in with {{ \App\Support\Tenancy\SocialLogin::label($social['provider']) }}</p>
                    <p class="truncate text-emerald-700">{{ $social['email'] }}</p>
                </div>
                <button type="submit" form="trial-social-forget" class="shrink-0 text-xs font-semibold text-emerald-800 underline hover:text-emerald-950">Use another account</button>
            </div>
        @endif

        <div style="position: absolute; left: -9999px; width: 1px; height: 1px; overflow: hidden;" aria-hidden="true">
            <label for="website">Website</label>
            <input id="website" type="text" name="website" tabindex="-1" autocomplete="off">
        </div>

        <div class="lc-owner-field-block">
            <label for="library_name" class="lc-owner-label">Library name</label>
            <div class="lc-owner-field">
                <svg class="lc-owner-field__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                <input
                    id="library_name"
                    class="lc-owner-input"
                    type="text"
                    name="library_name"
                    x-model="libraryName"
                    x-on:input="suggest()"
                    required
                    autofocus
                    maxlength="120"
                    placeholder="Sunrise Study Library"
                >
            </div>
            <x-input-error :messages="$errors->get('library_name')" class="mt-2" />
        </div>

        <div class="lc-owner-field-block">
            <label for="subdomain" class="lc-owner-label">Your library address</label>
            <div class="lc-owner-address">
                <div class="relative min-w-0 flex-1">
                    <input
                        id="subdomain"
                        name="subdomain"
                        type="text"
                        x-model="subdomain"
                        x-on:input="edited = subdomain !== ''; queueCheck()"
                        required
                        maxlength="30"
                        autocomplete="off"
                        spellcheck="false"
                        class="lc-owner-address__input"
                        x-bind:class="state === 'available' ? 'text-emerald-700' : (state === 'taken' ? 'text-rose-700' : '')"
                        placeholder="sunrise"
                    >
                    <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3" aria-hidden="true">
                        <svg x-cloak x-show="state === 'checking'" class="h-4 w-4 animate-spin text-gray-400" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="3" class="opacity-25"/><path d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg>
                        <svg x-cloak x-show="state === 'available'" class="h-4 w-4 text-emerald-500" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.7 5.3a1 1 0 0 1 0 1.4l-8 8a1 1 0 0 1-1.4 0l-4-4a1 1 0 1 1 1.4-1.4L8 12.6l7.3-7.3a1 1 0 0 1 1.4 0z" clip-rule="evenodd"/></svg>
                        <svg x-cloak x-show="state === 'taken'" class="h-4 w-4 text-rose-500" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4.3 4.3a1 1 0 0 1 1.4 0L10 8.6l4.3-4.3a1 1 0 1 1 1.4 1.4L11.4 10l4.3 4.3a1 1 0 0 1-1.4 1.4L10 11.4l-4.3 4.3a1 1 0 0 1-1.4-1.4L8.6 10 4.3 5.7a1 1 0 0 1 0-1.4z" clip-rule="evenodd"/></svg>
                    </span>
                </div>
                <span class="lc-owner-address__suffix">.{{ $baseDomain }}</span>
            </div>
            <p class="mt-1.5 text-xs text-gray-500" x-cloak x-show="state === 'checking'">Checking availability…</p>
            <p class="mt-1.5 text-xs" x-cloak x-show="state === 'available' || state === 'taken'" :class="state === 'available' ? 'text-emerald-600' : 'text-rose-600'" data-subdomain-message>
                <span x-text="message"></span>
                <template x-if="state === 'taken' && suggestion">
                    <button type="button" class="ml-1 font-semibold text-indigo-600 underline hover:text-indigo-800" x-on:click="useSuggestion()">Use <span x-text="suggestion"></span></button>
                </template>
            </p>
            <x-input-error :messages="$errors->get('subdomain')" class="mt-2" />
        </div>

        @if ($social)
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
            <p class="text-xs text-slate-500">You'll sign in to your library with {{ \App\Support\Tenancy\SocialLogin::label($social['provider']) }}. You can also set a password later using "Forgot password" on your library's login page.</p>
        @else
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="lc-owner-field-block">
                    <label for="email" class="lc-owner-label">Email</label>
                    <div class="lc-owner-field">
                        <svg class="lc-owner-field__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        <input id="email" class="lc-owner-input" type="email" name="email" value="{{ old('email') }}" required maxlength="190" autocomplete="email" placeholder="you@library.com">
                    </div>
                    <x-input-error :messages="$errors->get('email')" class="mt-2" />
                </div>

                <div class="lc-owner-field-block">
                    <label for="password" class="lc-owner-label">Password</label>
                    <div class="lc-owner-field" x-data="{ show: false }">
                        <svg class="lc-owner-field__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                        <input
                            id="password"
                            class="lc-owner-input !pr-11"
                            :type="show ? 'text' : 'password'"
                            name="password"
                            required
                            minlength="8"
                            autocomplete="new-password"
                            placeholder="At least 8 characters"
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
            </div>
        @endif

        <button type="submit" class="lc-owner-btn" x-bind:disabled="submitting">
            <span x-show="! submitting">Create my library</span>
            <span x-show="submitting" x-cloak>Starting…</span>
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.4" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
        </button>

        <p class="lc-owner-foot" style="margin-top:0">
            By continuing you agree to our
            <a href="/terms-and-conditions.html" target="_blank" class="font-semibold text-[#2563eb] hover:underline">terms</a>
            and
            <a href="/privacy-policy.html" target="_blank" class="font-semibold text-[#2563eb] hover:underline">privacy policy</a>.
        </p>
    </form>

    @if (! $social && $socialProviders)
        <div class="lc-owner-social-block" data-library-social-login>
            <div class="lc-owner-social-divider">
                <span class="h-px flex-1 bg-slate-200"></span>
                Or continue with
                <span class="h-px flex-1 bg-slate-200"></span>
            </div>
            <div class="flex items-center justify-center gap-2.5">
                @foreach ($socialProviders as $provider => $label)
                    <a
                        href="{{ \App\Support\Tenancy\SocialLogin::redirectUrl($provider) }}"
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

    @if ($social)
        <form id="trial-social-forget" method="POST" action="{{ route('trials.social.forget') }}" class="hidden">@csrf</form>
    @endif

    <script>
        function trialSignup(config) {
            return {
                libraryName: config.libraryName,
                subdomain: config.subdomain,
                edited: config.subdomain !== '',
                state: 'idle',
                message: '',
                suggestion: null,
                submitting: false,
                timer: null,
                init() {
                    if (this.subdomain) this.queueCheck();
                },
                slug(value) {
                    return value.toLowerCase()
                        .replace(/[^a-z0-9]+/g, '-')
                        .replace(/^[^a-z]+/, '')
                        .slice(0, 30)
                        .replace(/-+$/, '');
                },
                suggest() {
                    if (this.edited) return;
                    const short = this.slug(this.libraryName.toLowerCase().replace(/\b(library|study|centre|center|reading|room|the)\b/g, ' '));
                    this.subdomain = short.length >= 3 ? short : this.slug(this.libraryName);
                    this.queueCheck();
                },
                useSuggestion() {
                    this.subdomain = this.suggestion;
                    this.queueCheck();
                },
                queueCheck() {
                    clearTimeout(this.timer);
                    this.suggestion = null;
                    if (this.subdomain.length < 3) {
                        this.state = 'idle';
                        return;
                    }
                    this.state = 'checking';
                    this.timer = setTimeout(() => this.check(), 400);
                },
                async check() {
                    const value = this.subdomain;
                    try {
                        const response = await fetch(config.checkUrl + '?subdomain=' + encodeURIComponent(value), { headers: { Accept: 'application/json' } });
                        if (! response.ok) throw new Error();
                        const data = await response.json();
                        if (value !== this.subdomain) return;

                        if (! data.available && data.suggestion && ! this.edited) {
                            this.subdomain = data.suggestion;
                            this.state = 'available';
                            this.message = value + ' is taken, so we picked ' + data.suggestion + '.' + config.baseDomain + ' for you.';
                            return;
                        }

                        this.state = data.available ? 'available' : 'taken';
                        this.message = data.message;
                        this.suggestion = data.available ? null : data.suggestion;
                    } catch (e) {
                        if (value === this.subdomain) this.state = 'idle';
                    }
                },
            };
        }
    </script>
</x-layouts.auth-owner-split>
