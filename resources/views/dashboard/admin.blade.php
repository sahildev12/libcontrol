@php
    $kpis = $admin['kpis'];
    $planExpiry = $admin['plan_expiry'];
    $utilization = $admin['utilization'];
    $attention = $admin['attention'];
    $systemAlerts = $admin['system_alerts'];
    $planTotal = max(1, (int) $planExpiry['total']);
    $expiredPct = round(($planExpiry['expired'] / $planTotal) * 100, 1);
    $d13Pct = round(($planExpiry['days_1_3'] / $planTotal) * 100, 1);
    $d47Pct = round(($planExpiry['days_4_7'] / $planTotal) * 100, 1);
    $donutStyle = $planExpiry['total'] > 0
        ? "background: conic-gradient(#EF4444 0 {$expiredPct}%, #F59E0B {$expiredPct}% ".($expiredPct + $d13Pct)."%, #6366F1 ".($expiredPct + $d13Pct)."% 100%);"
        : 'background: #E5E7EB;';
@endphp

<div
    x-data="{
        page: 1,
        perPage: 5,
        rows: @js($admin['branches']),
        switchUrl: @js($admin['switchUrl']),
        openingId: null,
        totalPages() { return Math.max(1, Math.ceil(this.rows.length / this.perPage)); },
        pageRows() {
            const start = (this.page - 1) * this.perPage;
            return this.rows.slice(start, start + this.perPage);
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
            <h1 class="text-2xl font-bold text-gray-900">Dashboard</h1>
            <p class="mt-1 text-sm text-gray-600">System Overview</p>
        </div>
        <form method="get" action="{{ route('dashboard') }}" class="flex flex-wrap items-end gap-2">
            <div class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-2 shadow-sm">
                <svg class="size-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3M5 11h14M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                <input type="date" name="from" value="{{ $admin['from'] }}" class="border-0 p-0 text-sm text-gray-700 focus:ring-0">
                <span class="text-xs text-gray-400">–</span>
                <input type="date" name="to" value="{{ $admin['to'] }}" class="border-0 p-0 text-sm text-gray-700 focus:ring-0">
            </div>
            <button type="submit" class="inline-flex h-10 items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                Apply
            </button>
            <a href="{{ route('dashboard') }}" class="inline-flex h-10 items-center gap-2 rounded-lg bg-indigo-600 px-3.5 text-sm font-semibold text-white hover:bg-indigo-700">
                <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v6h6M20 20v-6h-6M5 19a9 9 0 0014-7M19 5a9 9 0 00-14 7"/></svg>
                Refresh
            </a>
        </form>
    </header>

    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-6">
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Total Branches</p>
                    <p class="mt-2 text-2xl font-bold text-gray-900">{{ number_format($kpis['branches']) }}</p>
                    <p class="mt-1 text-xs text-gray-500">Active branches</p>
                </div>
                <span class="inline-flex size-9 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600">
                    <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0H5m14 0h2M5 21H3m4-10h2m4 0h2"/></svg>
                </span>
            </div>
            @if ($kpis['branches_delta'] > 0)
                <p class="mt-3 text-xs font-semibold text-emerald-600">↑ {{ $kpis['branches_delta'] }} this month</p>
            @endif
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Total Students</p>
                    <p class="mt-2 text-2xl font-bold text-gray-900">{{ number_format($kpis['students']) }}</p>
                    <p class="mt-1 text-xs text-gray-500">Across all branches</p>
                </div>
                <span class="inline-flex size-9 items-center justify-center rounded-lg bg-violet-50 text-violet-600">
                    <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17 20h5v-2a4 4 0 00-4-4h-1M9 20H4v-2a4 4 0 014-4h1m6-4a4 4 0 11-8 0 4 4 0 018 0zm6 0a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                </span>
            </div>
            @if ($kpis['students_delta_pct'] !== null)
                <p class="mt-3 text-xs font-semibold {{ $kpis['students_delta_pct'] >= 0 ? 'text-emerald-600' : 'text-red-600' }}">
                    {{ $kpis['students_delta_pct'] >= 0 ? '↑' : '↓' }} {{ abs($kpis['students_delta_pct']) }}% this month
                </p>
            @endif
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Total Seats</p>
                    <p class="mt-2 text-2xl font-bold text-gray-900">{{ number_format($kpis['seats']) }}</p>
                    <p class="mt-1 text-xs text-gray-500">Across all branches</p>
                </div>
                <span class="inline-flex size-9 items-center justify-center rounded-lg bg-sky-50 text-sky-600">
                    <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 10h16M6 6h12M8 14h8M10 18h4"/></svg>
                </span>
            </div>
            @if ($kpis['seats_delta_pct'] !== null)
                <p class="mt-3 text-xs font-semibold {{ $kpis['seats_delta_pct'] >= 0 ? 'text-emerald-600' : 'text-red-600' }}">
                    {{ $kpis['seats_delta_pct'] >= 0 ? '↑' : '↓' }} {{ abs($kpis['seats_delta_pct']) }}% this month
                </p>
            @endif
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Occupied Seats</p>
                    <p class="mt-2 text-2xl font-bold text-gray-900">{{ number_format($kpis['occupied']) }}</p>
                    <p class="mt-1 text-xs text-gray-500">Assigned seats</p>
                </div>
                <span class="inline-flex size-9 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600">
                    <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                </span>
            </div>
            <p class="mt-3 text-xs font-semibold text-amber-600">{{ number_format($kpis['occupancy_pct'], 2) }}% occupancy</p>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Available Seats</p>
                    <p class="mt-2 text-2xl font-bold text-gray-900">{{ number_format($kpis['available']) }}</p>
                    <p class="mt-1 text-xs text-gray-500">Seats available</p>
                </div>
                <span class="inline-flex size-9 items-center justify-center rounded-lg bg-teal-50 text-teal-600">
                    <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 10h16M6 6h12M8 14h8M10 18h4"/></svg>
                </span>
            </div>
            <p class="mt-3 text-xs font-semibold text-teal-600">{{ number_format($kpis['availability_pct'], 2) }}% availability</p>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Monthly Revenue</p>
                    <p class="mt-2 text-2xl font-bold text-gray-900">₹{{ number_format($kpis['monthly_revenue']) }}</p>
                    <p class="mt-1 text-xs text-gray-500">Selected period</p>
                </div>
                <span class="inline-flex size-9 items-center justify-center rounded-lg bg-fuchsia-50 text-fuchsia-600">
                    <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8V6m0 12v-2m8-4a8 8 0 11-16 0 8 8 0 0116 0z"/></svg>
                </span>
            </div>
            @if ($kpis['revenue_delta_pct'] !== null)
                <p class="mt-3 text-xs font-semibold {{ $kpis['revenue_delta_pct'] >= 0 ? 'text-emerald-600' : 'text-red-600' }}">
                    {{ $kpis['revenue_delta_pct'] >= 0 ? '↑' : '↓' }} {{ abs($kpis['revenue_delta_pct']) }}% vs last month
                </p>
            @endif
        </div>
    </div>

    <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 px-5 py-4">
            <div>
                <h2 class="text-base font-semibold text-gray-900">Branch Performance</h2>
                <p class="text-xs text-gray-500">{{ $admin['from_label'] }} – {{ $admin['to_label'] }}</p>
            </div>
            <a href="{{ route('branch.index') }}" class="text-sm font-semibold text-indigo-600 hover:text-indigo-700">View all branches →</a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[960px] text-left text-sm">
                <thead class="bg-gray-50 text-xs font-semibold uppercase tracking-wide text-gray-500">
                    <tr>
                        <th class="px-5 py-3">Branch</th>
                        <th class="px-3 py-3">Students</th>
                        <th class="px-3 py-3">Seats</th>
                        <th class="px-3 py-3">Occupied</th>
                        <th class="px-3 py-3">Available</th>
                        <th class="min-w-[11rem] px-3 py-3">Occupancy</th>
                        <th class="px-3 py-3">Revenue</th>
                        <th class="px-3 py-3">Status</th>
                        <th class="px-5 py-3 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <template x-for="row in pageRows()" :key="row.id">
                        <tr class="hover:bg-gray-50/70">
                            <td class="px-5 py-3 font-medium text-gray-900" x-text="row.name"></td>
                            <td class="px-3 py-3 text-gray-700" x-text="row.students"></td>
                            <td class="px-3 py-3 text-gray-700" x-text="row.seats"></td>
                            <td class="px-3 py-3 text-gray-700" x-text="row.occupied"></td>
                            <td class="px-3 py-3 text-gray-700" x-text="row.available"></td>
                            <td class="px-3 py-3">
                                <div class="flex items-center gap-2">
                                    <div class="h-2 flex-1 overflow-hidden rounded-full bg-gray-100">
                                        <div
                                            class="h-full rounded-full"
                                            :class="row.occupancy >= 70 ? 'bg-emerald-500' : (row.occupancy >= 40 ? 'bg-amber-500' : 'bg-rose-400')"
                                            :style="`width: ${Math.min(100, row.occupancy)}%`"
                                        ></div>
                                    </div>
                                    <span class="w-12 text-right text-xs font-semibold text-gray-600" x-text="`${row.occupancy}%`"></span>
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
                                    class="inline-flex size-8 items-center justify-center rounded-lg border border-gray-200 text-gray-500 hover:bg-indigo-50 hover:text-indigo-600 disabled:opacity-50"
                                    title="Open branch dashboard"
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
            <div class="flex items-center gap-2">
                <button type="button" class="rounded-lg border border-gray-200 px-3 py-1.5 text-xs font-semibold disabled:opacity-40" :disabled="page <= 1" @click="page = Math.max(1, page - 1)">Prev</button>
                <span class="text-xs" x-text="`Page ${page} / ${totalPages()}`"></span>
                <button type="button" class="rounded-lg border border-gray-200 px-3 py-1.5 text-xs font-semibold disabled:opacity-40" :disabled="page >= totalPages()" @click="page = Math.min(totalPages(), page + 1)">Next</button>
            </div>
        </div>
    </section>

    <div class="grid gap-4 lg:grid-cols-3">
        <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
                <div>
                    <h2 class="text-base font-semibold text-gray-900">Attention Required</h2>
                    <p class="text-xs text-gray-500">Items that need follow-up</p>
                </div>
            </div>
            <div class="divide-y divide-gray-100">
                @forelse ($attention as $item)
                    <a href="{{ $item['url'] }}" class="flex items-start gap-3 px-5 py-4 hover:bg-gray-50">
                        <span @class([
                            'mt-0.5 inline-flex size-8 shrink-0 items-center justify-center rounded-full',
                            'bg-red-50 text-red-600' => $item['tone'] === 'red',
                            'bg-amber-50 text-amber-600' => $item['tone'] === 'amber',
                            'bg-yellow-50 text-yellow-700' => $item['tone'] === 'yellow',
                            'bg-indigo-50 text-indigo-600' => $item['tone'] === 'indigo',
                            'bg-blue-50 text-blue-600' => $item['tone'] === 'blue',
                        ])>
                            <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M12 5a7 7 0 100 14 7 7 0 000-14z"/></svg>
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-semibold text-gray-900">{{ $item['title'] }}</p>
                            <p class="text-xs text-gray-500">{{ $item['detail'] }}</p>
                        </div>
                        <svg class="mt-1 size-4 shrink-0 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </a>
                @empty
                    <p class="px-5 py-10 text-center text-sm text-gray-500">Nothing needs attention right now.</p>
                @endforelse
            </div>
        </section>

        <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <div class="border-b border-gray-100 px-5 py-4">
                <h2 class="text-base font-semibold text-gray-900">Plan Expiry Overview</h2>
                <p class="text-xs text-gray-500">Based on active seat plans</p>
            </div>
            <div class="flex flex-col items-center gap-5 px-5 py-6 sm:flex-row sm:items-center">
                <div class="relative size-32 shrink-0 rounded-full" style="{{ $donutStyle }}">
                    <div class="absolute inset-3 flex flex-col items-center justify-center rounded-full bg-white">
                        <p class="text-xl font-bold text-gray-900">{{ number_format($planExpiry['total']) }}</p>
                        <p class="text-[10px] font-semibold uppercase tracking-wide text-gray-400">Plans</p>
                    </div>
                </div>
                <div class="w-full space-y-3 text-sm">
                    <div class="flex items-center justify-between gap-3">
                        <span class="inline-flex items-center gap-2 text-gray-700"><span class="size-2.5 rounded-full bg-red-500"></span> Expired</span>
                        <span class="font-semibold text-gray-900">{{ number_format($planExpiry['expired']) }}</span>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <span class="inline-flex items-center gap-2 text-gray-700"><span class="size-2.5 rounded-full bg-amber-500"></span> Expiring 1–3 days</span>
                        <span class="font-semibold text-gray-900">{{ number_format($planExpiry['days_1_3']) }}</span>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <span class="inline-flex items-center gap-2 text-gray-700"><span class="size-2.5 rounded-full bg-indigo-500"></span> Expiring 4–7 days</span>
                        <span class="font-semibold text-gray-900">{{ number_format($planExpiry['days_4_7']) }}</span>
                    </div>
                </div>
            </div>
        </section>

        <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <div class="border-b border-gray-100 px-5 py-4">
                <h2 class="text-base font-semibold text-gray-900">Seat Utilization by Branch</h2>
                <p class="text-xs text-gray-500">
                    Avg {{ number_format($utilization['average'], 1) }}%
                    @if ($utilization['highest'])
                        · Highest {{ $utilization['highest']['name'] }} ({{ $utilization['highest']['occupancy'] }}%)
                    @endif
                    @if ($utilization['lowest'])
                        · Lowest {{ $utilization['lowest']['name'] }} ({{ $utilization['lowest']['occupancy'] }}%)
                    @endif
                </p>
            </div>
            <div class="divide-y divide-gray-100">
                @forelse ($utilization['rows'] as $row)
                    <div class="px-5 py-3">
                        <div class="flex items-center justify-between gap-3">
                            <p class="truncate text-sm font-medium text-gray-900">{{ $row['name'] }}</p>
                            <p class="shrink-0 text-xs font-semibold text-gray-600">{{ $row['occupancy'] }}%</p>
                        </div>
                        <div class="mt-2 h-2 overflow-hidden rounded-full bg-gray-100">
                            <div
                                @class([
                                    'h-full rounded-full',
                                    'bg-emerald-500' => $row['occupancy'] >= 70,
                                    'bg-amber-500' => $row['occupancy'] >= 40 && $row['occupancy'] < 70,
                                    'bg-rose-400' => $row['occupancy'] < 40,
                                ])
                                style="width: {{ min(100, $row['occupancy']) }}%"
                            ></div>
                        </div>
                        <p class="mt-1 text-xs text-gray-500">{{ $row['occupied'] }} / {{ $row['seats'] }} seats occupied</p>
                    </div>
                @empty
                    <p class="px-5 py-10 text-center text-sm text-gray-500">No seat data yet.</p>
                @endforelse
            </div>
        </section>
    </div>

    @if (count($systemAlerts) > 0)
        <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <div class="border-b border-gray-100 px-5 py-3">
                <h2 class="text-sm font-semibold text-gray-900">System Alerts</h2>
            </div>
            <div class="flex flex-wrap gap-2 px-5 py-4">
                @foreach ($systemAlerts as $alert)
                    <span @class([
                        'inline-flex items-center rounded-full px-3 py-1.5 text-xs font-semibold',
                        'bg-red-50 text-red-700' => $alert['tone'] === 'red',
                        'bg-amber-50 text-amber-700' => $alert['tone'] === 'amber',
                        'bg-cyan-50 text-cyan-700' => $alert['tone'] === 'cyan',
                    ])>{{ $alert['label'] }}</span>
                @endforeach
            </div>
        </section>
    @endif
</div>
