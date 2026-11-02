<x-guest-layout
    portal="auth"
    title="Building {{ $tenant->client_name }}"
    subtitle="{{ $tenant->host() }}"
>
    <div
        x-data="trialBuilding(@js([
            'statusUrl' => route('trials.status', $token),
            'startUrl' => route('trials.start', $token),
            'csrf' => csrf_token(),
            'steps' => $steps,
        ]))"
        data-trial-building
    >
        <div class="flex items-center gap-4">
            <div class="relative flex size-14 shrink-0 items-center justify-center">
                <template x-if="status !== 'ready' && status !== 'failed'">
                    <span class="absolute inset-0 animate-spin rounded-full border-4 border-indigo-100 border-t-indigo-600"></span>
                </template>
                <template x-if="status === 'ready'">
                    <span class="flex size-14 items-center justify-center rounded-full bg-emerald-100 text-emerald-600">
                        <svg class="size-7" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    </span>
                </template>
                <template x-if="status === 'failed'">
                    <span class="flex size-14 items-center justify-center rounded-full bg-rose-100 text-rose-600">
                        <svg class="size-7" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </span>
                </template>
            </div>
            <div class="min-w-0">
                <p class="text-base font-semibold text-gray-900" x-text="headline" data-building-headline>Your software is being built</p>
                <p class="text-sm text-gray-500" x-text="subline">Please keep this page open. It usually takes about a minute.</p>
            </div>
        </div>

        <div class="mt-5 h-2 overflow-hidden rounded-full bg-gray-100">
            <div class="h-full rounded-full transition-all duration-700" :class="status === 'failed' ? 'bg-rose-500' : 'bg-indigo-600'" :style="`width: ${progress}%`"></div>
        </div>

        <ol class="mt-5 space-y-3">
            <template x-for="(label, key) in steps" :key="key">
                <li class="flex items-center gap-3 text-sm">
                    <span class="flex size-6 shrink-0 items-center justify-center rounded-full"
                          :class="{
                              'bg-emerald-500 text-white': state[key] === 'done',
                              'bg-indigo-100 text-indigo-600': state[key] === 'active',
                              'bg-rose-500 text-white': state[key] === 'failed',
                              'bg-gray-100 text-gray-300': state[key] === 'pending',
                          }">
                        <svg x-show="state[key] === 'done'" class="size-3.5" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        <span x-show="state[key] === 'active'" class="size-2 animate-pulse rounded-full bg-indigo-600"></span>
                        <span x-show="state[key] === 'failed'" class="text-xs font-bold">!</span>
                        <span x-show="state[key] === 'pending'" class="size-1.5 rounded-full bg-gray-300"></span>
                    </span>
                    <span :class="state[key] === 'pending' ? 'text-gray-400' : 'text-gray-800'" x-text="label"></span>
                </li>
            </template>
        </ol>

        <p class="mt-5 text-center text-xs text-gray-400" x-show="status !== 'ready' && status !== 'failed'" x-text="`${elapsed}s elapsed`"></p>

        <div x-cloak x-show="status === 'failed'" class="mt-5 rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800">
            <p x-text="message"></p>
            <button type="button" x-on:click="retry()" class="mt-3 rounded-lg bg-rose-600 px-4 py-2 text-xs font-semibold text-white hover:bg-rose-700">Try again</button>
        </div>

        <div x-cloak x-show="status === 'ready' && redirect" class="mt-5 text-center">
            <a :href="redirect" class="text-sm font-semibold text-indigo-600 hover:text-indigo-800">Not redirected? Open your library</a>
        </div>
    </div>

    <script>
        function trialBuilding(config) {
            const keys = Object.keys(config.steps);

            return {
                steps: config.steps,
                state: Object.fromEntries(keys.map((key, index) => [key, index === 0 ? 'active' : 'pending'])),
                status: 'pending',
                message: '',
                redirect: null,
                elapsed: 0,
                retriedAt: 0,
                timer: null,
                clock: null,
                get progress() {
                    if (this.status === 'ready') return 100;
                    const done = keys.filter((key) => this.state[key] === 'done').length;
                    const active = keys.some((key) => this.state[key] === 'active') ? 0.5 : 0;
                    return Math.max(4, Math.round(((done + active) / keys.length) * 100));
                },
                get headline() {
                    if (this.status === 'ready') return 'Your library is ready!';
                    if (this.status === 'failed') return 'We hit a problem';
                    return 'Your software is being built';
                },
                get subline() {
                    if (this.status === 'ready') return 'Taking you to your admin dashboard…';
                    if (this.status === 'failed') return 'Nothing was charged and no data was lost.';
                    return 'Please keep this page open. It usually takes about a minute.';
                },
                init() {
                    this.clock = setInterval(() => this.elapsed++, 1000);
                    this.start();
                    this.poll();
                },
                start() {
                    fetch(config.startUrl, {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': config.csrf, Accept: 'application/json' },
                    }).catch(() => {});
                },
                retry() {
                    this.status = 'pending';
                    this.retriedAt = Date.now();
                    this.elapsed = 0;
                    clearInterval(this.clock);
                    this.clock = setInterval(() => this.elapsed++, 1000);
                    this.state = Object.fromEntries(keys.map((key, index) => [key, index === 0 ? 'active' : 'pending']));
                    this.start();
                    clearTimeout(this.timer);
                    this.timer = setTimeout(() => this.poll(), 1500);
                },
                async poll() {
                    try {
                        const response = await fetch(config.statusUrl, { headers: { Accept: 'application/json' } });
                        const data = response.ok ? await response.json() : null;
                        const staleFailure = data?.status === 'failed' && Date.now() - this.retriedAt < 6000;
                        if (data && ! staleFailure) {
                            this.state = data.steps;
                            this.status = data.status;
                            this.message = data.message || '';
                            if (data.status === 'ready') {
                                clearInterval(this.clock);
                                this.redirect = data.redirect;
                                setTimeout(() => { window.location.href = data.redirect; }, 1200);
                                return;
                            }
                            if (data.status === 'failed') {
                                clearInterval(this.clock);
                                return;
                            }
                        }
                    } catch (e) {}
                    this.timer = setTimeout(() => this.poll(), 2000);
                },
            };
        }
    </script>
</x-guest-layout>
