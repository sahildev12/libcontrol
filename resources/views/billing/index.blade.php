<x-admin-layout>
    @php
        $limits = $snapshot['limits'];
        $usage = $snapshot['usage'];
        $currentTier = $limits['plan_tier'];
        $rupees = fn (int $paise) => '₹'.number_format($paise / 100, $paise % 100 === 0 ? 0 : 2);
        $statusStyles = [
            'completed' => 'bg-green-50 text-green-700 ring-green-200',
            'failed' => 'bg-red-50 text-red-700 ring-red-200',
            'pending' => 'bg-amber-50 text-amber-700 ring-amber-200',
            'created' => 'bg-slate-100 text-slate-600 ring-slate-200',
        ];
    @endphp

    <header class="flex flex-col gap-2">
        <h1 class="text-2xl font-bold tracking-tight text-[#082D70]">Subscription &amp; Billing</h1>
        <p class="text-sm text-gray-500">Your LibControl plan, limits and payments. Payments are processed securely by PhonePe for Katsindia.</p>
    </header>

    @if ($returnedPayment)
        @php $rs = $returnedPayment['status']; @endphp
        <div class="mt-5 rounded-xl border px-4 py-3 text-sm {{ $rs === 'completed' ? 'border-green-200 bg-green-50 text-green-800' : ($rs === 'failed' ? 'border-red-200 bg-red-50 text-red-800' : 'border-amber-200 bg-amber-50 text-amber-800') }}" role="status">
            @if ($rs === 'completed')
                <strong>Payment successful.</strong> {{ $returnedPayment['item_name'] }} — {{ $returnedPayment['amount_label'] }} received. Your plan is now active.
            @elseif ($rs === 'failed')
                <strong>Payment failed.</strong> {{ $returnedPayment['item_name'] }} was not charged. You can try again below.
            @else
                <strong>Payment not confirmed yet.</strong> If you paid, it will be applied automatically within a few minutes — please don't pay twice. If you left PhonePe without paying, nothing was charged.
            @endif
        </div>
    @endif

    @error('payment')
        <div class="mt-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">{{ $message }}</div>
    @enderror

    <section class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Current plan</p>
            <p class="mt-2 text-2xl font-bold text-[#082D70]">{{ $limits['plan_label'] }}</p>
            <p class="mt-1 text-sm text-gray-600">
                @if ($paidUntil)
                    Paid until <span class="font-semibold {{ $paidUntil->isPast() ? 'text-red-600' : 'text-gray-900' }}">{{ $paidUntil->format('d M Y') }}</span>
                    @if ($paidUntil->isPast()) <span class="text-red-600">(expired — please renew)</span> @endif
                @else
                    Managed by Katsindia
                @endif
            </p>
        </div>
        @foreach ([['Seats', 'seats', 'max_seats'], ['Halls', 'halls', 'max_halls'], ['Branches', 'branches', 'max_branches']] as [$label, $usageKey, $limitKey])
            @php($max = $limits[$limitKey])
            @php($used = $usage[$usageKey])
            @php($pct = $max ? min(100, (int) round($used / max(1, $max) * 100)) : 0)
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ $label }} used</p>
                <p class="mt-2 text-2xl font-bold text-gray-900">{{ $used }} <span class="text-base font-medium text-gray-400">/ {{ $max ?? 'Unlimited' }}</span></p>
                @if ($max)
                    <div class="mt-3 h-2 overflow-hidden rounded-full bg-gray-100"><div class="h-full rounded-full {{ $pct >= 100 ? 'bg-red-500' : 'bg-[#2563EB]' }}" style="width: {{ $pct }}%"></div></div>
                @endif
            </div>
        @endforeach
    </section>

    <section class="mt-8">
        <h2 class="text-lg font-bold text-[#082D70]">Choose or renew your plan</h2>
        <p class="mt-1 text-sm text-gray-500">Renewing the same plan adds time to your current period. Upgrades apply immediately after payment.</p>

        @if (! $paymentsAvailable)
            <div class="mt-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                {{ $unavailableReason ?? ($hubReachable ? 'Online payments are not available right now.' : 'Could not reach Katsindia right now. Please refresh in a minute.') }}
                Need help? <a href="{{ route('help-support.index') }}" class="font-semibold underline">Contact support</a>.
            </div>
        @endif

        @if ($currentTier === 'custom')
            <div class="mt-4 rounded-xl border border-indigo-200 bg-indigo-50 px-4 py-3 text-sm text-indigo-900">
                Your library is on a custom plan. Katsindia will share renewal details with you directly.
            </div>
        @else
            <div class="mt-4 grid gap-4 md:grid-cols-2">
                @foreach ($prices as $key => $price)
                    @php($planLimits = $plans[$key] ?? [])
                    @php($isCurrent = $key === $currentTier)
                    <article class="relative flex flex-col rounded-2xl border bg-white p-6 shadow-sm {{ $key === 'pro' ? 'border-[#082D70] ring-1 ring-[#082D70]' : 'border-gray-200' }}">
                        @if ($isCurrent)
                            <span class="absolute right-4 top-4 rounded-full bg-green-50 px-2.5 py-1 text-[11px] font-semibold text-green-700 ring-1 ring-inset ring-green-200">Current plan</span>
                        @elseif ($key === 'pro')
                            <span class="absolute right-4 top-4 rounded-full bg-[#ffc800] px-2.5 py-1 text-[11px] font-bold text-[#082D70]">Recommended</span>
                        @endif
                        <h3 class="text-xl font-bold text-[#082D70]">{{ $price['label'] }}</h3>
                        <p class="mt-2"><span class="text-3xl font-bold text-gray-900">{{ $rupees((int) $price['monthly']) }}</span><span class="text-sm text-gray-500">/month</span></p>
                        <p class="text-sm text-gray-500">or {{ $rupees((int) $price['yearly']) }}/year</p>
                        <ul class="mt-4 flex-1 space-y-2 text-sm text-gray-700">
                            <li class="flex gap-2"><span class="text-[#2563EB]">✓</span> Up to {{ $planLimits['max_seats'] ?? 'unlimited' }} seats</li>
                            <li class="flex gap-2"><span class="text-[#2563EB]">✓</span> {{ $planLimits['max_halls'] ?? 'Unlimited' }} {{ ($planLimits['max_halls'] ?? 0) === 1 ? 'hall' : 'halls' }}</li>
                            <li class="flex gap-2"><span class="text-[#2563EB]">✓</span> {{ $planLimits['max_branches'] ?? 'Unlimited' }} {{ ($planLimits['max_branches'] ?? 0) === 1 ? 'branch' : 'branches' }}</li>
                        </ul>
                        <div class="mt-5 grid gap-2 sm:grid-cols-2">
                            @foreach (['monthly' => 'Pay monthly', 'yearly' => 'Pay yearly'] as $cycle => $cta)
                                <form method="POST" action="{{ route('billing.pay') }}" x-data="{ busy: false }" @submit="busy = true">
                                    @csrf
                                    <input type="hidden" name="plan" value="{{ $key }}">
                                    <input type="hidden" name="cycle" value="{{ $cycle }}">
                                    <button type="submit" @disabled(! $paymentsAvailable) :disabled="busy" class="w-full rounded-lg px-4 py-2.5 text-sm font-semibold transition disabled:cursor-not-allowed disabled:opacity-50 {{ $cycle === 'monthly' ? 'bg-[#082D70] text-white hover:bg-[#0b3a8f]' : 'border border-[#082D70] bg-white text-[#082D70] hover:bg-indigo-50' }}">
                                        <span x-show="!busy">{{ $cta }} · {{ $rupees((int) $price[$cycle]) }}</span>
                                        <span x-show="busy" x-cloak>Opening PhonePe…</span>
                                    </button>
                                </form>
                            @endforeach
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
        <p class="mt-3 text-xs text-gray-500">Pay with UPI, cards or net banking on PhonePe's secure page. You'll come back here automatically.</p>
    </section>

    <section class="mt-8">
        <h2 class="text-lg font-bold text-[#082D70]">Payment history</h2>
        <div class="mt-3 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[640px] text-sm">
                    <thead class="border-b border-gray-200 bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                        <tr>
                            <th class="px-4 py-3">Item</th>
                            <th class="px-4 py-3">Amount</th>
                            <th class="px-4 py-3">Date</th>
                            <th class="px-4 py-3">Reference</th>
                            <th class="px-4 py-3">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-gray-700">
                        @forelse ($payments as $p)
                            <tr>
                                <td class="px-4 py-3"><p class="font-semibold text-[#082D70]">{{ $p['item_name'] }}</p><p class="text-xs text-gray-500">{{ $p['purpose_label'] }}</p></td>
                                <td class="whitespace-nowrap px-4 py-3 font-medium text-gray-900">{{ $p['amount_label'] }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-gray-500">{{ \Illuminate\Support\Carbon::parse($p['paid_at'] ?? $p['created_at'])->timezone(config('app.timezone'))->format('d M Y, h:i A') }}</td>
                                <td class="px-4 py-3 font-mono text-xs text-gray-500">{{ $p['reference'] }}</td>
                                <td class="px-4 py-3">
                                    <span class="rounded-full px-2.5 py-1 text-[11px] font-semibold ring-1 ring-inset {{ $statusStyles[$p['status']] ?? $statusStyles['created'] }}">{{ $p['status_label'] }}</span>
                                    @if (in_array($p['status'], ['pending', 'created'], true))
                                        <p class="mt-1.5 max-w-[220px] text-[11px] leading-snug text-gray-500">Not confirmed by PhonePe yet. Nothing is charged if you didn't finish paying.</p>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-4 py-10 text-center text-gray-500">No online payments yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</x-admin-layout>
