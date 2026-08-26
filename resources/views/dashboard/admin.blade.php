@php
    $kpis = $admin['kpis'];
    $attention = $admin['attention'];
    $revenueMonths = $admin['revenue_months'];
    $revenueChartMax = max(1, (float) ($admin['revenue_chart_max'] ?? 1));
    $revenueAxis = $admin['revenue_chart_axis'] ?? [];
    $chartHeight = 208;
    $revenueMonthsCount = (int) ($admin['revenue_months_count'] ?? 6);
@endphp

<div
    x-data="{
        page: 1,
        perPage: 5,
        rows: @js($admin['branches']),
        switchUrl: @js($admin['switchUrl']),
        openingId: null,
        rangeOpen: false,
        revenueOpen: false,
        totalPages() { return Math.max(1, Math.ceil(this.rows.length / this.perPage)); },
        pageRows() {
            const start = (this.page - 1) * this.perPage;
            return this.rows.slice(start, start + this.perPage);
        },
        pages() {
            return Array.from({ length: this.totalPages() }, (_, i) => i + 1);
        },
        occupancyBarWidth(value) {
            const pct = Number(value) || 0;
            if (pct <= 0) return '0%';
            return `${Math.min(100, Math.max(pct, 6))}%`;
        },
        async openBranch(id) {
            this.openingId = id;
            try {
                await window.axios.post(this.switchUrl, { branch_id: id });
                window.location.href = @js(route('dashboard'));
            } catch (e) {
                this.openingId = null;
                window.alert(e.response?.data?.message || 'Could not open branch dashboard.');
            }
        },
    }"
    class="space-y-6"
