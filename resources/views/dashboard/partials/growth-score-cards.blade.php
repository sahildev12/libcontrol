@php
    $business = $businessPerformance ?? ($combinedGrowth['business_performance'] ?? []);
    $businessPct = (int) ($combinedGrowth['business_pct'] ?? ($business['score_rounded'] ?? 0));
    $growthPct = (int) ($combinedGrowth['growth_score'] ?? 0);
    $scoreLabel = fn (int $pct): array => match (true) {
        $pct >= 80 => ['Excellent', '#16A34A'],
        $pct >= 60 => ['Good', '#2563EB'],
        $pct >= 40 => ['Fair', '#D97706'],
        default => ['Needs attention', '#DC2626'],
    };
    [$growthLabel, $growthLabelColor] = $scoreLabel($growthPct);
    [$businessLabel, $businessLabelColor] = $scoreLabel($businessPct);

    $ringR = 46;
    $ringCirc = round(2 * M_PI * $ringR, 2);
    $growthOffset = round($ringCirc * (1 - $growthPct / 100), 2);
    $businessOffset = round($ringCirc * (1 - $businessPct / 100), 2);

    $showProfileCompletionCard = isset($profileCompletionScore);
    $profilePct = $showProfileCompletionCard ? (int) ($profileCompletionScore['pct'] ?? 0) : 0;
    $profileOffset = round($ringCirc * (1 - $profilePct / 100), 2);
    [$profileLabel, $profileLabelColor] = $showProfileCompletionCard
        ? $scoreLabel($profilePct)
        : ['', '#718096'];
    $profileCompleteUrl = $showProfileCompletionCard
        ? (\Illuminate\Support\Facades\Route::has('profile-completion.index')
            ? route('profile-completion.index')
            : (\Illuminate\Support\Facades\Route::has('settings.index') ? route('settings.index') : null))
        : null;

    $improveScoreUrl = (! isset($improveScoreUrl))
        && \Illuminate\Support\Facades\Route::has('growth.index')
        && ! request()->routeIs('growth.index')
        ? route('growth.index') . '#recommendations'
        : ($improveScoreUrl ?? null);
@endphp

