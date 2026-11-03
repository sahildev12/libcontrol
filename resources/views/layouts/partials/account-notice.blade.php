@php
    use App\Services\Tenancy\TrialLifecycle;
    use App\Support\Subscription;
    use App\Support\Tenancy\TenantContext;

    $notice = null;
    $ownerView = ($isClientAdmin ?? false);
    $trialTenant = TenantContext::current();

    if ($trialTenant?->is_trial && ! ($isDeveloperAdmin ?? false)) {
        $upgrade = $ownerView ? ['label' => 'Upgrade now', 'url' => app(TrialLifecycle::class)->upgradeUrl($trialTenant), 'external' => true] : null;

        if ($trialTenant->isReadOnly()) {
            $deletesAt = $trialTenant->deletesAt();
            $notice = [
                'urgent' => true,
                'dismissible' => false,
                'title' => 'Your free trial has ended',
                'text' => $ownerView
                    ? 'This library is read-only. Upgrade to keep adding and changing records'.($deletesAt ? '; unpaid trials are deleted on '.$deletesAt->format('d M Y').'.' : '.')
                    : 'This library is read-only. Ask the library owner to upgrade.',
                'action' => $upgrade,
            ];
        } elseif ($deadline = $trialTenant->verificationDeadline()) {
            $hoursLeft = max(1, (int) ceil(now()->diffInMinutes($deadline) / 60));
            $notice = [
                'urgent' => false,
                'dismissible' => false,
                'title' => 'Please verify your email',
                'text' => 'We sent a link to '.$trialTenant->owner_email.'. Verify within '.$hoursLeft.' '.($hoursLeft === 1 ? 'hour' : 'hours').' to keep using this library.',
                'action' => $ownerView ? ['label' => 'Resend link', 'form' => route('trials.resend-verification')] : null,
            ];
        } elseif ($trialTenant->trial_ends_at) {
            $daysLeft = max(1, (int) ceil(now()->diffInHours($trialTenant->trial_ends_at) / 24));
            $notice = [
                'urgent' => false,
                'dismissible' => false,
                'title' => 'Free trial: '.$daysLeft.' '.($daysLeft === 1 ? 'day' : 'days').' left',
                'text' => $ownerView ? 'Upgrade any time to keep your library after the trial.' : null,
                'action' => $upgrade,
            ];
        }
    } elseif (! ($isDeveloperAdmin ?? false) && ! ($licenseServerEnabled ?? false)) {
        $subscription = Subscription::status();
        $state = $subscription['state'];
        $renew = $ownerView && ! request()->routeIs('billing.*');

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
                'action' => $renew ? ['label' => 'Renew now', 'url' => route('billing.index')] : null,
            ];
        } elseif (($planLimitCapped ?? []) !== [] && ($planSnapshot ?? null) && ! request()->routeIs('billing.*')) {
            $dismissedAt = (int) session('plan_limit_dismissed_at', 0);

            if ($dismissedAt === 0 || now()->timestamp - $dismissedAt >= 3 * 3600) {
                $notice = [
                    'urgent' => false,
                    'dismissible' => true,
                    'title' => 'Plan limit reached',
                    'text' => $ownerView ? 'Upgrade to add more branches, halls or seats.' : 'Ask the library owner to upgrade.',
                    'action' => $ownerView ? ['label' => 'Upgrade plan', 'url' => route('billing.index')] : null,
                ];
            }
        }
    }

    $toast = session('trial_read_only')
        ? ['message' => session('trial_read_only'), 'type' => 'error']
        : (session('trial_notice') ? ['message' => session('trial_notice'), 'type' => 'success'] : null);
@endphp

@if ($toast)
    <div x-data x-init="$nextTick(() => $dispatch('show-toast', @js($toast)))" hidden></div>
@endif

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
        @if ($action = $notice['action'] ?? null)
            @if (isset($action['form']))
                <form method="POST" action="{{ $action['form'] }}" class="shrink-0">
                    @csrf
                    <button
                        type="submit"
                        class="lc-web-btn lc-web-btn--yellow"
                        style="min-height:34px;padding:0 16px;border-radius:8px !important;font-size:12.5px;text-transform:uppercase;letter-spacing:0.04em;"
                    >{{ $action['label'] }}</button>
                </form>
            @else
                <a
                    href="{{ $action['url'] }}"
                    @if ($action['external'] ?? false) target="_blank" rel="noopener" @endif
                    class="lc-web-btn lc-web-btn--yellow shrink-0"
                    style="min-height:34px;padding:0 16px;border-radius:8px !important;font-size:12.5px;text-transform:uppercase;letter-spacing:0.04em;"
                >{{ $action['label'] }}</a>
            @endif
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
