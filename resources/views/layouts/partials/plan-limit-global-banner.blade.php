@php
    $capped = $planLimitCapped ?? [];
    $snapshot = $planSnapshot ?? null;
@endphp

@if ($snapshot && $capped !== [] && ! ($licenseServerEnabled ?? false) && ! ($isDeveloperAdmin ?? false))
    @php
        $planLabel = $snapshot['limits']['plan_label'] ?? 'Your plan';
        $ownerView = ($isClientAdmin ?? false);
        $usageChips = collect($capped)->map(fn (string $dimension) => [
            'label' => ucfirst($dimension),
            'value' => ($snapshot['usage'][$dimension] ?? 0).'/'.($snapshot['limits']['max_'.$dimension] ?? '∞'),
        ]);
    @endphp
    <div
        class="relative overflow-hidden shadow-sm"
        style="background-color:#082a67;border-radius:16px;padding:16px 18px;"
        role="alert"
    >
        <div class="pointer-events-none absolute" style="right:-40px;top:-60px;width:180px;height:180px;border-radius:999px;background:rgba(255,204,0,0.10);" aria-hidden="true"></div>
        <div class="relative flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div class="flex min-w-0 items-start gap-3">
                <span class="inline-flex shrink-0 items-center justify-center" style="width:40px;height:40px;border-radius:12px;background-color:#FFCC00;color:#082a67;">
                    <svg style="width:20px;height:20px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.25" d="M3 17l6-6 4 4 8-8M14 7h7v7"/>
                    </svg>
                </span>
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center" style="gap:8px;">
                        <p class="font-bold text-white" style="font-size:15px;line-height:1.3;margin:0;">LibControl plan limit reached</p>
                        <span class="inline-flex items-center font-bold uppercase" style="background-color:rgba(255,204,0,0.18);color:#FFCC00;border-radius:999px;padding:3px 8px;font-size:10px;letter-spacing:0.05em;">{{ $planLabel }}</span>
                    </div>
                    <p style="margin:4px 0 0;font-size:13px;line-height:1.5;color:rgba(255,255,255,0.82);">
                        @if ($ownerView)
                            Upgrade your subscription to add more branches, halls, or seats.
                        @else
                            Your library has hit its subscription limit. Ask the library owner to upgrade the plan, or contact Katsindia.
                        @endif
                    </p>
                    <div class="mt-2 flex flex-wrap" style="gap:6px;">
                        @foreach ($usageChips as $chip)
                            <span class="inline-flex items-center" style="gap:5px;border:1px solid rgba(255,255,255,0.18);background:rgba(255,255,255,0.06);border-radius:8px;padding:3px 8px;font-size:11.5px;color:rgba(255,255,255,0.85);">
                                {{ $chip['label'] }}
                                <strong style="color:#FFCC00;font-weight:700;">{{ $chip['value'] }}</strong>
                            </span>
                        @endforeach
                    </div>
                </div>
            </div>
            <div class="flex shrink-0 flex-wrap items-center" style="gap:8px;">
                @if ($ownerView)
                    <a
                        href="{{ route('billing.index') }}"
                        class="inline-flex items-center font-bold shadow-sm transition hover:brightness-95 active:scale-[0.98]"
                        style="height:38px;border-radius:10px;padding:0 14px;gap:8px;background-color:#FFCC00;color:#082a67;font-size:12.5px;"
                    >
                        Upgrade plan
                        <svg style="width:16px;height:16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.25" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/>
                        </svg>
                    </a>
                @endif
                <a
                    href="{{ route('growth.index') }}"
                    class="inline-flex items-center font-semibold text-white transition hover:bg-white/10"
                    style="height:38px;border-radius:10px;padding:0 14px;border:1px solid rgba(255,255,255,0.35);font-size:12.5px;"
                >
                    Grow My Library
                </a>
            </div>
        </div>
    </div>
@endif
