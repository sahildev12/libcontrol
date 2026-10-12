@props([
    /** @var 'branches'|'halls'|'seats'|'halls_and_seats' */
    'focus' => 'branches',
])

@php
    $pricingUrl = \App\Support\PlanLimitPresentation::libcontrolPricingUrl();
    $whatsappUrl = \App\Support\PlanLimitPresentation::upgradeWhatsAppUrl();

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
    class="mt-4 rounded-xl border border-amber-300 bg-gradient-to-r from-amber-50 to-orange-50 px-4 py-3.5 text-sm text-amber-950 shadow-sm"
    role="alert"
>
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="min-w-0">
            <p class="font-semibold text-amber-950">Plan limit reached</p>
            <p class="mt-1 text-amber-900/90">
                <span class="font-medium" x-text="planSnapshot?.limits?.plan_label"></span>
                —
                {{ $message }}
            </p>
        </div>
        <div class="flex shrink-0 flex-wrap gap-2">
            <a
                href="{{ $pricingUrl }}"
                target="_blank"
                rel="noopener noreferrer"
                class="inline-flex items-center justify-center rounded-lg bg-indigo-600 px-3.5 py-2 text-xs font-semibold text-white shadow-sm hover:bg-indigo-700"
            >
                View LibControl plans
            </a>
            @if ($whatsappUrl !== '')
                <a
                    href="{{ $whatsappUrl }}"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="inline-flex items-center justify-center rounded-lg border border-amber-400 bg-white px-3.5 py-2 text-xs font-semibold text-amber-950 hover:bg-amber-50"
                >
                    WhatsApp Katsindia
                </a>
            @endif
        </div>
    </div>
</div>
