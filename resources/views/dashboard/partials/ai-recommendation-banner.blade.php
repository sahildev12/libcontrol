@php
    $recommendations = $combinedGrowth['recommendations'] ?? [];
    $hasPackage = (bool) ($recommendations['has_package'] ?? false);
    $freeLimit = max(1, (int) ($recommendations['free_limit'] ?? config('growth.recommendations.free_limit', 3)));
    $growthHubUrl = \Illuminate\Support\Facades\Route::has('growth.index') ? route('growth.index') : '#';
    $packagesUrl = $growthHubUrl !== '#' ? $growthHubUrl.'#packages' : '#';
    $recsUrl = $growthHubUrl !== '#' ? $growthHubUrl.'#recommendations' : '#';
    $servicesRequestUrl = \Illuminate\Support\Facades\Route::has('growth.services.request')
        ? route('growth.services.request')
        : '';

    $rawUnlocked = $recommendations['unlocked'] ?? [];
    $rawLocked = $recommendations['locked'] ?? [];
    $pendingTotal = count($rawUnlocked) + count($rawLocked);

    $unlocked = collect($rawUnlocked)
        ->take($freeLimit)
        ->map(function (array $item) use ($recsUrl, $packagesUrl, $hasPackage) {
            $effort = (string) ($item['effort'] ?? 'medium');
            $impact = match ($effort) {
                'hard' => ['label' => 'High Impact', 'class' => 'lc-ai-banner__badge--high'],
                'easy' => ['label' => 'Quick Win', 'class' => 'lc-ai-banner__badge--quick'],
                default => ['label' => 'Medium Impact', 'class' => 'lc-ai-banner__badge--medium'],
            };
            $expertService = $item['expert_service'] ?? null;
            $expertAvailable = (bool) ($item['expert_available'] ?? filled($expertService));

            return [
                'key' => (string) ($item['key'] ?? ''),
                'title' => (string) ($item['title'] ?? $item['label'] ?? 'Growth tip'),
                'why' => (string) ($item['why'] ?: ($item['detail'] ?? '')),
                'effort' => $effort,
                'impact_label' => $impact['label'],
                'impact_class' => $impact['class'],
                'gain' => (int) ($item['gain'] ?? 0),
                'cta_label' => 'View Details',
                'cta_url' => $recsUrl,
                'show_hire' => $expertAvailable,
                'hire_mode' => $hasPackage && filled($expertService) ? 'request' : 'packages',
                'expert_service' => $hasPackage ? $expertService : null,
                'hire_url' => $packagesUrl,
                'is_premium' => false,
            ];
        })
        ->filter(fn (array $item) => $item['key'] !== '')
        ->values();

    if (! $hasPackage) {
        $unlocked->push([
            'key' => 'upgrade_premium_more_recs',
            'title' => 'Upgrade to Premium to see more recommendations',
            'why' => 'Unlock the full set of AI growth tips tailored to your library’s occupancy, renewals, and enquiries.',
            'effort' => 'premium',
            'impact_label' => 'Premium',
            'impact_class' => 'lc-ai-banner__badge--premium',
            'gain' => 0,
            'cta_label' => 'View Details',
            'cta_url' => $packagesUrl,
            'show_hire' => false,
            'hire_mode' => 'packages',
            'expert_service' => null,
            'hire_url' => $packagesUrl,
            'is_premium' => true,
        ]);
    }

    $unlocked = $unlocked->all();
    $botUrl = asset('logo/growth/ai-assistant-bot.png').'?v=2';
    $botVideoMp4Url = asset('logo/growth/ai-assistant-bot-spin.mp4').'?v=8';
@endphp