>
    <header class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-gray-900">Dashboard</h1>
            <p class="mt-1 text-sm text-gray-500">System Overview</p>
        </div>

        <div class="flex flex-wrap items-center gap-2 sm:gap-3">
            <div class="relative" @click.outside="rangeOpen = false">
                <button
                    type="button"
                    @click="rangeOpen = !rangeOpen"
                    class="inline-flex h-10 items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50"
                >
                    <svg class="size-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3M5 11h14M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    <span>{{ $admin['range_label'] }}</span>
                </button>
                <form
                    method="get"
                    action="{{ route('dashboard') }}"
                    x-show="rangeOpen"
                    x-cloak
                    x-transition
                    class="absolute right-0 z-20 mt-2 w-72 rounded-xl border border-gray-200 bg-white p-4 shadow-lg"
                >
                    <input type="hidden" name="revenue_months" value="{{ $revenueMonthsCount }}">
                    <div class="space-y-3">
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-gray-500">From</label>
                            <input type="date" name="from" value="{{ $admin['from'] }}" class="w-full rounded-lg border-gray-200 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-gray-500">To</label>
                            <input type="date" name="to" value="{{ $admin['to'] }}" class="w-full rounded-lg border-gray-200 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                        <button type="submit" class="inline-flex h-9 w-full items-center justify-center rounded-lg bg-indigo-600 text-sm font-semibold text-white hover:bg-indigo-700">
                            Apply range
                        </button>
                    </div>
                </form>
            </div>

            <a
                href="{{ route('dashboard', array_merge(request()->only(['from', 'to', 'revenue_months']))) }}"
                class="inline-flex h-10 items-center gap-2 rounded-lg border border-gray-200 bg-white px-3.5 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50"
            >
                <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v6h6M20 20v-6h-6M5 19a9 9 0 0014-7M19 5a9 9 0 00-14 7"/></svg>
                Refresh
            </a>
        </div>
    </header>

    {{-- KPI cards: single row on desktop --}}
    <div class="flex gap-3 overflow-x-auto pb-1 md:overflow-visible">
        <div class="min-w-[148px] flex-1 rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
            <div class="mb-3 inline-flex size-10 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600">
                <svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0H5m14 0h2M5 21H3m4-10h2m4 0h2"/></svg>
            </div>
            <p class="text-xs font-medium text-gray-500">Total Branches</p>
            <p class="mt-1 text-2xl font-bold tabular-nums text-gray-900">{{ number_format($kpis['branches']) }}</p>
            <p class="mt-0.5 text-[11px] text-gray-400">Active branches</p>
            @if ($kpis['branches_delta'] > 0)
                <p class="mt-2 text-[11px] font-semibold text-emerald-600">↑ {{ $kpis['branches_delta'] }} this month</p>
            @endif
        </div>

        <div class="min-w-[148px] flex-1 rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
            <div class="mb-3 inline-flex size-10 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600">
                <svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17 20h5v-2a4 4 0 00-4-4h-1M9 20H4v-2a4 4 0 014-4h1m6-4a4 4 0 11-8 0 4 4 0 018 0zm6 0a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            </div>
            <p class="text-xs font-medium text-gray-500">Total Students</p>
            <p class="mt-1 text-2xl font-bold tabular-nums text-gray-900">{{ number_format($kpis['students']) }}</p>
            <p class="mt-0.5 text-[11px] text-gray-400">Across all branches</p>
            @if ($kpis['students_delta_pct'] !== null)
                <p class="mt-2 text-[11px] font-semibold {{ $kpis['students_delta_pct'] >= 0 ? 'text-emerald-600' : 'text-red-600' }}">
                    {{ $kpis['students_delta_pct'] >= 0 ? '↑' : '↓' }} {{ abs($kpis['students_delta_pct']) }}% this month
                </p>
            @endif
        </div>

        <div class="min-w-[148px] flex-1 rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
            <div class="mb-3 inline-flex size-10 items-center justify-center rounded-xl bg-sky-50 text-sky-600">
                <svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 10h16M6 6h12M8 14h8M10 18h4"/></svg>
            </div>
            <p class="text-xs font-medium text-gray-500">Total Seats</p>
            <p class="mt-1 text-2xl font-bold tabular-nums text-gray-900">{{ number_format($kpis['seats']) }}</p>
            <p class="mt-0.5 text-[11px] text-gray-400">Across all branches</p>
            @if ($kpis['seats_delta_pct'] !== null)
                <p class="mt-2 text-[11px] font-semibold {{ $kpis['seats_delta_pct'] >= 0 ? 'text-emerald-600' : 'text-red-600' }}">
                    {{ $kpis['seats_delta_pct'] >= 0 ? '↑' : '↓' }} {{ abs($kpis['seats_delta_pct']) }}% this month
                </p>
            @endif
        </div>

        <div class="min-w-[148px] flex-1 rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
            <div class="mb-3 inline-flex size-10 items-center justify-center rounded-xl bg-orange-50 text-orange-600">
                <svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M5 20v-9a2 2 0 012-2h2a2 2 0 012 2v9M9 20h6M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            </div>
            <p class="text-xs font-medium text-gray-500">Occupied Seats</p>
            <p class="mt-1 text-2xl font-bold tabular-nums text-gray-900">{{ number_format($kpis['occupied']) }}</p>
            <p class="mt-0.5 text-[11px] text-gray-400">Assigned seats</p>
            <p class="mt-2 text-[11px] font-semibold text-amber-600">{{ number_format($kpis['occupancy_pct'], 2) }}% occupancy</p>
        </div>

        <div class="min-w-[148px] flex-1 rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
            <div class="mb-3 inline-flex size-10 items-center justify-center rounded-xl bg-teal-50 text-teal-600">
                <svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 10h16M6 6h12M8 14h8M10 18h4"/></svg>
            </div>
            <p class="text-xs font-medium text-gray-500">Available Seats</p>
            <p class="mt-1 text-2xl font-bold tabular-nums text-gray-900">{{ number_format($kpis['available']) }}</p>
            <p class="mt-0.5 text-[11px] text-gray-400">Seats available</p>
            <p class="mt-2 text-[11px] font-semibold text-teal-600">{{ number_format($kpis['availability_pct'], 2) }}% availability</p>
        </div>

        <div class="min-w-[148px] flex-1 rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
            <div class="mb-3 inline-flex size-10 items-center justify-center rounded-xl bg-violet-50 text-violet-600">
                <svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
            </div>
            <p class="text-xs font-medium text-gray-500">Monthly Revenue</p>
            <p class="mt-1 text-2xl font-bold tabular-nums text-gray-900">₹{{ number_format($kpis['monthly_revenue']) }}</p>
            <p class="mt-0.5 text-[11px] text-gray-400">This month</p>
            @if ($kpis['revenue_delta_pct'] !== null)
                <p class="mt-2 text-[11px] font-semibold {{ $kpis['revenue_delta_pct'] >= 0 ? 'text-emerald-600' : 'text-red-600' }}">
                    {{ $kpis['revenue_delta_pct'] >= 0 ? '↑' : '↓' }} {{ abs($kpis['revenue_delta_pct']) }}% vs last month
                </p>
            @endif
        </div>
    </div>

    <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 px-5 py-4">
            <h2 class="text-base font-semibold text-gray-900">Branch Performance</h2>
            <a href="{{ route('branch.index') }}" class="text-sm font-semibold text-indigo-600 hover:text-indigo-700">View all branches →</a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[960px] text-left text-sm">
                <thead class="border-b border-gray-200 bg-gray-50 text-xs font-semibold uppercase tracking-wide text-gray-500">
                    <tr>
                        <th class="px-5 py-3">Branch</th>
                        <th class="px-3 py-3">Students</th>
                        <th class="px-3 py-3">Seats</th>
                        <th class="px-3 py-3">Occupied</th>
                        <th class="px-3 py-3">Available</th>
                        <th class="min-w-[11rem] px-3 py-3">Occupancy</th>
                        <th class="px-3 py-3">Revenue (This Month)</th>
                        <th class="px-3 py-3">Status</th>
                        <th class="px-5 py-3 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-gray-700">
                    <template x-for="row in pageRows()" :key="row.id">
                        <tr class="hover:bg-indigo-50/40">
                            <td class="px-5 py-3 font-medium text-gray-900" x-text="row.name"></td>
                            <td class="px-3 py-3" x-text="Number(row.students).toLocaleString('en-IN')"></td>
                            <td class="px-3 py-3" x-text="Number(row.seats).toLocaleString('en-IN')"></td>
                            <td class="px-3 py-3" x-text="Number(row.occupied).toLocaleString('en-IN')"></td>
                            <td class="px-3 py-3" x-text="Number(row.available).toLocaleString('en-IN')"></td>
                            <td class="px-3 py-3">
                                <div class="flex items-center gap-2">
                                    <div class="h-2 w-24 overflow-hidden rounded-full bg-gray-100">
                                        <div
                                            class="h-full rounded-full"
                                            :class="row.occupancy >= 70 ? 'bg-emerald-500' : (row.occupancy >= 40 ? 'bg-amber-500' : 'bg-rose-400')"
                                            :style="`width: ${occupancyBarWidth(row.occupancy)}`"
                                        ></div>
                                    </div>
                                    <span class="text-xs font-semibold text-gray-600" x-text="`${row.occupancy}%`"></span>
                                </div>
                            </td>
                            <td class="px-3 py-3 font-medium text-gray-900" x-text="`₹${Number(row.revenue || 0).toLocaleString('en-IN')}`"></td>
                            <td class="px-3 py-3">
                                <span
                                    class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold"
                                    :class="row.status === 'Active' ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700'"
                                    x-text="row.status"
                                ></span>
                            </td>
                            <td class="px-5 py-3 text-right">
                                <button
                                    type="button"
                                    @click="openBranch(row.id)"
                                    :disabled="openingId === row.id"
                                    class="inline-flex size-8 items-center justify-center rounded-lg border border-gray-200 text-gray-500 hover:border-indigo-200 hover:bg-indigo-50 hover:text-indigo-600 disabled:opacity-50"
                                    title="View branch dashboard"
                                >
                                    <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                </button>
                            </td>
                        </tr>
                    </template>
                    <tr x-show="rows.length === 0">
                        <td colspan="9" class="px-5 py-10 text-center text-gray-500">No branches found.</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="flex flex-wrap items-center justify-between gap-3 border-t border-gray-100 px-5 py-3 text-sm text-gray-600">
            <p>Total <span class="font-semibold text-gray-900" x-text="rows.length"></span> branches</p>
            <div class="flex items-center gap-1.5">
                <template x-for="p in pages()" :key="p">
                    <button
                        type="button"
                        @click="page = p"
                        class="inline-flex size-8 items-center justify-center rounded-lg text-xs font-semibold"
                        :class="page === p ? 'bg-indigo-600 text-white' : 'border border-gray-200 text-gray-600 hover:bg-gray-50'"
                        x-text="p"
                    ></button>
                </template>
                <button
                    type="button"
                    class="inline-flex h-8 items-center rounded-lg border border-gray-200 px-3 text-xs font-semibold text-gray-600 hover:bg-gray-50 disabled:opacity-40"
                    :disabled="page >= totalPages()"
                    @click="page = Math.min(totalPages(), page + 1)"
                >Next</button>
            </div>
        </div>
    </section>

    <div class="flex flex-col gap-4 md:flex-row md:items-stretch">
        <section class="flex min-w-0 flex-col overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm md:w-[60%]">
            <div class="flex items-center justify-between gap-3 border-b border-gray-100 px-5 py-4">
                <h2 class="text-base font-semibold text-gray-900">
                    Revenue Overview
                    <span class="font-normal text-gray-400">(Last {{ $revenueMonthsCount }} Months)</span>
                </h2>
                <div class="relative" @click.outside="revenueOpen = false">
                    <button
                        type="button"
                        @click="revenueOpen = !revenueOpen"
                        class="inline-flex items-center gap-1.5 rounded-md border border-gray-200 bg-white px-2.5 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50"
                    >
                        {{ $admin['revenue_months_label'] }}
                        <svg class="size-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div
                        x-show="revenueOpen"
                        x-cloak
                        x-transition
                        class="absolute right-0 z-20 mt-1 w-36 overflow-hidden rounded-lg border border-gray-200 bg-white py-1 shadow-lg"
                    >
                        @foreach ([3 => '3 Months', 6 => '6 Months', 12 => '12 Months'] as $months => $label)
                            <a
                                href="{{ route('dashboard', array_merge(request()->only(['from', 'to']), ['revenue_months' => $months])) }}"
                                @class([
                                    'block px-3 py-2 text-xs font-medium hover:bg-gray-50',
                                    'bg-indigo-50 text-indigo-700' => $revenueMonthsCount === $months,
                                    'text-gray-700' => $revenueMonthsCount !== $months,
                                ])
                            >{{ $label }}</a>
                        @endforeach
                    </div>
                </div>
            </div>
            <div class="flex flex-1 flex-col px-5 pb-5 pt-4">
                <div class="flex gap-3" style="height: {{ $chartHeight }}px">
                    <div class="flex w-16 shrink-0 flex-col justify-between py-1 text-[10px] font-medium text-gray-400">
                        @foreach ($revenueAxis as $tick)
                            <span class="tabular-nums">{{ $tick['label'] }}</span>
                        @endforeach
                    </div>
                    <div class="relative flex-1">
                        @foreach ($revenueAxis as $tick)
                            @if ($tick['pct'] > 0)
                                <div class="absolute left-0 right-0 border-t border-gray-100" style="bottom: {{ $tick['pct'] }}%"></div>
                            @endif
                        @endforeach
                        <div class="absolute inset-0 flex items-end gap-2 sm:gap-3">
                            @foreach ($revenueMonths as $month)
                                @php
                                    $barPx = $month['amount'] > 0
                                        ? max(8, (int) round(($month['amount'] / $revenueChartMax) * ($chartHeight - 24)))
                                        : 0;
                                @endphp
                                <div class="flex h-full min-w-0 flex-1 flex-col items-center justify-end gap-2">
                                    <div
                                        class="w-full max-w-[2.75rem] rounded-t-md bg-indigo-500"
                                        style="height: {{ $barPx }}px"
                                        title="{{ $month['label'] }}: ₹{{ number_format($month['amount']) }}"
                                    ></div>
                                    <span class="truncate text-[10px] font-medium text-gray-400">{{ $month['label'] }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                <div class="mt-4 flex items-center justify-between border-t border-gray-100 pt-3 text-sm">
                    <span class="text-gray-500">Total Revenue ({{ $revenueMonthsCount }} Months)</span>
                    <span class="text-lg font-bold text-gray-900">₹{{ number_format($admin['revenue_months_total']) }}</span>
                </div>
            </div>
        </section>

        <section class="flex min-w-0 flex-col overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm md:w-[40%]">
            <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
                <h2 class="text-base font-semibold text-gray-900">Attention Required</h2>
                <a href="{{ route('fees.index') }}" class="text-sm font-semibold text-indigo-600 hover:text-indigo-700">View all</a>
            </div>
            <div class="flex-1">
                @forelse ($attention as $item)
                    <a
                        href="{{ $item['url'] }}"
                        @class([
                            'flex items-start gap-3 border-b border-gray-100/80 px-5 py-4 transition-colors last:border-b-0 hover:opacity-95',
                            'bg-red-50' => $item['tone'] === 'red',
                            'bg-amber-50' => $item['tone'] === 'amber',
                            'bg-yellow-50' => $item['tone'] === 'yellow',
                            'bg-indigo-50' => $item['tone'] === 'indigo',
                            'bg-blue-50' => $item['tone'] === 'blue',
                        ])
                    >
                        <span @class([
                            'mt-0.5 inline-flex size-8 shrink-0 items-center justify-center rounded-full',
                            'bg-red-100 text-red-600' => $item['tone'] === 'red',
                            'bg-amber-100 text-amber-600' => $item['tone'] === 'amber',
                            'bg-yellow-100 text-yellow-700' => $item['tone'] === 'yellow',
                            'bg-indigo-100 text-indigo-600' => $item['tone'] === 'indigo',
                            'bg-blue-100 text-blue-600' => $item['tone'] === 'blue',
                        ])>
                            @if ($item['tone'] === 'blue')
                                <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                            @else
                                <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
                            @endif
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-semibold text-gray-900">{{ $item['title'] }}</p>
                            <p class="text-xs text-gray-500">{{ $item['detail'] }}</p>
                        </div>
                        <svg class="mt-1 size-4 shrink-0 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </a>
                @empty
                    <p class="px-5 py-10 text-center text-sm text-gray-500">Nothing needs attention right now.</p>
                @endforelse
            </div>
        </section>
    </div>
</div>
