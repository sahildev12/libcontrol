@php
    $capped = $planLimitCapped ?? [];
    $snapshot = $planSnapshot ?? null;
@endphp

@if ($snapshot && $capped !== [] && ! ($licenseServerEnabled ?? false) && ! ($isDeveloperAdmin ?? false))
    @php
        $summary = \App\Support\PlanLimitPresentation::summaryLine($snapshot, $capped);
        $whatsappUrl = \App\Support\PlanLimitPresentation::upgradeWhatsAppUrl();
        $planLabel = $snapshot['limits']['plan_label'] ?? 'Your plan';
        $ownerView = ($isClientAdmin ?? false);
    @endphp
    <div
        class="rounded-xl border border-amber-300 bg-gradient-to-r from-amber-50 to-orange-50 px-4 py-3.5 text-sm text-amber-950 shadow-sm"
        role="alert"
    >
        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            <div class="min-w-0">
                <p class="font-semibold text-amber-950">LibControl plan limit reached</p>
                <p class="mt-1 text-amber-900/90">
                    <span class="font-medium">{{ $planLabel }}</span>
                    — {{ $summary }}.
                    @if ($ownerView)
                        Upgrade your subscription to add more branches, halls, or seats.
                    @else
                        Your library has hit its subscription limit. Ask the library owner to upgrade the plan, or contact Katsindia.
                    @endif
                </p>
            </div>
            <div class="flex shrink-0 flex-wrap gap-2">
                @if ($ownerView)
                    <a
                        href="{{ route('billing.index') }}"
                        class="inline-flex items-center justify-center rounded-lg bg-indigo-600 px-3.5 py-2 text-xs font-semibold text-white shadow-sm hover:bg-indigo-700"
                    >
                        Upgrade plan
                    </a>
                @endif
                <a
                    href="{{ route('growth.index') }}"
                    class="inline-flex items-center justify-center rounded-lg border border-indigo-200 bg-white px-3.5 py-2 text-xs font-semibold text-indigo-800 hover:bg-indigo-50"
                >
                    Grow My Library
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
@endif