@if (count($unlocked) > 0 || $pendingTotal === 0)
<style>
    .lc-ai-banner {
        --lc-ai-navy: #082D70;
        --lc-ai-blue: #2563EB;
        --lc-ai-yellow: #FFCC00;
        --lc-ai-bg: #0b3680;
        --lc-ai-ink: #0f274f;
        --lc-ai-muted: #5b6f8f;
        --lc-ai-ease: cubic-bezier(0.4, 0.05, 0.2, 1);
        --lc-ai-dur: 750ms;
        position: relative;
        display: flex;
        align-items: stretch;
        gap: 8px;
        overflow: hidden;
        border-radius: 18px;
        background: #0b3680;
        border: 1px solid #0b3680;
        min-height: 260px;
    }
    .lc-ai-banner__bot-wrap {
        position: relative;
        flex: 0 0 auto;
        width: 204px;
        align-self: stretch;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 12px 8px 12px 14px;
        z-index: 2;
        overflow: hidden;
        background: var(--lc-ai-bg);
    }
    .lc-ai-banner__bot-frame {
        position: relative;
        width: 184px;
        height: 196px;
        overflow: hidden;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 0;
        background: var(--lc-ai-bg);
    }
    .lc-ai-banner__bot {
        display: block;
        backface-visibility: hidden;
    }
    .lc-ai-banner__bot-video {
        width: 184px;
        height: auto;
        max-height: 196px;
        margin: 0;
        object-fit: contain;
        object-position: center center;
        pointer-events: none;
        user-select: none;
        mix-blend-mode: screen;
        filter: drop-shadow(0 6px 12px rgba(8, 45, 112, 0.2));
    }
    .lc-ai-banner__bot-fallback {
        display: none;
        width: 156px;
        height: auto;
        max-height: 176px;
        object-fit: contain;
        object-position: center bottom;
        mix-blend-mode: screen;
        filter: drop-shadow(0 6px 12px rgba(8, 45, 112, 0.2));
    }
    .lc-ai-banner__bot-fallback.is-spinning {
        animation: lc-ai-bot-spin 0.85s var(--lc-ai-ease);
    }
    @keyframes lc-ai-bot-spin {
        0% { transform: rotateY(0deg) translateY(0) scale(1); }
        35% { transform: rotateY(140deg) translateY(-10px) scale(1.08); }
        70% { transform: rotateY(280deg) translateY(4px) scale(1.02); }
        100% { transform: rotateY(360deg) translateY(0) scale(1); }
    }
    .lc-ai-banner__body {
        position: relative;
        flex: 1 1 auto;
        min-width: 0;
        padding: 18px 48px 18px 6px;
        display: flex;
        flex-direction: column;
        justify-content: center;
        gap: 12px;
        z-index: 1;
    }
    .lc-ai-banner__head {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 10px 16px;
        padding-right: 28px;
    }
    .lc-ai-banner__eyebrow {
        margin: 0 0 4px;
        font-size: 0.6875rem;
        font-weight: 800;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: var(--lc-ai-yellow);
    }
    .lc-ai-banner__heading {
        margin: 0;
        font-size: 1.05rem;
        font-weight: 800;
        letter-spacing: -0.015em;
        color: #fff;
        line-height: 1.25;
    }
    .lc-ai-banner__stack-wrap {
        position: relative;
        min-height: 198px;
        padding-bottom: 8px;
    }
    .lc-ai-banner__stack {
        position: relative;
        min-height: 198px;
    }
    .lc-ai-banner__layer {
        position: absolute;
        left: 0;
        right: 12px;
        top: 0;
        transition:
            transform var(--lc-ai-dur) var(--lc-ai-ease),
            opacity var(--lc-ai-dur) var(--lc-ai-ease),
            filter var(--lc-ai-dur) var(--lc-ai-ease);
        will-change: transform, opacity, filter;
        pointer-events: none;
    }
    .lc-ai-banner__stack.is-snap .lc-ai-banner__layer,
    .lc-ai-banner__stack.is-snap .lc-ai-banner__incoming {
        transition: none !important;
        animation: none !important;
    }
    .lc-ai-banner__layer.is-active { pointer-events: auto; }
    /* Resting stack */
    .lc-ai-banner__layer[data-depth="0"] {
        transform: translateY(0) scale(1);
        opacity: 1;
        filter: blur(0);
        z-index: 5;
    }
    .lc-ai-banner__layer[data-depth="1"] {
        transform: translateY(44px) scale(0.98);
        opacity: 0.8;
        filter: blur(1px);
        z-index: 4;
    }
    .lc-ai-banner__layer[data-depth="2"] {
        transform: translateY(80px) scale(0.96);
        opacity: 0.55;
        filter: blur(2px);
        z-index: 3;
    }
    .lc-ai-banner__layer[data-depth="3"] {
        transform: translateY(108px) scale(0.94);
        opacity: 0.34;
        filter: blur(2.8px);
        z-index: 2;
    }
    .lc-ai-banner__layer[data-depth="4"] {
        transform: translateY(130px) scale(0.92);
        opacity: 0.18;
        filter: blur(3.5px);
        z-index: 1;
    }
    /* Forward: active exits up, lower cards rise into focus */
    .lc-ai-banner__stack.is-advancing > [data-depth="0"] {
        transform: translateY(-52px) scale(0.985);
        opacity: 0;
        filter: blur(1px);
        z-index: 6;
        pointer-events: none;
    }
    .lc-ai-banner__stack.is-advancing > [data-depth="1"] {
        transform: translateY(0) scale(1);
        opacity: 1;
        filter: blur(0);
        z-index: 5;
    }
    .lc-ai-banner__stack.is-advancing > [data-depth="2"] {
        transform: translateY(44px) scale(0.98);
        opacity: 0.8;
        filter: blur(1px);
        z-index: 4;
    }
    .lc-ai-banner__stack.is-advancing > [data-depth="3"] {
        transform: translateY(80px) scale(0.96);
        opacity: 0.55;
        filter: blur(2px);
        z-index: 3;
    }
    .lc-ai-banner__stack.is-advancing > [data-depth="4"] {
        transform: translateY(108px) scale(0.94);
        opacity: 0.34;
        filter: blur(2.8px);
        z-index: 2;
    }
    /* Reverse: active sinks, previous rises from above */
    .lc-ai-banner__stack.is-reversing > [data-depth="0"] {
        transform: translateY(44px) scale(0.98);
        opacity: 0.8;
        filter: blur(1px);
        z-index: 4;
        pointer-events: none;
    }
    .lc-ai-banner__stack.is-reversing > [data-depth="1"] {
        transform: translateY(80px) scale(0.96);
        opacity: 0.55;
        filter: blur(2px);
        z-index: 3;
    }
    .lc-ai-banner__stack.is-reversing > [data-depth="2"] {
        transform: translateY(108px) scale(0.94);
        opacity: 0.34;
        filter: blur(2.8px);
        z-index: 2;
    }
    .lc-ai-banner__stack.is-reversing > [data-depth="3"] {
        transform: translateY(130px) scale(0.92);
        opacity: 0.18;
        filter: blur(3.5px);
        z-index: 1;
    }
    .lc-ai-banner__stack.is-reversing > [data-depth="4"] {
        transform: translateY(148px) scale(0.9);
        opacity: 0;
        filter: blur(4px);
        z-index: 0;
    }
    .lc-ai-banner__incoming {
        position: absolute;
        left: 0;
        right: 12px;
        top: 0;
        transform: translateY(-52px) scale(0.985);
        opacity: 0;
        filter: blur(1px);
        z-index: 7;
        pointer-events: none;
        transition:
            transform var(--lc-ai-dur) var(--lc-ai-ease),
            opacity var(--lc-ai-dur) var(--lc-ai-ease),
            filter var(--lc-ai-dur) var(--lc-ai-ease);
    }
    .lc-ai-banner__stack.is-reversing .lc-ai-banner__incoming {
        transform: translateY(0) scale(1);
        opacity: 1;
        filter: blur(0);
    }
    .lc-ai-banner__card {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 12px 14px;
        padding: 12px 14px;
        border-radius: 14px;
        background: #fff;
        border: 1px solid #c9daf3;
        box-shadow: 0 2px 8px rgba(8, 45, 112, 0.06);
    }
    .lc-ai-banner__layer:not(.is-active) .lc-ai-banner__card,
    .lc-ai-banner__incoming .lc-ai-banner__card {
        box-shadow: none;
    }
    .lc-ai-banner__stack:not(.is-advancing) .lc-ai-banner__layer:not(.is-active) .lc-ai-banner__why,
    .lc-ai-banner__stack:not(.is-advancing) .lc-ai-banner__layer:not(.is-active) .lc-ai-banner__actions,
    .lc-ai-banner__stack:not(.is-advancing) .lc-ai-banner__layer:not(.is-active) .lc-ai-banner__meta,
    .lc-ai-banner__stack.is-advancing > [data-depth="0"] .lc-ai-banner__why,
    .lc-ai-banner__stack.is-advancing > [data-depth="0"] .lc-ai-banner__actions,
    .lc-ai-banner__stack.is-advancing > [data-depth="0"] .lc-ai-banner__meta,
    .lc-ai-banner__stack.is-advancing > [data-depth="2"] .lc-ai-banner__why,
    .lc-ai-banner__stack.is-advancing > [data-depth="2"] .lc-ai-banner__actions,
    .lc-ai-banner__stack.is-advancing > [data-depth="2"] .lc-ai-banner__meta,
    .lc-ai-banner__stack.is-advancing > [data-depth="3"] .lc-ai-banner__why,
    .lc-ai-banner__stack.is-advancing > [data-depth="3"] .lc-ai-banner__actions,
    .lc-ai-banner__stack.is-advancing > [data-depth="3"] .lc-ai-banner__meta,
    .lc-ai-banner__stack.is-advancing > [data-depth="4"] .lc-ai-banner__why,
    .lc-ai-banner__stack.is-advancing > [data-depth="4"] .lc-ai-banner__actions,
    .lc-ai-banner__stack.is-advancing > [data-depth="4"] .lc-ai-banner__meta {
        display: none;
    }
    .lc-ai-banner__stack.is-advancing > [data-depth="1"] .lc-ai-banner__why,
    .lc-ai-banner__stack.is-advancing > [data-depth="1"] .lc-ai-banner__actions,
    .lc-ai-banner__stack.is-advancing > [data-depth="1"] .lc-ai-banner__meta {
        display: block;
    }
    .lc-ai-banner__stack.is-advancing > [data-depth="1"] .lc-ai-banner__actions {
        display: flex;
    }
    .lc-ai-banner__icon {
        flex: 0 0 auto;
        width: 42px;
        height: 42px;
        border-radius: 12px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: var(--lc-ai-yellow);
        color: var(--lc-ai-navy);
    }
    .lc-ai-banner__icon svg { width: 20px; height: 20px; }
    .lc-ai-banner__copy { flex: 1 1 160px; min-width: 0; }
    .lc-ai-banner__title-row {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 8px;
    }
    .lc-ai-banner__title {
        margin: 0;
        font-size: 0.9375rem;
        font-weight: 750;
        color: var(--lc-ai-ink);
        line-height: 1.3;
    }
    .lc-ai-banner__badge {
        display: inline-flex;
        align-items: center;
        padding: 2px 9px;
        border-radius: 999px;
        font-size: 0.6875rem;
        font-weight: 700;
        letter-spacing: 0.02em;
        white-space: nowrap;
        text-transform: uppercase;
    }
    .lc-ai-banner__badge--high { background: #dbeafe; color: #1d4ed8; }
    .lc-ai-banner__badge--medium { background: #fef3c7; color: #92400e; }
    .lc-ai-banner__badge--quick { background: #dcfce7; color: #166534; }
    .lc-ai-banner__badge--premium { background: var(--lc-ai-yellow); color: var(--lc-ai-navy); }
    .lc-ai-banner__why {
        margin: 4px 0 0;
        font-size: 0.8125rem;
        line-height: 1.45;
        color: var(--lc-ai-muted);
    }
    .lc-ai-banner__meta {
        margin: 6px 0 0;
        font-size: 0.75rem;
        font-weight: 700;
        color: var(--lc-ai-blue);
    }
    .lc-ai-banner__actions {
        flex: 0 0 auto;
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 10px;
    }
    .lc-ai-banner__cta,
    .lc-ai-banner__hire {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        min-height: 42px;
        padding: 10px 16px;
        border-radius: 12px;
        border: 0;
        font-family: inherit;
        font-size: 0.8125rem;
        font-weight: 700;
        letter-spacing: 0.01em;
        line-height: 1.2;
        text-decoration: none;
        white-space: nowrap;
        cursor: pointer;
        box-shadow:
            inset 0 1px 0 #ffffff59,
            inset 0 -2px 0 #0003,
            0 1px 2px #0006;
        transition: background 0.2s ease, box-shadow 0.15s ease, translate 0.08s ease, color 0.2s ease;
    }
    .lc-ai-banner__cta {
        background: linear-gradient(#4f9cf9, #2563eb);
        color: #fff;
    }
    .lc-ai-banner__cta:hover {
        background: linear-gradient(#6aafff, #3b82f6);
        color: #fff;
    }
    .lc-ai-banner__cta:active,
    .lc-ai-banner__hire:active {
        translate: 0 1px;
        box-shadow: inset 0 2px 4px #0004;
    }
    .lc-ai-banner__cta svg {
        width: 14px;
        height: 14px;
        flex-shrink: 0;
    }
    .lc-ai-banner__hire {
        background: linear-gradient(#ffd84d, #f0b800);
        color: #082D70;
    }
    .lc-ai-banner__hire:hover {
        background: linear-gradient(#ffe066, #f5c000);
        color: #082D70;
    }
    .lc-ai-banner__hire:disabled {
        opacity: 0.65;
        cursor: wait;
        translate: none;
    }
    .lc-ai-banner__cta:focus-visible,
    .lc-ai-banner__hire:focus-visible,
    .lc-ai-banner__nav-btn:focus-visible,
    .lc-ai-banner__dismiss:focus-visible {
        outline: 2px solid var(--lc-ai-navy);
        outline-offset: 2px;
    }
    .lc-ai-banner__nav {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-shrink: 0;
        margin: 0;
    }
    .lc-ai-banner__nav-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 34px;
        min-height: 34px;
        padding: 0;
        border-radius: 10px;
        background: #fff;
        border: 1px solid #c9daf3;
        color: var(--lc-ai-navy);
        cursor: pointer;
        transition: background 0.15s ease;
    }
    .lc-ai-banner__nav-btn:hover { background: #f8fbff; }
    .lc-ai-banner__caught-up {
        margin: 0;
        padding: 18px 16px;
        border-radius: 14px;
        background: #fff;
        border: 1px solid #c9daf3;
        color: #166534;
        font-size: 0.9375rem;
        font-weight: 700;
        text-align: center;
    }
    .lc-ai-banner__dismiss {
        position: absolute;
        top: 10px;
        right: 10px;
        z-index: 8;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 28px;
        height: 28px;
        border: none;
        border-radius: 8px;
        background: transparent;
        color: rgba(255, 255, 255, 0.75);
        cursor: pointer;
    }
    .lc-ai-banner__dismiss:hover { background: rgba(255, 255, 255, 0.14); color: #fff; }
    @media (prefers-reduced-motion: reduce) {
        .lc-ai-banner {
            --lc-ai-dur: 520ms;
        }
    }
    @media (max-width: 900px) {
        .lc-ai-banner { flex-direction: column; min-height: 0; }
        .lc-ai-banner__bot-wrap {
            width: 100%;
            max-width: none;
            justify-content: center;
            align-items: center;
            padding: 10px 16px 0;
        }
        .lc-ai-banner__bot-frame { width: 168px; height: 178px; }
        .lc-ai-banner__bot-video { width: 168px; max-height: 178px; }
        .lc-ai-banner__bot-fallback { width: 140px; max-height: 160px; }
        .lc-ai-banner__body { padding: 8px 16px 16px; }
        .lc-ai-banner__stack-wrap,
        .lc-ai-banner__stack { min-height: 210px; }
        .lc-ai-banner__layer { right: 0; }
        .lc-ai-banner__actions { width: 100%; }
        .lc-ai-banner__cta,
        .lc-ai-banner__hire { flex: 1 1 auto; }
    }
</style>

<section
    class="lc-ai-banner"
    x-data="aiRecommendationBanner(@js([
        'items' => $unlocked,
        'pendingTotal' => $pendingTotal,
        'storageKey' => 'lc_ai_rec_dismissed_'.(auth()->id() ?? 'guest'),
        'rotateMs' => 10000,
        'motionMs' => 750,
        'servicesRequestUrl' => $servicesRequestUrl,
        'csrfToken' => csrf_token(),
        'packagesUrl' => $packagesUrl,
        'recsUrl' => $recsUrl,
    ]))"
    x-show="bannerVisible"
    x-cloak
    role="region"
    aria-roledescription="carousel"
    aria-label="AI growth recommendations"
>
    <button type="button" class="lc-ai-banner__dismiss" @click="dismissBanner()" aria-label="Dismiss recommendations banner">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M18 6L6 18"/>
        </svg>
    </button>

    <div class="lc-ai-banner__bot-wrap" aria-hidden="true">
        <div class="lc-ai-banner__bot-frame">
            <video
                x-ref="botVideo"
                x-show="!botVideoFailed"
                class="lc-ai-banner__bot lc-ai-banner__bot-video"
                src="{{ $botVideoMp4Url }}"
                muted
                playsinline
                webkit-playsinline
                preload="auto"
                tabindex="-1"
                x-on:error="botVideoFailed = true"
            ></video>
            <img
                x-show="botVideoFailed"
                src="{{ $botUrl }}"
                alt=""
                width="210"
                height="220"
                class="lc-ai-banner__bot lc-ai-banner__bot-fallback"
                :class="{ 'is-spinning': spinning }"
                decoding="async"
            >
        </div>
    </div>

    <div class="lc-ai-banner__body">
        <div class="lc-ai-banner__head">
            <div>
                <p class="lc-ai-banner__eyebrow">AI Recommendations</p>
                <h2 class="lc-ai-banner__heading">Growth opportunities for your library</h2>
            </div>
            <div
                class="lc-ai-banner__nav"
                x-show="queue.length > 1"
                x-cloak
                @mouseenter="pause()"
                @mouseleave="resume()"
                @focusin="pause()"
                @focusout="resume()"
            >
                <button type="button" class="lc-ai-banner__nav-btn" @click="prev()" aria-label="Previous recommendation">
                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.25" d="M15 19l-7-7 7-7"/>
                    </svg>
                </button>
                <button type="button" class="lc-ai-banner__nav-btn" @click="next()" aria-label="Next recommendation">
                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.25" d="M9 5l7 7-7 7"/>
                    </svg>
                </button>
            </div>
        </div>

        <template x-if="caughtUp">
            <p class="lc-ai-banner__caught-up">You're all caught up! Great work keeping your library growing.</p>
        </template>

        <div
            class="lc-ai-banner__stack-wrap"
            x-show="!caughtUp"
            x-cloak
            @mouseenter="pause()"
            @mouseleave="resume()"
            @focusin="pause()"
            @focusout="resume()"
        >
            <div
                class="lc-ai-banner__stack"
                :class="{ 'is-advancing': advancing, 'is-reversing': reversing, 'is-snap': snapping }"
                aria-live="polite"
            >
                <template x-for="slot in slots" :key="'slot-' + slot.depth">
                    <div
                        class="lc-ai-banner__layer"
                        :class="{ 'is-active': slot.depth === 0 && !advancing && !reversing }"
                        :data-depth="slot.depth"
                        :aria-hidden="slot.depth !== 0 || advancing || reversing"
                    >
                        <div class="lc-ai-banner__card">
                            <span class="lc-ai-banner__icon" aria-hidden="true">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6M7 4h10a2 2 0 012 2v14l-4-2-4 2-4-2-4 2V6a2 2 0 012-2z"/>
                                </svg>
                            </span>
                            <div class="lc-ai-banner__copy">
                                <div class="lc-ai-banner__title-row">
                                    <h3 class="lc-ai-banner__title" x-text="slot.item.title"></h3>
                                    <span class="lc-ai-banner__badge" :class="slot.item.impact_class" x-text="slot.item.impact_label"></span>
                                </div>
                                <p class="lc-ai-banner__why" x-text="slot.item.why"></p>
                                <p class="lc-ai-banner__meta" x-show="slot.item.gain > 0" x-cloak>
                                    <span x-text="'+' + slot.item.gain + ' growth pts'"></span>
                                </p>
                            </div>
                            <div class="lc-ai-banner__actions">
                                <a class="lc-ai-banner__cta" :href="slot.item.cta_url">
                                    <span x-text="slot.item.cta_label || 'View Details'"></span>
                                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.25" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/>
                                    </svg>
                                </a>
                                <template x-if="slot.item.show_hire">
                                    <button
                                        type="button"
                                        class="lc-ai-banner__hire"
                                        :disabled="busy === slot.item.key"
                                        @click="hireExpert(slot.item)"
                                    >
                                        <span x-text="busy === slot.item.key ? 'Sending…' : 'Hire an Expert'"></span>
                                    </button>
                                </template>
                            </div>
                        </div>
                    </div>
                </template>

                <div class="lc-ai-banner__incoming" x-show="reversing && incomingItem" x-cloak>
                    <template x-if="incomingItem">
                        <div class="lc-ai-banner__card">
                            <span class="lc-ai-banner__icon" aria-hidden="true">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6M7 4h10a2 2 0 012 2v14l-4-2-4 2-4-2-4 2V6a2 2 0 012-2z"/>
                                </svg>
                            </span>
                            <div class="lc-ai-banner__copy">
                                <div class="lc-ai-banner__title-row">
                                    <h3 class="lc-ai-banner__title" x-text="incomingItem.title"></h3>
                                    <span class="lc-ai-banner__badge" :class="incomingItem.impact_class" x-text="incomingItem.impact_label"></span>
                                </div>
                                <p class="lc-ai-banner__why" x-text="incomingItem.why"></p>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>
</section>
@endif
