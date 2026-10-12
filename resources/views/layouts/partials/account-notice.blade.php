@php
    use App\Support\Subscription;

    $notice = null;
    $ownerView = ($isClientAdmin ?? false);

    if (! ($isDeveloperAdmin ?? false) && ! ($licenseServerEnabled ?? false)) {
        $subscription = Subscription::status();
        $state = $subscription['state'];

        if ($state !== Subscription::ACTIVE) {
            $daysLeft = (int) $subscription['days_left'];
            $notice = [
                'urgent' => $state !== Subscription::ENDING_SOON,
                'dismissible' => false,
                'title' => $state === Subscription::ENDING_SOON
                    ? 'Your subscription ends on '.$subscription['paid_until']->format('d M Y')
                    : 'Your subscription has ended',
                'text' => match (true) {
                    ! $ownerView => 'Ask the library owner to renew it.',
                    $state === Subscription::LOCKED => 'Renew below to continue.',
                    $state === Subscription::GRACE => $daysLeft > 0
                        ? 'Renew within '.$daysLeft.' '.($daysLeft === 1 ? 'day' : 'days').' to keep using LibControl.'
                        : 'Renew today to keep using LibControl.',
                    default => null,
                },
                'button' => $ownerView && ! request()->routeIs('billing.*') ? 'Renew now' : null,
            ];
        } elseif (($planLimitCapped ?? []) !== [] && ($planSnapshot ?? null) && ! request()->routeIs('billing.*')) {
            $dismissedAt = (int) session('plan_limit_dismissed_at', 0);

            if ($dismissedAt === 0 || now()->timestamp - $dismissedAt >= 3 * 3600) {
                $notice = [
                    'urgent' => false,
                    'dismissible' => true,
                    'title' => 'Plan limit reached',
                    'text' => $ownerView ? 'Upgrade to add more branches, halls or seats.' : 'Ask the library owner to upgrade.',
                    'button' => $ownerView ? 'Upgrade plan' : null,
                ];
            }
        }
    }
@endphp

@if ($notice)
    <div
        x-data="{ open: true, close() { this.open = false; window.axios.post(@js(route('plan-limit.dismiss'))).catch(() => {}); } }"
        x-show="open"
        class="flex items-center gap-3 shadow-sm"
        style="background-color:#082a67;border-radius:14px;padding:12px 14px;{{ $notice['urgent'] ? 'box-shadow:inset 4px 0 0 #f87171;' : '' }}"
        role="alert"
        data-account-notice
    >
        <span class="inline-flex shrink-0 items-center justify-center" style="width:34px;height:34px;border-radius:10px;background-color:{{ $notice['urgent'] ? '#f87171' : '#FFCC00' }};color:#082a67;">
            <svg style="width:18px;height:18px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.25" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
            </svg>
        </span>
        <p class="min-w-0 flex-1" style="margin:0;font-size:13.5px;line-height:1.45;color:rgba(255,255,255,0.85);">
            <span class="font-bold text-white">{{ $notice['title'] }}.</span>
            @if ($notice['text'])
                {{ $notice['text'] }}
            @endif
        </p>
        @if ($notice['button'])
            <a
                href="{{ route('billing.index') }}"
                class="inline-flex shrink-0 items-center font-bold transition hover:brightness-95"
                style="height:34px;border-radius:9px;padding:0 12px;background-color:#FFCC00;color:#082a67;font-size:12.5px;"
            >{{ $notice['button'] }}</a>
        @endif
        @if ($notice['dismissible'])
            <button
                type="button"
                @click="close()"
                class="inline-flex shrink-0 items-center justify-center rounded-lg text-white/70 transition hover:bg-white/10 hover:text-white"
                style="width:30px;height:30px;"
                aria-label="Close"
            >
                <svg style="width:16px;height:16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.25" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        @endif
    </div>
@endif
