@props([
    /** @var 'branches'|'halls'|'seats'|'halls_and_seats' */
    'focus' => 'branches',
])

@php
    $pricingUrl = \App\Support\PlanLimitPresentation::libcontrolPricingUrl();
    $canPayOnline = ! config('libcontrol.license_server.enabled') && (auth()->user()?->isClientAdmin() ?? false);

    $showExpr = match ($focus) {
        'branches' => 'planSnapshot && planSnapshot.limits.max_branches != null && planSnapshot.remaining.branches === 0',
        'halls' => 'planSnapshot && planSnapshot.limits.max_halls != null && planSnapshot.remaining.halls === 0',
        'seats' => 'planSnapshot && planSnapshot.limits.max_seats != null && planSnapshot.remaining.seats === 0',
        'halls_and_seats' => 'planSnapshot && ((planSnapshot.limits.max_halls != null && planSnapshot.remaining.halls === 0) || (planSnapshot.limits.max_seats != null && planSnapshot.remaining.seats === 0))',
        default => 'false',
    };

    $message = match ($focus) {
        'branches' => 'You have used every branch included in your subscription. Upgrade to open another location.',
        'halls' => 'You have used every hall slot on your plan. Upgrade to add more study halls.',
        'seats' => 'You have used all seat capacity on your plan. Upgrade to add more seats or grow your library.',
        'halls_and_seats' => 'You are at your plan limit for halls or total seats. Upgrade to add more capacity.',
        default => 'You have reached a limit on your current plan. Upgrade to continue growing.',
    };
@endphp

<div
    x-show="{{ $showExpr }}"
    x-cloak
    class="mt-4 shadow-sm"
    style="background-color:#082a67;border-radius:16px;padding:14px 16px;"
    role="alert"
>
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex min-w-0 items-start gap-3">
            <span class="inline-flex shrink-0 items-center justify-center" style="width:36px;height:36px;border-radius:10px;background-color:#FFCC00;color:#082a67;">
                <svg style="width:18px;height:18px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.25" d="M3 17l6-6 4 4 8-8M14 7h7v7"/>
                </svg>
            </span>
            <div class="min-w-0">
                <p class="font-bold text-white" style="font-size:14px;margin:0;">
                    Plan limit reached
                    <span style="color:#FFCC00;" x-text="planSnapshot?.limits?.plan_label ? '· ' + planSnapshot.limits.plan_label : ''"></span>
                </p>
                <p style="margin:3px 0 0;font-size:12.5px;line-height:1.5;color:rgba(255,255,255,0.82);">{{ $message }}</p>
            </div>
        </div>
        <div class="flex shrink-0">
            @if ($canPayOnline)
                <a
                    href="{{ route('billing.index') }}"
                    class="inline-flex items-center font-bold shadow-sm transition hover:brightness-95 active:scale-[0.98]"
                    style="height:36px;border-radius:10px;padding:0 14px;gap:8px;background-color:#FFCC00;color:#082a67;font-size:12.5px;"
                >
                    Upgrade plan
                    <svg style="width:16px;height:16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.25" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/>
                    </svg>
                </a>
            @else
                <a
                    href="{{ $pricingUrl }}"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="inline-flex items-center font-bold shadow-sm transition hover:brightness-95"
                    style="height:36px;border-radius:10px;padding:0 14px;background-color:#FFCC00;color:#082a67;font-size:12.5px;"
                >
                    View LibControl plans
                </a>
            @endif
        </div>
    </div>
</div>