<style>
    .lc-score-info { position: relative; display: inline-flex; vertical-align: middle; }
    .lc-score-info__btn {
        display: inline-flex; align-items: center; justify-content: center;
        width: 18px; height: 18px; border: none; padding: 0; border-radius: 999px;
        background: transparent; color: #718096; cursor: help;
    }
    .lc-score-info__btn:hover,
    .lc-score-info__btn:focus-visible { background: #F1F5F9; color: #475569; outline: none; }
    .lc-score-info__tip {
        position: absolute; left: 50%; bottom: calc(100% + 8px);
        z-index: 30; width: max(200px, 13rem); max-width: min(240px, 70vw);
        padding: 8px 10px; border-radius: 8px;
        background: #082D70; color: #fff; font-size: 11px; font-weight: 500; line-height: 1.45;
        text-align: left; box-shadow: 0 4px 14px rgba(8, 45, 112, 0.22);
        opacity: 0; visibility: hidden; pointer-events: none;
        transform: translateX(-50%) translateY(4px);
        transition: opacity 0.15s ease, transform 0.15s ease, visibility 0.15s;
    }
    .lc-score-info__tip::after {
        content: ''; position: absolute; left: 50%; top: 100%; margin-left: -5px;
        border: 5px solid transparent; border-top-color: #082D70;
    }
    .lc-score-info:hover .lc-score-info__tip,
    .lc-score-info:focus-within .lc-score-info__tip {
        opacity: 1; visibility: visible; transform: translateX(-50%) translateY(0);
    }

    .lc-score-card {
        overflow: visible;
        border: 1px solid #E8EEF7;
        border-radius: 16px;
        background: #fff;
        box-shadow: 0 1px 3px rgba(8, 42, 103, 0.06);
    }
    .lc-score-card__pad { padding: 18px 18px 16px; }

    .lc-score-ring-wrap {
        position: relative;
        flex-shrink: 0;
        width: 108px;
        height: 108px;
    }
    .lc-score-ring {
        display: block;
        width: 108px;
        height: 108px;
    }
    .lc-score-ring__track { stroke: #E8ECF2; }
    .lc-score-ring__progress {
        stroke-linecap: round;
        stroke-dasharray: {{ $ringCirc }};
        stroke-dashoffset: {{ $ringCirc }};
    }
    .lc-score-ring__progress--growth { stroke: #f6cd3b; }
    .lc-score-ring__progress--business { stroke: #22C55E; }
    .lc-score-ring__progress--profile { stroke: #2563EB; }
    .lc-score-ring__center {
        position: absolute;
        inset: 12px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        border-radius: 999px;
        background: #fff;
    }
    .lc-score-ring__value {
        font-size: 28px;
        font-weight: 700;
        line-height: 1;
        color: #082a67;
        font-variant-numeric: tabular-nums;
    }
    .lc-score-ring__max {
        margin-top: 2px;
        font-size: 11px;
        font-weight: 500;
        color: #718096;
    }
    .lc-score-cta {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        margin-top: 10px;
        min-height: 32px;
        padding: 0 12px;
        border-radius: 8px;
        background: #FFCC00;
        color: #082a67;
        font-size: 12px;
        font-weight: 700;
        text-decoration: none;
        white-space: nowrap;
    }
    .lc-score-cta:hover { filter: brightness(0.97); }
</style>

<div id="lc-score-cards-root" class="grid gap-4 md:grid-cols-2 {{ $showProfileCompletionCard ? 'xl:grid-cols-3' : '' }}">
    <section class="lc-score-card">
        <div class="lc-score-card__pad">
            <div class="flex items-center" style="gap:6px;">
                <h3 class="font-bold" style="margin:0;font-size:14px;color:#082a67;">Growth Score</h3>
                <span class="lc-score-info">
                    <button
                        type="button"
                        class="lc-score-info__btn"
                        aria-describedby="lc-growth-score-tip"
                        aria-label="What is the growth score?"
                    >
                        <svg style="width:14px;height:14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </button>
                    <span id="lc-growth-score-tip" role="tooltip" class="lc-score-info__tip">
                        Your overall library growth. Complete recommendations and keep business metrics healthy to raise this score.
                    </span>
                </span>
            </div>

            <div class="flex items-center" style="margin-top:14px;gap:16px;">
                <div class="lc-score-ring-wrap" role="img" aria-label="Growth score {{ $growthPct }} out of 100">
                    <svg class="lc-score-ring" viewBox="0 0 108 108" aria-hidden="true">
                        <circle class="lc-score-ring__track" cx="54" cy="54" r="{{ $ringR }}" fill="none" stroke-width="10"/>
                        <circle
                            class="lc-score-ring__progress lc-score-ring__progress--growth js-lc-score-ring"
                            cx="54"
                            cy="54"
                            r="{{ $ringR }}"
                            fill="none"
                            stroke-width="10"
                            transform="rotate(-90 54 54)"
                            data-circ="{{ $ringCirc }}"
                            data-target-offset="{{ $growthOffset }}"
                            data-delay="50"
                            data-duration="1100"
                        />
                    </svg>
                    <div class="lc-score-ring__center">
                        <span
                            class="lc-score-ring__value js-lc-score-count"
                            data-target="{{ $growthPct }}"
                            data-delay="50"
                            data-duration="1100"
                        >0</span>
                        <span class="lc-score-ring__max">/ 100</span>
                    </div>
                </div>
                <div class="min-w-0 flex-1">
                    <p class="font-bold" style="margin:0;font-size:15px;color:{{ $growthLabelColor }};">{{ $growthLabel }}</p>
                    <p style="margin:4px 0 0;font-size:12px;line-height:1.45;color:#718096;">Your library's overall growth. Complete recommendations to raise it.</p>
                    @if ($improveScoreUrl)
                        <a href="{{ $improveScoreUrl }}" class="lc-score-cta">
                            Improve score
                            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.25" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg>
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </section>

    <section class="lc-score-card">
        <div class="lc-score-card__pad">
            <div class="flex items-center" style="gap:6px;">
                <h3 class="font-bold" style="margin:0;font-size:14px;color:#082a67;">Business Performance</h3>
                <span class="lc-score-info">
                    <button
                        type="button"
                        class="lc-score-info__btn"
                        aria-describedby="lc-business-performance-tip"
                        aria-label="What is business performance?"
                    >
                        <svg style="width:14px;height:14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </button>
                    <span id="lc-business-performance-tip" role="tooltip" class="lc-score-info__tip">
                        Measured automatically from seats, fees, renewals, enquiries, profile setup, and how actively you use LibControl.
                    </span>
                </span>
            </div>

            <div class="flex items-center" style="margin-top:14px;gap:16px;">
                <div class="lc-score-ring-wrap" role="img" aria-label="Business performance {{ $businessPct }} out of 100">
                    <svg class="lc-score-ring" viewBox="0 0 108 108" aria-hidden="true">
                        <circle class="lc-score-ring__track" cx="54" cy="54" r="{{ $ringR }}" fill="none" stroke-width="10"/>
                        <circle
                            class="lc-score-ring__progress lc-score-ring__progress--business js-lc-score-ring"
                            cx="54"
                            cy="54"
                            r="{{ $ringR }}"
                            fill="none"
                            stroke-width="10"
                            transform="rotate(-90 54 54)"
                            data-circ="{{ $ringCirc }}"
                            data-target-offset="{{ $businessOffset }}"
                            data-delay="150"
                            data-duration="1100"
                        />
                    </svg>
                    <div class="lc-score-ring__center">
                        <span
                            class="lc-score-ring__value js-lc-score-count"
                            data-target="{{ $businessPct }}"
                            data-delay="150"
                            data-duration="1100"
                        >0</span>
                        <span class="lc-score-ring__max">/ 100</span>
                    </div>
                </div>
                <div class="min-w-0 flex-1">
                    <p class="font-bold" style="margin:0;font-size:15px;color:{{ $businessLabelColor }};">{{ $businessLabel }}</p>
                    <p style="margin:4px 0 0;font-size:12px;line-height:1.45;color:#718096;">Measured automatically from seats, fees, students and enquiries.</p>
                </div>
            </div>
        </div>
    </section>

    @if ($showProfileCompletionCard)
        <section class="lc-score-card">
            <div class="lc-score-card__pad">
                <div class="flex items-center" style="gap:6px;">
                    <h3 class="font-bold" style="margin:0;font-size:14px;color:#082a67;">Profile completion</h3>
                    <span class="lc-score-info">
                        <button
                            type="button"
                            class="lc-score-info__btn"
                            aria-describedby="lc-profile-completion-tip"
                            aria-label="What is profile completion?"
                        >
                            <svg style="width:14px;height:14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </button>
                        <span id="lc-profile-completion-tip" role="tooltip" class="lc-score-info__tip">
                            How complete your library profile is — logo, branch details, amenities, gallery, and social links.
                        </span>
                    </span>
                </div>

                <div class="flex items-center" style="margin-top:14px;gap:16px;">
                    <div class="lc-score-ring-wrap" role="img" aria-label="Profile completion {{ $profilePct }} percent">
                        <svg class="lc-score-ring" viewBox="0 0 108 108" aria-hidden="true">
                            <circle class="lc-score-ring__track" cx="54" cy="54" r="{{ $ringR }}" fill="none" stroke-width="10"/>
                            <circle
                                class="lc-score-ring__progress lc-score-ring__progress--profile js-lc-score-ring"
                                cx="54"
                                cy="54"
                                r="{{ $ringR }}"
                                fill="none"
                                stroke-width="10"
                                transform="rotate(-90 54 54)"
                                data-circ="{{ $ringCirc }}"
                                data-target-offset="{{ $profileOffset }}"
                                data-delay="250"
                                data-duration="1100"
                            />
                        </svg>
                        <div class="lc-score-ring__center">
                            <span
                                class="lc-score-ring__value js-lc-score-count"
                                data-target="{{ $profilePct }}"
                                data-delay="250"
                                data-duration="1100"
                            >0</span>
                            <span class="lc-score-ring__max">/ 100</span>
                        </div>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="font-bold" style="margin:0;font-size:15px;color:{{ $profileLabelColor }};">{{ $profileLabel }}</p>
                        <p style="margin:4px 0 0;font-size:12px;line-height:1.45;color:#718096;">
                            @if ($profileCompletionScore['complete'] ?? false)
                                Your library profile is fully set up.
                            @else
                                Add halls, hours, amenities, photos and more to reach 100%.
                            @endif
                        </p>
                        @if ($profileCompleteUrl && ! ($profileCompletionScore['complete'] ?? false))
                            <a href="{{ $profileCompleteUrl }}" class="lc-score-cta">
                                Complete profile
                                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.25" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg>
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </section>
    @endif
</div>

<script>
    (function () {
        function easeOutCubic(t) {
            return 1 - Math.pow(1 - t, 3);
        }

        function animateRing(el) {
            const full = Number(el.dataset.circ) || 0;
            const targetOffset = Number(el.dataset.targetOffset) || 0;
            const delay = Number(el.dataset.delay) || 0;
            const duration = Number(el.dataset.duration) || 1100;
            el.style.strokeDashoffset = String(full);
            const startAt = performance.now() + delay;

            function tick(now) {
                if (now < startAt) {
                    requestAnimationFrame(tick);
                    return;
                }
                const progress = Math.min(1, (now - startAt) / duration);
                const eased = easeOutCubic(progress);
                const offset = full - (full - targetOffset) * eased;
                el.style.strokeDashoffset = String(offset);
                if (progress < 1) {
                    requestAnimationFrame(tick);
                }
            }

            requestAnimationFrame(tick);
        }

        function animateCounter(el) {
            const target = Number(el.dataset.target) || 0;
            const delay = Number(el.dataset.delay) || 0;
            const duration = Number(el.dataset.duration) || 1100;
            el.textContent = '0';
            const startAt = performance.now() + delay;

            function tick(now) {
                if (now < startAt) {
                    requestAnimationFrame(tick);
                    return;
                }
                const progress = Math.min(1, (now - startAt) / duration);
                el.textContent = String(Math.round(target * easeOutCubic(progress)));
                if (progress < 1) {
                    requestAnimationFrame(tick);
                }
            }

            requestAnimationFrame(tick);
        }

        function boot() {
            const root = document.getElementById('lc-score-cards-root');
            if (!root) {
                return;
            }

            root.querySelectorAll('.js-lc-score-ring').forEach(animateRing);
            root.querySelectorAll('.js-lc-score-count').forEach(animateCounter);
        }

        function scheduleBoot() {
            requestAnimationFrame(function () {
                requestAnimationFrame(boot);
            });
        }

        scheduleBoot();
        window.addEventListener('pageshow', function (event) {
            if (event.persisted) {
                scheduleBoot();
            }
        });
    })();
</script>
