@php
    $business = $businessPerformance ?? [];
    $businessPct = (int) ($business['score_rounded'] ?? 0);
    $businessConfidence = (int) ($business['data_confidence'] ?? 0);
    $businessFactors = $business['factors'] ?? [];

    $routeOr = fn (string $name, array $params = [], string $fallback = '#') => \Illuminate\Support\Facades\Route::has($name) ? route($name, $params) : $fallback;
    $growthHubUrl = $routeOr('growth.index');

    $factorHrefs = [
        'profile_completion' => $routeOr('profile-completion.index', [], $routeOr('settings.index', ['tab' => 'website'])),
        'occupancy' => $routeOr('seats.index'),
        'renewals' => $routeOr('fees.index'),
        'branch_staff' => $routeOr('branch.index'),
        'membership_payment' => $routeOr('fees.index'),
        'expenses_finance' => $routeOr('finance.index'),
        'operations' => $routeOr('halls.index'),
        'system_usage' => $growthHubUrl,
    ];
@endphp

<div x-show="settingsTab === 'business'" x-cloak class="mt-4 space-y-6">
    <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <div class="border-b border-gray-100 px-5 py-4">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="text-sm font-semibold text-gray-900">Business Performance</h2>
                    <p class="mt-1 text-xs text-gray-600">
                        Weighted business health across occupancy, renewals, payments, finance, operations, and system usage.
                        This is {{ (int) round(((float) config('growth.growth_score.business_weight', 0.6)) * 100) }}% of the Growth Score.
                    </p>
                </div>
                <span class="text-lg font-bold tabular-nums text-gray-900">{{ $businessPct }}/100</span>
            </div>
            <span class="relative mt-4 block h-3 overflow-hidden rounded-full bg-gray-100" role="progressbar" aria-valuenow="{{ $businessPct }}" aria-valuemin="0" aria-valuemax="100">
                <span class="absolute inset-y-0 left-0 rounded-full bg-blue-500" style="width: {{ $businessPct }}%"></span>
            </span>
            @if ($businessConfidence < 100)
                <p class="mt-2 text-xs text-gray-500">Data confidence {{ $businessConfidence }}% — factors without data are left out, not counted as zero.</p>
            @endif
        </div>

        <ul class="divide-y divide-gray-100">
            @forelse ($businessFactors as $factor)
                @php
                    $available = (bool) ($factor['available'] ?? false);
                    $factorScore = $available ? (int) round((float) ($factor['score'] ?? 0)) : null;
                    $weightPct = (int) round(((float) ($factor['weight'] ?? 0)) * 100);
                @endphp
                <li>
                    <a href="{{ $factorHrefs[$factor['key'] ?? ''] ?? '#' }}" class="flex items-center gap-4 px-5 py-3 transition-colors hover:bg-gray-50">
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-semibold text-gray-900">
                                {{ $factor['label'] ?? '' }}
                                <span class="font-medium text-gray-400">· {{ $weightPct }}%</span>
                            </p>
                            <p class="mt-0.5 text-xs text-gray-500">{{ $factor['detail'] ?? '' }}</p>
                        </div>
                        <div class="hidden w-32 sm:block">
                            <span class="relative block h-2 overflow-hidden rounded-full bg-gray-100">
                                <span class="absolute inset-y-0 left-0 rounded-full bg-blue-500" style="width: {{ $factorScore ?? 0 }}%"></span>
                            </span>
                        </div>
                        <span class="w-10 shrink-0 text-right text-sm font-semibold tabular-nums {{ $available ? 'text-gray-900' : 'text-gray-400' }}">
                            {{ $available ? $factorScore : '—' }}
                        </span>
                        <svg class="size-4 shrink-0 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </a>
                </li>
            @empty
                <li class="px-5 py-8 text-center text-sm text-gray-500">Business performance is not available yet.</li>
            @endforelse
        </ul>
    </section>
</div>
