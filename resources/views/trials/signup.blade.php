<x-guest-layout
    portal="auth"
    compact
    title="Start your free {{ $trialDays }}-day trial"
    subtitle="Full Pro plan · No card needed · Ready in about a minute"
>
    <form
        method="POST"
        action="{{ route('trials.store') }}"
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
            <div class="mb-4 rounded-md border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700" role="alert">{{ session('trial_social_error') }}</div>
        @endif

        @if ($social)
            <div class="mb-5 flex items-center justify-between gap-3 rounded-md border border-emerald-200 bg-emerald-50 px-3 py-2.5 text-sm" data-social-account>
                <div class="min-w-0">
                    <p class="font-semibold text-emerald-800">Signed in with {{ \App\Support\Tenancy\SocialLogin::label($social['provider']) }}</p>
                    <p class="truncate text-emerald-700">{{ $social['email'] }}</p>
                </div>
                <button type="submit" form="trial-social-forget" class="shrink-0 text-xs font-semibold text-emerald-800 underline hover:text-emerald-950">Use another account</button>
            </div>
        @elseif ($socialProviders)
            <div class="grid gap-2 {{ count($socialProviders) > 1 ? 'sm:grid-cols-2' : '' }}">
                @foreach ($socialProviders as $provider => $label)
                    <a href="{{ \App\Support\Tenancy\SocialLogin::redirectUrl($provider) }}" class="flex w-full items-center justify-center gap-2.5 rounded-md border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50" data-social-provider="{{ $provider }}">
                        @include('trials.partials.social-icon', ['provider' => $provider])
                        <span>@if (count($socialProviders) > 1)<span class="sm:hidden">Continue with </span>@else Continue with @endif{{ $label }}</span>
                    </a>
                @endforeach
            </div>
            <div class="my-4 flex items-center gap-3 text-xs uppercase tracking-wide text-gray-400">
                <span class="h-px flex-1 bg-gray-200"></span>
                or sign up with email
                <span class="h-px flex-1 bg-gray-200"></span>
            </div>
        @endif

        <div style="position: absolute; left: -9999px; width: 1px; height: 1px; overflow: hidden;" aria-hidden="true">
            <label for="website">Website</label>
            <input id="website" type="text" name="website" tabindex="-1" autocomplete="off">
        </div>

        <div>
            <x-input-label for="library_name" value="Library name" />
            <x-text-input id="library_name" class="mt-1 block w-full" type="text" name="library_name" x-model="libraryName" x-on:input="suggest()" required autofocus maxlength="120" placeholder="Sunrise Study Library" />
            <x-input-error :messages="$errors->get('library_name')" class="mt-2" />
        </div>

        <div class="mt-3">
            <x-input-label for="subdomain" value="Your library address" />
            <div class="mt-1 flex rounded-md shadow-sm">
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
                        class="block w-full rounded-l-md border-gray-300 pr-9 lowercase focus:border-indigo-500 focus:ring-indigo-500"
                        x-bind:class="state === 'available' ? 'border-emerald-400' : (state === 'taken' ? 'border-rose-400' : '')"
                        placeholder="sunrise"
                    >
                    <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3" aria-hidden="true">
                        <svg x-cloak x-show="state === 'checking'" class="h-4 w-4 animate-spin text-gray-400" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="3" class="opacity-25"/><path d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg>
                        <svg x-cloak x-show="state === 'available'" class="h-4 w-4 text-emerald-500" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.7 5.3a1 1 0 0 1 0 1.4l-8 8a1 1 0 0 1-1.4 0l-4-4a1 1 0 1 1 1.4-1.4L8 12.6l7.3-7.3a1 1 0 0 1 1.4 0z" clip-rule="evenodd"/></svg>
                        <svg x-cloak x-show="state === 'taken'" class="h-4 w-4 text-rose-500" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4.3 4.3a1 1 0 0 1 1.4 0L10 8.6l4.3-4.3a1 1 0 1 1 1.4 1.4L11.4 10l4.3 4.3a1 1 0 0 1-1.4 1.4L10 11.4l-4.3 4.3a1 1 0 0 1-1.4-1.4L8.6 10 4.3 5.7a1 1 0 0 1 0-1.4z" clip-rule="evenodd"/></svg>
                    </span>
                </div>
                <span class="inline-flex shrink-0 items-center rounded-r-md border border-l-0 border-gray-300 bg-gray-50 px-2 text-xs text-gray-500 sm:px-3 sm:text-sm">.{{ $baseDomain }}</span>
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
            <p class="mt-3 text-xs text-gray-500">You'll sign in to your library with {{ \App\Support\Tenancy\SocialLogin::label($social['provider']) }}. You can also set a password later using "Forgot password" on your library's login page.</p>
        @else
            <div class="mt-3 grid gap-3 sm:grid-cols-2">
                <div>
                    <x-input-label for="email" value="Email" />
                    <x-text-input id="email" class="mt-1 block w-full" type="email" name="email" :value="old('email')" required maxlength="190" autocomplete="email" />
                    <x-input-error :messages="$errors->get('email')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="password" value="Choose a password" />
                    <x-password-input id="password" name="password" autocomplete="new-password" minlength="8" />
                    <x-input-error :messages="$errors->get('password')" class="mt-2" />
                </div>
            </div>
            <p class="mt-1.5 text-xs text-gray-500">Password: at least 8 characters. You'll use these to log in as admin.</p>
        @endif

        <div class="mt-5">
            <x-primary-button class="w-full justify-center" x-bind:disabled="submitting" x-bind:class="submitting && 'opacity-70'">
                <span x-show="! submitting">Create my library</span>
                <span x-show="submitting" x-cloak>Starting…</span>
            </x-primary-button>
        </div>

        <p class="mt-3 text-center text-xs text-gray-500">
            By continuing you agree to our
            <a href="/terms-and-conditions.html" target="_blank" class="font-semibold text-indigo-600 hover:text-indigo-800">terms</a>
            and
            <a href="/privacy-policy.html" target="_blank" class="font-semibold text-indigo-600 hover:text-indigo-800">privacy policy</a>.
        </p>
    </form>

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
</x-guest-layout>
