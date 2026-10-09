@php
    $action = $growthScore ?? [];
    $actionScore = (int) ($action['score'] ?? 0);
    $actionMax = max(1, (int) ($action['max'] ?? 100));
    $actionPct = (int) ($combinedGrowth['action_pct'] ?? round(($actionScore / $actionMax) * 100));
    $items = $action['items'] ?? [];
    $completedCount = collect($items)->where('status', 'ok')->count();
    $totalItems = count($items);

    $recommendations = $combinedGrowth['recommendations'] ?? [];
    $recHasPackage = (bool) ($recommendations['has_package'] ?? false);
    $recUnlocked = $recommendations['unlocked'] ?? [];
    $recLocked = $recommendations['locked'] ?? [];
    $recCompleted = $recommendations['completed'] ?? [];
    $servicesRequestUrl = \Illuminate\Support\Facades\Route::has('growth.services.request') ? route('growth.services.request') : '';

    $growthHubUrl = \Illuminate\Support\Facades\Route::has('growth.index') ? route('growth.index') : '#';
    $illustrationUrl = asset('logo/growth/grow-banner-illustration.jpg');

@endphp

<div class="lc-grow-panel flex min-w-0 flex-col" style="gap:14px;">
    {{-- Banner --}}
    <section
        class="lc-grow-banner relative overflow-hidden text-white shadow-sm"
        style="background-color:#082a67;border-radius:16px;height:180px;"
    >
        <div class="pointer-events-none absolute inset-y-0 right-0 overflow-hidden" style="width:44%;">
            <img
                src="{{ $illustrationUrl }}"
                alt=""
                decoding="async"
                style="display:block;width:100%;height:100%;object-fit:contain;object-position:bottom right;"
            >
        </div>
        <div class="relative z-10 flex h-full flex-col justify-center" style="padding:20px 18px;padding-right:42%;max-width:100%;">
            <div class="flex flex-nowrap items-center" style="gap:8px;">
                <h2 class="truncate font-bold tracking-tight text-white" style="font-size:18px;line-height:1.2;margin:0;">Grow My Library</h2>
                <span class="inline-flex shrink-0 items-center font-bold uppercase" style="background-color:#FFCC00;color:#082a67;border-radius:999px;padding:3px 8px;font-size:9px;letter-spacing:0.05em;">New</span>
            </div>
            <p style="margin:8px 0 0;font-size:12.5px;line-height:1.45;color:rgba(255,255,255,0.9);">
                Track business health, complete growth actions, and fill more seats.
            </p>
            <a
                href="{{ $growthHubUrl }}#packages"
                class="lc-shine-btn relative z-20 inline-flex items-center font-bold shadow-sm transition hover:brightness-95 active:scale-[0.98]"
                style="margin-top:14px;height:38px;border-radius:10px;padding:0 14px;background-color:#FFCC00;color:#082a67;font-size:12px;width:fit-content;overflow:hidden;isolation:isolate;"
            >
                <span class="lc-shine-btn__glint" aria-hidden="true"></span>
                <span class="relative z-[1] inline-flex items-center" style="gap:8px;">
                    Explore Marketing Services
                    <svg style="width:16px;height:16px;flex-shrink:0;" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.25" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/>
                    </svg>
                </span>
            </a>
        </div>
    </section>

    {{-- Recommendations --}}
    <style>
        .lc-rec-card { border: 1px solid #E2E8F0; border-radius: 18px; box-shadow: 0 1px 3px rgba(8, 45, 112, 0.06); }
        .lc-rec-card__inner { padding: 16px 18px 18px; }
        .lc-rec-card__head { display: flex; align-items: flex-start; justify-content: space-between; gap: 12px; }
        .lc-rec-card__title { margin: 0; font-size: 15px; font-weight: 700; color: #082D70; line-height: 1.3; }
        .lc-rec-card__sub { margin: 4px 0 0; font-size: 12px; line-height: 1.45; color: #64748B; }
        .lc-rec-free-badge {
            display: inline-flex; align-items: center; gap: 5px; flex-shrink: 0;
            padding: 4px 10px; border-radius: 999px; background: #FFCC00; color: #082D70;
            font-size: 10px; font-weight: 700; letter-spacing: 0.04em; text-transform: uppercase;
        }
        .lc-rec-progress { margin: 0; font-size: 12px; font-weight: 600; color: #082D70; flex-shrink: 0; }
        .lc-rec-list { margin: 14px 0 0; padding: 0; list-style: none; border-top: 1px solid #E2E8F0; }
        .lc-rec-list > li { border-bottom: 1px solid #E2E8F0; }
        .lc-rec-list > li:last-child { border-bottom: none; }
        .lc-rec > summary { list-style: none; cursor: pointer; display: flex; align-items: center; gap: 10px; padding: 12px 0; }
        .lc-rec > summary::-webkit-details-marker { display: none; }
        .lc-rec > summary:hover { background: #F5F7FB; margin: 0 -6px; padding-left: 6px; padding-right: 6px; border-radius: 8px; }
        .lc-rec[open] .lc-rec-chevron { transform: rotate(180deg); }
        .lc-rec-chevron, .lc-rec-toggle-chevron { transition: transform 0.2s ease; flex-shrink: 0; }
        .lc-rec-toggle-chevron.is-open { transform: rotate(180deg); }
        .lc-rec-effort {
            display: inline-flex; align-items: center; gap: 4px; flex-shrink: 0;
            padding: 3px 8px; border-radius: 999px; font-size: 9.5px; font-weight: 700; letter-spacing: 0.04em;
        }
        .lc-rec-effort--easy { background: #DCFCE7; color: #16A34A; }
        .lc-rec-effort--medium { background: #FEF3C7; color: #92400E; }
        .lc-rec-effort--hard { background: #FEE2E2; color: #991B1B; }
        .lc-rec-row-title { min-width: 0; flex: 1; font-size: 13px; font-weight: 600; color: #082D70; line-height: 1.35; }
        .lc-rec-points {
            flex-shrink: 0; padding: 3px 8px; border-radius: 999px; background: #EFF6FF;
            font-size: 11px; font-weight: 700; color: #2563EB; font-variant-numeric: tabular-nums;
        }
        .lc-rec-body { padding: 0 0 14px 2px; }
        .lc-rec-steps {
            margin: 0 0 12px;
            padding-left: 1.15rem;
            list-style: disc;
            list-style-position: outside;
            font-size: 12px;
            line-height: 1.55;
            color: #334155;
        }
        .lc-rec-steps li { margin-bottom: 6px; padding-left: 2px; }
        .lc-rec-steps li:last-child { margin-bottom: 0; }
        .lc-rec-more-panel {
            display: flex; align-items: flex-start; gap: 12px; margin-top: 14px; padding: 12px 14px;
            border-radius: 12px; background: #EFF6FF; border: 1px solid #DBEAFE;
        }
        .lc-rec-more-panel__icon {
            display: flex; align-items: center; justify-content: center; flex-shrink: 0;
            width: 36px; height: 36px; border-radius: 10px; background: #DBEAFE; color: #082D70;
        }
        .lc-rec-more-panel__title { margin: 0; font-size: 13px; font-weight: 700; color: #082D70; }
        .lc-rec-more-panel__sub { margin: 2px 0 0; font-size: 12px; line-height: 1.4; color: #64748B; }
        .lc-rec-upgrade {
            display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 12px;
            margin-top: 14px; padding: 12px 14px; border-radius: 12px; background: #082D70;
        }
        .lc-rec-upgrade__left { display: flex; align-items: center; gap: 12px; min-width: 0; flex: 1; }
        .lc-rec-upgrade__crown {
            display: flex; align-items: center; justify-content: center; flex-shrink: 0;
            width: 40px; height: 40px; border-radius: 10px; background: rgba(37, 99, 235, 0.35); color: #FFCC00;
        }
        .lc-rec-upgrade__title { margin: 0; font-size: 13px; font-weight: 700; color: #fff; }
        .lc-rec-upgrade__sub { margin: 2px 0 0; font-size: 12px; color: rgba(255, 255, 255, 0.78); }
        .lc-rec-upgrade__btn {
            display: inline-flex; align-items: center; justify-content: center; gap: 6px; flex-shrink: 0;
            min-height: 38px; padding: 0 14px; border-radius: 10px; background: #FFCC00; color: #082D70;
            font-size: 12px; font-weight: 700; text-decoration: none; transition: filter 0.15s ease;
        }
        .lc-rec-upgrade__btn:hover { filter: brightness(0.97); }
        .lc-rec-hire-pro {
            display: inline-flex; align-items: center; gap: 6px; min-height: 36px; padding: 0 12px;
            border-radius: 8px; border: 1px dashed #EAB308; background: #FFFBEB; color: #92400E;
            font-size: 12px; font-weight: 600; text-decoration: none;
        }
        .lc-rec-hire-pro:hover { background: #FEF3C7; }
        .lc-rec-hire-btn {
            min-height: 36px; padding: 0 14px; border-radius: 8px; background: #FFCC00; color: #082D70;
            font-size: 12px; font-weight: 700; border: none; cursor: pointer;
        }
        .lc-rec-hire-btn:disabled { opacity: 0.65; cursor: wait; }
        @media (max-width: 480px) {
            .lc-rec-upgrade { flex-direction: column; align-items: stretch; }
            .lc-rec-upgrade__btn { width: 100%; }
        }
    </style>
    <section
        class="lc-rec-card overflow-hidden bg-white"
        x-data="{
            showCompleted: false,
            busy: null,
            async hireExpert(service, key) {
                this.busy = key;
                try {
                    const res = await fetch(@js($servicesRequestUrl), {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': @js(csrf_token()),
                        },
                        body: JSON.stringify({ item_key: service, source: 'recommendation' }),
                    });
                    const data = await res.json().catch(() => ({}));
                    if (! res.ok) {
                        if (data.packages_url) { window.location.href = data.packages_url; return; }
                        throw new Error(data.message || 'Could not send request');
                    }
                    window.dispatchEvent(new CustomEvent('show-toast', { detail: { message: data.message || 'Request sent to Phenomit.', type: 'success' } }));
                } catch (e) {
                    window.dispatchEvent(new CustomEvent('show-toast', { detail: { message: e.message || 'Could not send request', type: 'error' } }));
                } finally {
                    this.busy = null;
                }
            },
        }"
    >
        <div class="lc-rec-card__inner">
            <div class="lc-rec-card__head">
                <div class="min-w-0">
                    <h3 class="lc-rec-card__title">Recommendations</h3>
                    <p class="lc-rec-card__sub">Complete these to raise your Growth Score.</p>
                </div>
                @if (! $recHasPackage)

                @else
                    <p class="lc-rec-progress">{{ $completedCount }}/{{ $totalItems }} done · {{ $actionPct }}%</p>
                @endif
            </div>

            @if ($recUnlocked === [] && $recLocked === [])
                <p class="rounded-lg" style="margin:14px 0 0;padding:10px 12px;font-size:12px;background:#F0FDF4;color:#166534;">All recommendations done. Great work!</p>
            @endif

            <ul class="lc-rec-list">
                @foreach ($recUnlocked as $rec)
                    @php
                        $effort = (string) ($rec['effort'] ?? 'medium');
                        $effortClass = match ($effort) {
                            'easy' => 'lc-rec-effort--easy',
                            'hard' => 'lc-rec-effort--hard',
                            default => 'lc-rec-effort--medium',
                        };
                    @endphp
                    <li>
                        <details class="lc-rec">
                            <summary>
                                <span class="lc-rec-effort {{ $effortClass }}">
                                    @if ($effort === 'easy')
                                        <svg width="10" height="10" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M13 2L3 14h8l-1 8 10-12h-8l1-8z"/></svg>
                                    @endif
                                    {{ ucfirst($effort) }}
                                </span>
                                <span class="lc-rec-row-title">{{ $rec['title'] }}</span>
                                <span class="lc-rec-points">+{{ $rec['gain'] }} pts</span>
                                <svg class="lc-rec-chevron" width="16" height="16" style="color:#94A3B8;" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </summary>
                            <div class="lc-rec-body">
                                @if ($rec['why'] !== '')
                                    <p style="margin:0 0 8px;font-size:12px;line-height:1.45;color:#64748B;">{{ $rec['why'] }}</p>
                                @endif
                                @if ($rec['diy_steps'] !== [])
                                    <ul class="lc-rec-steps">
                                        @foreach ($rec['diy_steps'] as $step)
                                            <li>{{ $step }}</li>
                                        @endforeach
                                    </ul>
                                @endif
                                @if (($recHasPackage && ! empty($rec['expert_service'])) || (! $recHasPackage && ! empty($rec['expert_available'])))
                                    <div class="flex flex-wrap" style="gap:8px;">
                                        @if ($recHasPackage && ! empty($rec['expert_service']))
                                            <button
                                                type="button"
                                                class="lc-rec-hire-btn relative z-[2]"
                                                :disabled="busy === @js($rec['key'])"
                                                @click.stop="hireExpert(@js($rec['expert_service']), @js($rec['key']))"
                                            >
                                                <span x-show="busy !== @js($rec['key'])">Hire an expert</span>
                                                <span x-show="busy === @js($rec['key'])" x-cloak>Sending…</span>
                                            </button>
                                        @elseif (! $recHasPackage && ! empty($rec['expert_available']))
                                            <a href="{{ $growthHubUrl }}#packages" class="lc-rec-hire-pro">
                                                <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16.5 10.5V7.5a4.5 4.5 0 10-9 0v3m-.75 0h10.5a1.5 1.5 0 011.5 1.5v6a1.5 1.5 0 01-1.5 1.5H6.75a1.5 1.5 0 01-1.5-1.5v-6a1.5 1.5 0 011.5-1.5z"/></svg>
                                                Hire an expert (Pro)
                                            </a>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </details>
                    </li>
                @endforeach
            </ul>

            @if ($recLocked !== [] && ! $recHasPackage)
                <div class="lc-rec-more-panel" role="region" aria-label="Pro recommendations">
                    <span class="lc-rec-more-panel__icon" aria-hidden="true">
                        <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16.5 10.5V7.5a4.5 4.5 0 10-9 0v3m-.75 0h10.5a1.5 1.5 0 011.5 1.5v6a1.5 1.5 0 01-1.5 1.5H6.75a1.5 1.5 0 01-1.5-1.5v-6a1.5 1.5 0 011.5-1.5z"/></svg>
                    </span>
                    <div class="min-w-0">
                        <p class="lc-rec-more-panel__title">More recommendations</p>
                        <p class="lc-rec-more-panel__sub">Unlock additional ideas with Pro.</p>
                    </div>
                </div>

                <div class="lc-rec-upgrade">
                    <div class="lc-rec-upgrade__left">
                        <span class="lc-rec-upgrade__crown" aria-hidden="true">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M5 16L3 5l5.5 5L12 4l3.5 6L21 5l-2 11H5zm14 3H5v-2h14v2z"/></svg>
                        </span>
                        <div>
                            <p class="lc-rec-upgrade__title">Unlock All Recommendations.</p>
                            <!-- <p class="lc-rec-upgrade__sub">Unlock all recommendations.</p> -->
                        </div>
                    </div>
                    <a href="{{ $growthHubUrl }}#packages" class="lc-rec-upgrade__btn">
                        Upgrade to Pro
                        <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.25" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg>
                    </a>
                </div>
            @endif

            @if ($recCompleted !== [])
                <div style="margin-top:14px;border-top:1px solid #E2E8F0;padding-top:10px;">
                    <button type="button" class="flex w-full items-center font-semibold" style="gap:6px;font-size:12px;color:#16A34A;" @click="showCompleted = ! showCompleted" :aria-expanded="showCompleted.toString()">
                        <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                        {{ count($recCompleted) }} completed
                        <svg class="lc-rec-toggle-chevron" :class="showCompleted && 'is-open'" style="margin-left:auto;color:#94A3B8;" width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <ul x-show="showCompleted" x-cloak style="margin:6px 0 0;padding:0;list-style:none;">
                        @foreach ($recCompleted as $done)
                            <li class="flex items-center" style="gap:8px;padding:5px 2px;font-size:12px;color:#475569;">
                                <span class="inline-flex shrink-0 items-center justify-center rounded-full text-white" style="width:16px;height:16px;background:#22C55E;">
                                    <svg width="9" height="9" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                </span>
                                <span>{{ $done['title'] }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    </section>
</div>
