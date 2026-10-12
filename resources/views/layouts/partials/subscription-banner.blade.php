@php
    $subscription = ! ($isDeveloperAdmin ?? false) && ! ($licenseServerEnabled ?? false)
        ? \App\Support\Subscription::status()
        : null;
    $subscriptionState = $subscription['state'] ?? \App\Support\Subscription::ACTIVE;
@endphp

@if ($subscription && $subscriptionState !== \App\Support\Subscription::ACTIVE)
    @php
        $ownerView = ($isClientAdmin ?? false);
        $paidUntilLabel = $subscription['paid_until']->format('d M Y');
        $daysLeft = (int) $subscription['days_left'];
        $dayWord = $daysLeft === 1 ? 'day' : 'days';
        [$badge, $title, $body] = match ($subscriptionState) {
            \App\Support\Subscription::LOCKED => [
                'Subscription ended',
                'LibControl is paused for your library',
                'Your plan ended on '.$paidUntilLabel.'. Renew below to unlock every page again. Your data is safe.',
            ],
            \App\Support\Subscription::GRACE => [
                'Payment due',
                'Your subscription ended on '.$paidUntilLabel,
                $daysLeft > 0
                    ? 'LibControl will be paused in '.$daysLeft.' '.$dayWord.' unless the plan is renewed.'
                    : 'LibControl will be paused today unless the plan is renewed.',
            ],
            default => [
                'Renewal due',
                'Your subscription ends on '.$paidUntilLabel,
                $daysLeft > 0
                    ? 'Renew in the next '.$daysLeft.' '.$dayWord.' to keep LibControl running without interruption.'
                    : 'Renew today to keep LibControl running without interruption.',
            ],
        };
        $urgent = $subscriptionState !== \App\Support\Subscription::ENDING_SOON;
        $onBilling = request()->routeIs('billing.*');
    @endphp
    <div
        class="relative overflow-hidden shadow-sm"
        style="background-color:#082a67;border-radius:16px;padding:16px 18px;{{ $urgent ? 'box-shadow:inset 4px 0 0 #f87171;' : '' }}"
        role="alert"
    >
        <div class="relative flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div class="flex min-w-0 items-start gap-3">
                <span class="inline-flex shrink-0 items-center justify-center" style="width:40px;height:40px;border-radius:12px;background-color:{{ $urgent ? '#f87171' : '#FFCC00' }};color:#082a67;">
                    <svg style="width:20px;height:20px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.25" d="M12 8v4l2.5 2.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </span>
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center" style="gap:8px;">
                        <p class="font-bold text-white" style="font-size:15px;line-height:1.3;margin:0;">{{ $title }}</p>
                        <span class="inline-flex items-center font-bold uppercase" style="background-color:{{ $urgent ? 'rgba(248,113,113,0.2)' : 'rgba(255,204,0,0.18)' }};color:{{ $urgent ? '#fecaca' : '#FFCC00' }};border-radius:999px;padding:3px 8px;font-size:10px;letter-spacing:0.05em;">{{ $badge }}</span>
                    </div>
                    <p style="margin:4px 0 0;font-size:13px;line-height:1.5;color:rgba(255,255,255,0.82);">
                        {{ $body }}
                        @unless ($ownerView)
                            Ask the library owner to renew it.
                        @endunless
                    </p>
                </div>
            </div>
            @if ($ownerView && ! $onBilling)
                <a
                    href="{{ route('billing.index') }}"
                    class="inline-flex shrink-0 items-center font-bold shadow-sm transition hover:brightness-95 active:scale-[0.98]"
                    style="height:38px;border-radius:10px;padding:0 14px;gap:8px;background-color:#FFCC00;color:#082a67;font-size:12.5px;width:fit-content;"
                >
                    Renew now
                    <svg style="width:16px;height:16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.25" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/>
                    </svg>
                </a>
            @endif
        </div>
    </div>
@endif
