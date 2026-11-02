<x-guest-layout
    portal="auth"
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
        ]))"
        x-on:submit="submitting = true"
        data-trial-signup
    >
        @csrf

        <div style="position: absolute; left: -9999px; width: 1px; height: 1px; overflow: hidden;" aria-hidden="true">
            <label for="website">Website</label>
            <input id="website" type="text" name="website" tabindex="-1" autocomplete="off">
        </div>

        <div>
            <x-input-label for="library_name" value="Library name" />
            <x-text-input id="library_name" class="mt-1 block w-full" type="text" name="library_name" x-model="libraryName" x-on:input="suggest()" required autofocus maxlength="120" placeholder="Sunrise Study Library" />
            <x-input-error :messages="$errors->get('library_name')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="subdomain" value="Your library address" />
            <div class="mt-1 flex rounded-md shadow-sm">
                <input
                    id="subdomain"
                    name="subdomain"
                    type="text"
                    x-model="subdomain"
                    x-on:input="edited = true; queueCheck()"
                    required
                    maxlength="30"
                    autocomplete="off"
                    spellcheck="false"
                    class="block w-full min-w-0 rounded-l-md border-gray-300 lowercase focus:border-indigo-500 focus:ring-indigo-500"
                    placeholder="sunrise"
                >
                <span class="inline-flex shrink-0 items-center rounded-r-md border border-l-0 border-gray-300 bg-gray-50 px-2 text-xs text-gray-500 sm:px-3 sm:text-sm">.{{ $baseDomain }}</span>
            </div>
            <p class="mt-1.5 text-xs" x-cloak x-show="message" :class="available ? 'text-emerald-600' : 'text-rose-600'" x-text="message" data-subdomain-message></p>
            <x-input-error :messages="$errors->get('subdomain')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="owner_name" value="Your name" />
            <x-text-input id="owner_name" class="mt-1 block w-full" type="text" name="owner_name" :value="old('owner_name')" required maxlength="120" autocomplete="name" />
            <x-input-error :messages="$errors->get('owner_name')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="email" value="Email" />
            <x-text-input id="email" class="mt-1 block w-full" type="email" name="email" :value="old('email')" required maxlength="190" autocomplete="email" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="password" value="Choose a password" />
            <x-password-input id="password" name="password" autocomplete="new-password" minlength="8" />
            <p class="mt-1 text-xs text-gray-500">At least 8 characters. You will use this to log in as the library admin.</p>
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="mt-6">
            <x-primary-button class="w-full justify-center" x-bind:disabled="submitting" x-bind:class="submitting && 'opacity-70'">
                <span x-show="! submitting">Create my library</span>
                <span x-show="submitting" x-cloak>Starting…</span>
            </x-primary-button>
        </div>

        <p class="mt-4 text-center text-xs text-gray-500">
            By continuing you agree to our
            <a href="/terms-and-conditions.html" target="_blank" class="font-semibold text-indigo-600 hover:text-indigo-800">terms</a>
            and
            <a href="/privacy-policy.html" target="_blank" class="font-semibold text-indigo-600 hover:text-indigo-800">privacy policy</a>.
        </p>
    </form>

    <script>
        function trialSignup(config) {
            return {
                libraryName: config.libraryName,
                subdomain: config.subdomain,
                edited: config.subdomain !== '',
                available: false,
                message: '',
                submitting: false,
                timer: null,
                init() {
                    if (this.subdomain) this.queueCheck();
                },
                suggest() {
                    if (this.edited) return;
                    this.subdomain = this.libraryName.toLowerCase()
                        .replace(/\b(library|study|centre|center|reading|room|the)\b/g, ' ')
                        .replace(/[^a-z0-9]+/g, '-')
                        .replace(/^-+|-+$/g, '')
                        .replace(/^[^a-z]+/, '')
                        .slice(0, 30)
                        .replace(/-+$/, '');
                    this.queueCheck();
                },
                queueCheck() {
                    clearTimeout(this.timer);
                    this.message = '';
                    if (this.subdomain.length < 3) return;
                    this.timer = setTimeout(() => this.check(), 450);
                },
                async check() {
                    const value = this.subdomain;
                    try {
                        const response = await fetch(config.checkUrl + '?subdomain=' + encodeURIComponent(value), { headers: { Accept: 'application/json' } });
                        if (! response.ok) return;
                        const data = await response.json();
                        if (value !== this.subdomain) return;
                        this.available = data.available;
                        this.message = data.message;
                    } catch (e) {}
                },
            };
        }
    </script>
</x-guest-layout>
