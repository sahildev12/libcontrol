<x-admin-layout>
    @php
        $recHasPackage = (bool) ($recommendations['has_package'] ?? false);
        $recUnlocked = $recommendations['unlocked'] ?? [];
        $profile = $score['profile'];
        $settingsWebsiteUrl = \Illuminate\Support\Facades\Route::has('settings.index')
            ? route('settings.index', ['tab' => 'website'])
            : '#visibility-form';

        $recAction = static function (string $key) use ($settingsWebsiteUrl): array {
            return match ($key) {
                'website' => ['label' => 'Setup Website', 'href' => $settingsWebsiteUrl],
                'gbp' => ['label' => 'Open Guide', 'href' => '#visibility-form'],
                'referral' => ['label' => 'Open Guide', 'href' => '#referral-kit'],
                'reviews' => ['label' => 'Open Guide', 'href' => '#visibility-form'],
                'whatsapp' => ['label' => 'Open Promotion', 'href' => route('promotion.index')],
                'enquiries' => ['label' => 'Open Guide', 'href' => \Illuminate\Support\Facades\Route::has('enquiries.index') ? route('enquiries.index') : '#visibility-form'],
                default => ['label' => 'Open Guide', 'href' => '#visibility-form'],
            };
        };

        $hubActions = collect($actions)->only(['whatsapp', 'google_search', 'google_maps', 'social', 'reviews', 'referral'])->all();

        $hubActionIcon = static function (string $key): string {
            return match ($key) {
                'whatsapp' => '<svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>',
                'google_search' => '<svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>',
                'google_maps' => '<svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a2 2 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>',
                'social' => '<svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h6m-6 4h10M5 6a2 2 0 012-2h10a2 2 0 012 2v12a2 2 0 01-2 2H7a2 2 0 01-2-2V6z"/></svg>',
                'reviews' => '<svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>',
                'referral' => '<svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>',
                default => '<svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>',
            };
        };
    @endphp

    <style>
        .lc-growth { --lc-navy: #082D70; --lc-blue: #2563EB; --lc-yellow: #FFCC00; --lc-border: #E2E8F0; --lc-muted: #64748B; }
        .lc-growth-panel {
            border-radius: 14px;
            border: 1px solid #DBEAFE;
            background: linear-gradient(180deg, #EFF6FF 0%, #F8FAFC 100%);
            box-shadow: 0 1px 3px rgba(37, 99, 235, 0.08);
        }
        .lc-growth-panel--solid {
            background: #fff;
            border-color: var(--lc-border);
            box-shadow: 0 1px 3px rgba(8, 45, 112, 0.06);
        }
        .lc-growth-panel__pad { padding: 4px 10px; }
        .lc-growth-icon-box {
            width: 40px; height: 40px; border-radius: 10px; background: #DBEAFE; color: var(--lc-navy);
            display: flex; align-items: center; justify-content: center; flex-shrink: 0;
        }
        .lc-growth-card {
            background: #fff; border: 1px solid var(--lc-border); border-radius: 14px;
            box-shadow: 0 1px 3px rgba(8, 45, 112, 0.06);
        }
        .lc-growth-card__pad { padding: 18px 20px; }
        .lc-growth-kicker { font-size: 11px; font-weight: 700; letter-spacing: 0.06em; text-transform: uppercase; color: var(--lc-blue); }
        .lc-growth-title { margin: 0; font-size: 1.5rem; font-weight: 700; color: var(--lc-navy); line-height: 1.25; }
        .lc-growth-sub { margin: 6px 0 0; font-size: 13px; line-height: 1.5; color: var(--lc-muted); max-width: 42rem; }
        .lc-growth-btn-yellow {
            display: inline-flex; align-items: center; justify-content: center; gap: 8px;
            min-height: 40px; padding: 0 16px; border-radius: 10px; background: var(--lc-yellow); color: var(--lc-navy);
            font-size: 13px; font-weight: 700; text-decoration: none; white-space: nowrap;
        }
        .lc-growth-btn-yellow:hover { filter: brightness(0.97); }
        .lc-growth-btn-navy {
            display: inline-flex; align-items: center; justify-content: center; gap: 6px;
            min-height: 36px; padding: 0 14px; border-radius: 10px; background: var(--lc-navy); color: #fff;
            font-size: 12px; font-weight: 700; border: none; cursor: pointer;
        }
        .lc-growth-btn-outline {
            display: inline-flex; align-items: center; justify-content: center;
            min-height: 36px; padding: 0 12px; border-radius: 10px; border: 1px solid var(--lc-border);
            background: #fff; color: var(--lc-navy); font-size: 12px; font-weight: 600;
            text-decoration: none; cursor: pointer; transition: background 0.15s ease, border-color 0.15s ease;
        }
        .lc-growth-btn-outline:hover { background: #F8FAFC; border-color: #CBD5E1; }
        .lc-growth-btn-navy { text-decoration: none; }
        .lc-growth-page {
            max-width: 80rem;
            margin-left: auto;
            margin-right: auto;
        }
        .lc-theme main.lc-main:has(.lc-growth-page) {
            padding-top: 0;
        }
        .lc-growth-section-title { margin: 0; font-size: 16px; font-weight: 700; color: var(--lc-navy); }
        .lc-growth-section-sub { margin: 4px 0 0; font-size: 13px; color: var(--lc-muted); }
        .lc-growth-link {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 13px;
            font-weight: 600;
            color: var(--lc-blue);
            text-decoration: none;
        }
        .lc-growth-link:hover { text-decoration: underline; }
        .lc-growth-link svg { flex-shrink: 0; }
        .lc-growth-rec-list { margin: 0; padding: 0; list-style: none; }
        .lc-growth-rec-list > li { border-bottom: 1px solid var(--lc-border); }
        .lc-growth-rec-list > li:last-child { border-bottom: none; }
        .lc-growth-rec > summary {
            list-style: none; cursor: pointer;
            display: flex; align-items: center; gap: 10px; padding: 12px 4px;
        }
        .lc-growth-rec > summary::-webkit-details-marker { display: none; }
        .lc-growth-rec > summary:hover { background: rgb(255 255 255 / 0.65); margin: 0 -6px; padding-left: 10px; padding-right: 10px; border-radius: 10px; }
        .lc-growth-rec-title { margin: 0; flex: 1; min-width: 0; font-size: 13px; font-weight: 600; color: var(--lc-navy); line-height: 1.35; }
        .lc-growth-rec-chevron { color: #94A3B8; transition: transform 0.2s ease; flex-shrink: 0; }
        .lc-growth-rec[open] .lc-growth-rec-chevron { transform: rotate(180deg); }
        .lc-growth-rec-body { padding: 0 4px 14px 4px; }
        .lc-growth-rec-why { margin: 0 0 8px; font-size: 12px; line-height: 1.45; color: var(--lc-muted); }
        .lc-growth-rec-steps { margin: 0 0 12px; padding-left: 1.1rem; font-size: 12px; line-height: 1.5; color: #334155; }
        .lc-growth-rec-steps li { margin-bottom: 4px; }
        .lc-growth-rec-icon {
            width: 36px; height: 36px; border-radius: 10px; background: #DBEAFE; color: var(--lc-navy);
            display: flex; align-items: center; justify-content: center; flex-shrink: 0;
        }
        .lc-growth-effort {
            display: inline-flex; align-items: center; gap: 4px; padding: 3px 8px; border-radius: 999px;
            font-size: 9.5px; font-weight: 700; letter-spacing: 0.04em; background: #DCFCE7; color: #16A34A;
        }
        .lc-growth-effort--medium { background: #FEF3C7; color: #92400E; }
        .lc-growth-effort--hard { background: #FEE2E2; color: #991B1B; }
        .lc-growth-points { padding: 3px 8px; border-radius: 999px; background: #EFF6FF; color: var(--lc-blue); font-size: 11px; font-weight: 700; }
        .lc-growth-pro-card {
            border-radius: 14px; border: 1px solid #DBEAFE; background: linear-gradient(180deg, #EFF6FF 0%, #F8FAFC 100%);
            padding: 18px 18px; box-shadow: 0 1px 3px rgba(37, 99, 235, 0.08);
        }
        .lc-growth-rec-panel { min-height: 120px; flex: 1; min-width: 0; }
        .lc-growth-rec-grid { display: flex; flex-direction: column; gap: 1rem; margin-top: 1rem; }
        @media (min-width: 900px) {
            .lc-growth-rec-grid { flex-direction: row; align-items: flex-start; }
            .lc-growth-rec-grid .lc-growth-pro-card { width: 260px; flex-shrink: 0; position: sticky; top: 1.5rem; }
        }
        .lc-growth-action-card { padding: 16px; height: 100%; display: flex; flex-direction: column; gap: 10px; }
        .lc-growth-action-card h3 { margin: 0; font-size: 14px; font-weight: 700; color: var(--lc-navy); }
        .lc-growth-action-card p { margin: 0; flex: 1; font-size: 12px; line-height: 1.45; color: var(--lc-muted); }
        .lc-growth-pack { display: flex; flex-direction: column; height: 100%; padding: 18px; border-radius: 14px; }
        .lc-growth-pack--popular { border-color: var(--lc-yellow) !important; box-shadow: 0 0 0 1px rgba(255, 204, 0, 0.4), 0 4px 14px rgba(255, 204, 0, 0.15); }
        .lc-growth-wa-link { display: inline-flex; align-items: center; justify-content: center; gap: 6px; font-size: 12px; font-weight: 600; color: #15803d; text-decoration: none; }
        .lc-growth-wa-link:hover { text-decoration: underline; }
        .lc-growth-pack-badge { display: inline-block; margin-bottom: 8px; padding: 3px 8px; border-radius: 999px; background: var(--lc-yellow); color: var(--lc-navy); font-size: 10px; font-weight: 700; text-transform: uppercase; }
        .lc-growth-service { display: flex; flex-direction: column; gap: 8px; padding: 16px 18px; transition: border-color 0.15s ease, box-shadow 0.15s ease; }
        .lc-growth-service:hover { border-color: #BFDBFE; box-shadow: 0 4px 14px rgba(37, 99, 235, 0.08); }
        .lc-growth-service__name { margin: 0; font-size: 14px; font-weight: 700; color: var(--lc-navy); line-height: 1.3; }
        .lc-growth-service__badge { flex-shrink: 0; padding: 2px 8px; border-radius: 999px; background: #EFF6FF; color: var(--lc-blue); font-size: 10.5px; font-weight: 700; white-space: nowrap; }
        .lc-growth-service__desc { margin: 0; flex: 1; font-size: 12.5px; line-height: 1.5; color: var(--lc-muted); }
        .lc-growth-service__foot { display: flex; align-items: flex-end; justify-content: space-between; gap: 12px; padding-top: 10px; border-top: 1px dashed var(--lc-border); }
        .lc-growth-service__price { margin: 0; font-size: 14px; font-weight: 700; color: var(--lc-navy); }
        .lc-growth-field-label { display: block; font-size: 12px; font-weight: 600; color: #334155; }
        .lc-growth-input { margin-top: 4px; width: 100%; border-radius: 8px; border: 1px solid #CBD5E1; font-size: 13px; padding: 7px 10px; background: #fff; }
        .lc-growth-input:focus { border-color: var(--lc-blue); outline: none; box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15); }
        .lc-growth-input.is-invalid { border-color: #F87171; }
        .lc-growth-error { margin: 4px 0 0; font-size: 11.5px; color: #DC2626; }
        .lc-growth-hint { margin: 4px 0 0; font-size: 11.5px; color: var(--lc-muted); }
        .lc-growth-group { border-top: 1px solid #DBEAFE; padding-top: 14px; margin-top: 14px; }
        .lc-growth-group__title { margin: 0 0 8px; font-size: 11px; font-weight: 700; letter-spacing: 0.06em; text-transform: uppercase; color: var(--lc-blue); }
        .lc-growth-check { display: flex; align-items: center; gap: 8px; font-size: 13px; color: #1E293B; }
        .lc-growth-check input:disabled + span { color: #94A3B8; }
        .lc-growth-status { display: inline-flex; align-items: center; gap: 5px; padding: 3px 9px; border-radius: 999px; font-size: 11px; font-weight: 700; }
        .lc-growth-status--live { background: #DCFCE7; color: #15803D; }
        .lc-growth-status--paused { background: #F1F5F9; color: #475569; }
        .lc-growth-stat { border-radius: 10px; background: #fff; border: 1px solid #DBEAFE; padding: 10px 12px; }
        .lc-growth-stat__value { margin: 0; font-size: 20px; font-weight: 700; color: var(--lc-navy); line-height: 1.1; }
        .lc-growth-stat__label { margin: 2px 0 0; font-size: 11px; color: var(--lc-muted); }
        .lc-growth-steps { margin: 0; padding: 0; list-style: none; counter-reset: step; display: grid; gap: 8px; }
        .lc-growth-steps li { counter-increment: step; display: flex; gap: 8px; font-size: 12px; line-height: 1.45; color: #334155; }
        .lc-growth-steps li::before { content: counter(step); flex-shrink: 0; width: 18px; height: 18px; border-radius: 999px; background: var(--lc-navy); color: #fff; font-size: 10px; font-weight: 700; display: flex; align-items: center; justify-content: center; margin-top: 1px; }
        /* .lc-growth redefines --lc-blue; table bars must keep the admin theme blue. */
        .lc-theme .lc-main .lc-growth .lc-table-toolbar,
        .lc-theme .lc-main .lc-growth .border-b:has(> h2, > h3) { background-color: #243a8b !important; }
    </style>

    <div
        class="lc-growth lc-growth-page mt-0 space-y-8"
        x-data="growthHub({
            requestPackageUrl: @js(route('growth.packages.request')),
            requestServiceUrl: @js(route('growth.services.request')),
            csrf: @js(csrf_token()),
        })"
    >

        <header class="flex flex-wrap items-start justify-between gap-4">
            <div class="min-w-0">
                <h1 class="lc-growth-title">Get more enquiries, improve visibility and fill more seats.</h1>
                <p class="lc-growth-sub">Branch: {{ $branch->name }}</p>
            </div>
        </header>

        @if (session('status'))
            <div x-data="{ show: true }" x-init="setTimeout(() => show = false, 6000)" x-show="show" x-transition class="fixed bottom-6 right-6 z-50 flex max-w-sm items-start gap-3 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800 shadow-lg" role="status">
                <span class="flex-1">{{ session('status') }}</span>
                <button type="button" @click="show = false" class="text-green-700 hover:text-green-900" aria-label="Dismiss">&times;</button>
            </div>
        @endif

        @include('dashboard.partials.growth-score-cards', [
            'combinedGrowth' => $combinedGrowth,
            'businessPerformance' => $combinedGrowth['business_performance'] ?? [],
            'profileCompletionScore' => $profileCompletionScore,
        ])

        @if ($profile->active_package)
            <p class="text-xs font-semibold text-[#2563EB]">
                Active package: {{ ucfirst((string) $profile->active_package) }}
                @if ($profile->package_active_until)
                    · until {{ $profile->package_active_until->format('d M Y') }}
                @endif
            </p>
        @endif

        <section id="recommendations">
            <div class="flex flex-wrap items-end justify-between gap-3">
                <div>
                    <h2 class="lc-growth-section-title">Recommended next steps</h2>
                    <p class="lc-growth-section-sub">Complete these to raise your Growth Score.</p>
                </div>
                @if (! $recHasPackage && ($recommendations['locked'] ?? []) !== [])
                    <a href="#packages" class="lc-growth-link">
                        View all recommendations
                        <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.25" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg>
                    </a>
                @endif
            </div>

            <div class="lc-growth-rec-grid">
                <div class="lc-growth-panel lc-growth-panel__pad lc-growth-rec-panel">
                    @if ($recUnlocked === [])
                        <p class="text-sm text-[#64748B]">All recommendations complete. Great work!</p>
                    @else
                        <ul class="lc-growth-rec-list">
                            @foreach ($recUnlocked as $rec)
                                @php
                                    $effort = (string) ($rec['effort'] ?? 'medium');
                                    $action = $recAction($rec['key']);
                                @endphp
                                <li>
                                    <details class="lc-growth-rec">
                                        <summary>
                                            <span class="lc-growth-effort lc-growth-effort--{{ $effort }}">
                                                @if ($effort === 'easy')
                                                    <svg width="10" height="10" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M13 2L3 14h8l-1 8 10-12h-8l1-8z"/></svg>
                                                @endif
                                                {{ ucfirst($effort) }}
                                            </span>
                                            <span class="lc-growth-rec-title">{{ $rec['title'] }}</span>
                                            <span class="lc-growth-points">+{{ $rec['gain'] }} pts</span>
                                            <svg class="lc-growth-rec-chevron" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                        </summary>
                                        <div class="lc-growth-rec-body">
                                            @if (($rec['why'] ?? '') !== '')
                                                <p class="lc-growth-rec-why">{{ $rec['why'] }}</p>
                                            @endif
                                            @if ($rec['diy_steps'] !== [])
                                                <ul class="lc-growth-rec-steps">
                                                    @foreach ($rec['diy_steps'] as $step)
                                                        <li>{{ $step }}</li>
                                                    @endforeach
                                                </ul>
                                            @endif
                                            <a href="{{ $action['href'] }}" class="lc-growth-btn-navy">{{ $action['label'] }}</a>
                                        </div>
                                    </details>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>

                @if (! $recHasPackage && ($recommendations['locked'] ?? []) !== [])
                    <aside class="lc-growth-pro-card flex flex-col justify-between gap-5">
                        <div class="flex gap-3">
                            <span class="lc-growth-rec-icon" style="background:#DBEAFE;">
                                <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16.5 10.5V7.5a4.5 4.5 0 10-9 0v3m-.75 0h10.5a1.5 1.5 0 011.5 1.5v6a1.5 1.5 0 01-1.5 1.5H6.75a1.5 1.5 0 01-1.5-1.5v-6a1.5 1.5 0 011.5-1.5z"/></svg>
                            </span>
                            <div>
                                <p class="text-sm font-bold text-[#082D70]">More recommendations with Pro</p>
                                <p class="mt-1 text-xs leading-relaxed text-[#64748B]">Unlock additional growth ideas and earn more points.</p>
                            </div>
                        </div>
                        <a href="#packages" class="lc-growth-btn-navy w-full sm:w-auto">
                            Upgrade to Pro
                            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.25" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg>
                        </a>
                    </aside>
                @endif
            </div>
        </section>

        <section>
            <div class="flex flex-wrap items-end justify-between gap-3">
                <div>
                    <h2 class="lc-growth-section-title">Growth actions</h2>
                    <p class="lc-growth-section-sub">DIY tools to raise your score. PhenomIT can handle the rest for you.</p>
                </div>
                <a href="#individual-services" class="lc-growth-link">
                    View all actions
                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.25" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg>
                </a>
            </div>
            <div class="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                @foreach ($hubActions as $key => $action)
                    <article class="lc-growth-panel lc-growth-action-card">
                        <div class="flex gap-3">
                            <span class="lc-growth-icon-box">{!! $hubActionIcon($key) !!}</span>
                            <div class="min-w-0">
                                <h3>{{ $action['label'] }}</h3>
                                <p class="mt-1">{{ $action['blurb'] }}</p>
                            </div>
                        </div>
                        @if (! empty($action['service']))
                            <button type="button" class="lc-growth-btn-navy w-full" @click="requestService(@js($action['service']))">Hire an expert</button>
                        @endif
                    </article>
                @endforeach
            </div>
        </section>

        <section id="packages">
            <h2 class="lc-growth-section-title">Library Growth Marketing Packages</h2>
            <p class="lc-growth-section-sub">{{ $gstDisclaimer }} {{ $adSpendDisclaimer }}</p>
            <div class="mt-4 grid gap-4 lg:grid-cols-3">
                @foreach ($packages as $key => $package)
                    <article class="lc-growth-panel lc-growth-pack {{ $key === 'grow' ? 'lc-growth-pack--popular' : '' }}">
                        @if ($key === 'grow')
                            <span class="lc-growth-pack-badge">Most popular</span>
                        @endif
                        <h3 class="text-lg font-bold text-[#082D70]">{{ $package['name'] }}</h3>
                        <p class="mt-1 text-2xl font-bold text-[#082D70]">{{ $package['price_label'] }}</p>
                        <p class="mt-2 text-sm text-[#64748B]">{{ $package['tagline'] }}</p>
                        <ul class="mt-4 flex-1 space-y-2 text-sm text-[#334155]">
                            @foreach ($package['includes'] as $line)
                                <li class="flex gap-2"><span class="text-[#2563EB]">✓</span><span>{{ $line }}</span></li>
                            @endforeach
                        </ul>
                        @foreach ($package['notes'] ?? [] as $note)
                            <p class="mt-3 text-xs text-amber-700">{{ $note }}</p>
                        @endforeach
                        <div class="mt-5 flex flex-col gap-2">
                            <button type="button" class="lc-growth-btn-navy w-full" @click="requestPackage(@js($key), false)">Request {{ $package['name'] }}</button>
                            @if ($razorpayEnabled)
                                <button type="button" class="lc-growth-btn-outline w-full" @click="requestPackage(@js($key), true)">Request + Pay online</button>
                            @endif
                            <a :href="whatsappFor(@js($package['name']))" target="_blank" rel="noopener" class="lc-growth-wa-link">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/><path d="M12 0C5.373 0 0 5.373 0 12c0 2.11.547 4.091 1.505 5.82L0 24l6.335-1.662A11.93 11.93 0 0012 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 21.82a9.78 9.78 0 01-4.978-1.357l-.357-.212-3.76.987 1.004-3.66-.233-.374A9.77 9.77 0 012.18 12C2.18 6.57 6.57 2.18 12 2.18S21.82 6.57 21.82 12 17.43 21.82 12 21.82z"/></svg>
                                Talk on WhatsApp
                            </a>
                        </div>
                    </article>
                @endforeach
            </div>
        </section>

        <section id="individual-services">
            <h2 class="lc-growth-section-title">Individual services</h2>
            <p class="lc-growth-section-sub">Need just one thing? Request a single service — no package required.</p>
            <div class="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                @foreach ($services as $key => $service)
                    @php
                        $billing = match ($service['billing'] ?? '') {
                            'monthly' => 'Monthly',
                            'one_time' => 'One-time',
                            'campaign' => 'Per campaign',
                            'setup' => 'One-time setup',
                            default => null,
                        };
                    @endphp
                    <article class="lc-growth-card lc-growth-service">
                        <div class="flex items-start justify-between gap-3">
                            <h3 class="lc-growth-service__name">{{ $service['name'] }}</h3>
                            @if ($billing)
                                <span class="lc-growth-service__badge">{{ $billing }}</span>
                            @endif
                        </div>
                        <p class="lc-growth-service__desc">{{ $service['description'] }}</p>
                        <div class="lc-growth-service__foot">
                            <div class="min-w-0">
                                <p class="lc-growth-service__price">{{ $service['price_label'] }}</p>
                                @if (! empty($service['ad_spend']))
                                    <p class="text-[11px] text-amber-700">Ad budget billed separately</p>
                                @endif
                            </div>
                            <button type="button" class="lc-growth-btn-navy shrink-0" @click="requestService(@js($key))">Request</button>
                        </div>
                    </article>
                @endforeach
            </div>
        </section>

        @include('growth.partials.visibility-campaigns')

        @if ($orders->isNotEmpty())
            @php
                $requestStatuses = [
                    'new' => ['New', 'bg-sky-50 text-sky-700 ring-sky-200'],
                    'quoted' => ['Quoted', 'bg-amber-50 text-amber-700 ring-amber-200'],
                    'active' => ['Active', 'bg-green-50 text-green-700 ring-green-200'],
                    'paused' => ['Paused', 'bg-slate-100 text-slate-600 ring-slate-200'],
                    'completed' => ['Completed', 'bg-indigo-50 text-indigo-700 ring-indigo-200'],
                    'cancelled' => ['Cancelled', 'bg-red-50 text-red-700 ring-red-200'],
                ];
            @endphp
            <section id="growth-requests" x-data="growthRequestTable({ rows: @js($orders) })">
                <h2 class="lc-growth-section-title">Your growth requests</h2>
                <p class="lc-growth-section-sub">Track everything you have asked Phenomit for. Our team is notified by email for every request.</p>

                <div class="lc-growth-card mt-4 overflow-hidden">
                    <div class="lc-table-toolbar flex flex-wrap items-center justify-between gap-3 border-b border-gray-200 px-4 py-3">
                        <div class="flex flex-wrap items-center gap-1.5">
                            <button type="button" @click="statusFilter = 'all'" class="lc-toolbar-filter rounded-full border px-3 py-1 text-xs font-semibold" :class="statusFilter === 'all' ? 'is-active' : ''">
                                All <span class="opacity-70" x-text="rows.length"></span>
                            </button>
                            @foreach ($requestStatuses as $key => [$label])
                                <button type="button" x-show="statusCount(@js($key)) > 0" @click="statusFilter = @js($key)" class="lc-toolbar-filter rounded-full border px-3 py-1 text-xs font-semibold" :class="statusFilter === @js($key) ? 'is-active' : ''">
                                    {{ $label }} <span class="opacity-70" x-text="statusCount(@js($key))"></span>
                                </button>
                            @endforeach
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            <input type="search" x-model.debounce.250ms="search" placeholder="Search requests..." class="w-full min-w-[200px] rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/30 sm:w-60">
                            <label class="text-xs font-medium text-gray-600">Rows</label>
                            <x-admin.select wrapper-class="relative inline-flex" class="h-9 pl-3 pr-9" x-model.number="perPage">
                                <option value="5">5</option>
                                <option value="10">10</option>
                                <option value="25">25</option>
                            </x-admin.select>
                        </div>
                    </div>

                <div class="overflow-x-auto">
                    <table class="w-full min-w-[760px]">
                        <thead class="border-b border-gray-200 bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                            <tr>
                                <th class="px-4 py-3">Request</th>
                                <th class="px-4 py-3">Type</th>
                                <th class="px-4 py-3">Price</th>
                                <th class="px-4 py-3">Requested on</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 text-sm text-gray-700">
                            <template x-for="row in paginatedRows()" :key="row.id">
                                <tr class="hover:bg-indigo-50/40">
                                    <td class="px-4 py-3">
                                        <p class="font-semibold text-[#082D70]" x-text="row.item_name"></p>
                                        <p class="text-xs text-gray-500" x-text="row.requested_by ? `by ${row.requested_by}` : ''"></p>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="rounded-full px-2.5 py-1 text-[11px] font-semibold ring-1 ring-inset" :class="row.type_label === 'Package' ? 'bg-amber-50 text-amber-700 ring-amber-200' : 'bg-slate-50 text-slate-700 ring-slate-200'" x-text="row.type_label"></span>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 font-medium text-gray-900" x-text="row.price_label"></td>
                                    <td class="whitespace-nowrap px-4 py-3 text-gray-500" x-text="row.created_at"></td>
                                    <td class="px-4 py-3">
                                        <span class="rounded-full px-2.5 py-1 text-[11px] font-semibold ring-1 ring-inset" :class="@js(collect($requestStatuses)->map(fn ($s) => $s[1]))[row.status] || 'bg-gray-100 text-gray-600'" x-text="row.status_label"></span>
                                        <p x-show="row.team_note" x-cloak class="mt-1.5 max-w-xs whitespace-pre-line text-xs text-gray-600"><span class="font-semibold text-[#082D70]">Phenomit:</span> <span x-text="row.team_note"></span></p>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <div class="inline-flex flex-wrap justify-end gap-1.5">
                                            <a x-show="row.monthly_report_url" :href="row.monthly_report_url" target="_blank" rel="noopener" class="rounded-lg border border-indigo-200 bg-indigo-50 px-2.5 py-1.5 text-xs font-semibold text-indigo-700 hover:bg-indigo-100">Report</a>
                                            <a :href="whatsappFor(`${row.item_name} (follow-up)`)" target="_blank" rel="noopener" class="rounded-lg border border-emerald-200 bg-emerald-50 px-2.5 py-1.5 text-xs font-semibold text-emerald-700 hover:bg-emerald-100">Follow up</a>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                            <tr x-show="paginatedRows().length === 0">
                                <td colspan="6" class="px-4 py-10 text-center text-gray-500">No requests match this filter.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <x-admin.data-table-footer />
                </div>
            </section>
        @endif

    </div>

    <script>
        function growthHub(config) {
            return {
                busy: false,
                init() {
                    const flash = sessionStorage.getItem('growthToast');
                    if (flash) {
                        sessionStorage.removeItem('growthToast');
                        window.showToast(flash);
                    }
                },
                async requestPackage(key, withCheckout) {
                    await this.submit(config.requestPackageUrl, { item_key: key, with_checkout: withCheckout ? 1 : 0 });
                },
                async requestService(key) {
                    await this.submit(config.requestServiceUrl, { item_key: key });
                },
                whatsappFor(name) {
                    return 'https://web.whatsapp.com/send?phone=' + @js($whatsappPhone) + '&text=' + encodeURIComponent('Hi Phenomit, I want LibControl Growth: ' + name);
                },
                async submit(url, body) {
                    if (this.busy) return;
                    this.busy = true;
                    // Opened during the click so the browser doesn't block it as a popup.
                    const tab = window.open('about:blank', '_blank');
                    try {
                        const res = await fetch(url, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': config.csrf,
                            },
                            body: JSON.stringify(body),
                        });
                        const data = await res.json();
                        if (!res.ok) throw new Error(data.message || 'Request failed');

                        const target = data.checkout_url || data.whatsapp_url;
                        if (target && tab) {
                            tab.location.href = target;
                        } else if (target) {
                            window.open(target, '_blank');
                        } else {
                            tab?.close();
                        }

                        sessionStorage.setItem('growthToast', data.message || 'Request sent');
                        window.location.reload();
                    } catch (e) {
                        tab?.close();
                        window.showToast(e.message || 'Could not send request', 'error');
                        this.busy = false;
                    }
                },
            };
        }
    </script>
</x-admin-layout>
