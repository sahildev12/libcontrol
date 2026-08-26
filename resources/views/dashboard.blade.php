<x-admin-layout>
    @if ($mode === 'admin')
        @php
            $kpis = $admin['kpis'];
            $branches = $admin['branches'];
            $revenueMonths = $admin['revenue_months'];
            $attention = $admin['attention'];
            $maxRevenue = max(1, collect($revenueMonths)->max('amount') ?: 1);
            $totalSixMonthRevenue = collect($revenueMonths)->sum('amount');
        @endphp

        <div
            x-data="{
                page: 1,
                perPage: 5,
                rows: @js($branches),
                totalPages() { return Math.max(1, Math.ceil(this.rows.length / this.perPage)); },
                pageRows() {
                    const start = (this.page - 1) * this.perPage;
                    return this.rows.slice(start, start + this.perPage);
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
                    <div>
                        <label class="block text-[11px] font-semibold uppercase tracking-wide text-gray-400">From</label>
                        <input type="date" name="from" value="{{ $admin['from'] }}" class="mt-1 rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm">
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold uppercase tracking-wide text-gray-400">To</label>
                        <input type="date" name="to" value="{{ $admin['to'] }}" class="mt-1 rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm">
                    </div>
                    <button type="submit" class="inline-flex h-10 items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                        Apply
                    </button>
                    <a href="{{ route('dashboard') }}" class="inline-flex h-10 items-center gap-2 rounded-lg bg-indigo-600 px-3 text-sm font-semibold text-white hover:bg-indigo-700">
                        Refresh
                    </a>
                </form>
            </header>

            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-6">
                <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Total Branches</p>
                    <p class="mt-2 text-3xl font-bold text-gray-900">{{ number_format($kpis['branches']) }}</p>
                    <p class="mt-1 text-xs text-gray-500">Active branches</p>
                    <p class="mt-3 text-xs font-semibold text-emerald-600">↑ {{ $kpis['branches_delta'] }} this month</p>
                </div>
                <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Total Students</p>
                    <p class="mt-2 text-3xl font-bold text-gray-900">{{ number_format($kpis['students']) }}</p>
                    <p class="mt-1 text-xs text-gray-500">Across all branches</p>
                    <p class="mt-3 text-xs font-semibold {{ ($kpis['students_delta_pct'] ?? 0) >= 0 ? 'text-emerald-600' : 'text-red-600' }}">
                        {{ ($kpis['students_delta_pct'] ?? 0) >= 0 ? '↑' : '↓' }}
                        {{ abs($kpis['students_delta_pct'] ?? 0) }}% this month
                    </p>
                </div>
                <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Total Seats</p>
                    <p class="mt-2 text-3xl font-bold text-gray-900">{{ number_format($kpis['seats']) }}</p>
                    <p class="mt-1 text-xs text-gray-500">Across all branches</p>
                    <p class="mt-3 text-xs font-semibold {{ ($kpis['seats_delta_pct'] ?? 0) >= 0 ? 'text-emerald-600' : 'text-red-600' }}">
                        {{ ($kpis['seats_delta_pct'] ?? 0) >= 0 ? '↑' : '↓' }}
                        {{ abs($kpis['seats_delta_pct'] ?? 0) }}% this month
                    </p>
                </div>
                <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Occupied Seats</p>
                    <p class="mt-2 text-3xl font-bold text-gray-900">{{ number_format($kpis['occupied']) }}</p>
                    <p class="mt-1 text-xs text-gray-500">Assigned seats</p>
                    <p class="mt-3 text-xs font-semibold text-amber-600">{{ number_format($kpis['occupancy_pct'], 2) }}% occupancy</p>
                </div>
                <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Available Seats</p>
                    <p class="mt-2 text-3xl font-bold text-gray-900">{{ number_format($kpis['available']) }}</p>
                    <p class="mt-1 text-xs text-gray-500">Seats available</p>
                    <p class="mt-3 text-xs font-semibold text-cyan-600">{{ number_format($kpis['availability_pct'], 2) }}% availability</p>
                </div>
                <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Monthly Revenue</p>
                    <p class="mt-2 text-3xl font-bold text-gray-900">₹{{ number_format($kpis['monthly_revenue']) }}</p>
                    <p class="mt-1 text-xs text-gray-500">Selected period</p>
                    <p class="mt-3 text-xs font-semibold {{ ($kpis['revenue_delta_pct'] ?? 0) >= 0 ? 'text-emerald-600' : 'text-red-600' }}">
                        {{ ($kpis['revenue_delta_pct'] ?? 0) >= 0 ? '↑' : '↓' }}
                        {{ abs($kpis['revenue_delta_pct'] ?? 0) }}% vs last month
                    </p>
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
                    <table class="w-full min-w-[900px] text-left text-sm">
                        <thead class="bg-gray-50 text-xs font-semibold uppercase tracking-wide text-gray-500">
                            <tr>
                                <th class="px-5 py-3">Branch</th>
                                <th class="px-4 py-3">Students</th>
                                <th class="px-4 py-3">Seats</th>
                                <th class="px-4 py-3">Occupied</th>
                                <th class="px-4 py-3">Available</th>
                                <th class="px-4 py-3 min-w-[10rem]">Occupancy</th>
                                <th class="px-4 py-3">Revenue</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-5 py-3 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <template x-for="row in pageRows()" :key="row.id">
                                <tr class="hover:bg-gray-50/80">
                                    <td class="px-5 py-3 font-medium text-gray-900" x-text="row.name"></td>
                                    <td class="px-4 py-3 text-gray-700" x-text="row.students"></td>
                                    <td class="px-4 py-3 text-gray-700" x-text="row.seats"></td>
                                    <td class="px-4 py-3 text-gray-700" x-text="row.occupied"></td>
                                    <td class="px-4 py-3 text-gray-700" x-text="row.available"></td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-2">
                                            <div class="h-2 flex-1 overflow-hidden rounded-full bg-gray-100">
                                                <div class="h-full rounded-full bg-cyan-500" :style="`width: ${Math.min(100, row.occupancy)}%`"></div>
                                            </div>
                                            <span class="w-12 text-right text-xs font-semibold text-gray-600" x-text="`${row.occupancy}%`"></span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 font-medium text-gray-900" x-text="`₹${Number(row.revenue || 0).toLocaleString('en-IN')}`"></td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">Active</span>
                                    </td>
                                    <td class="px-5 py-3 text-right">
                                        <a :href="`{{ route('branch.index') }}`" class="inline-flex size-8 items-center justify-center rounded-lg border border-gray-200 text-gray-500 hover:bg-gray-50" title="View branches">
                                            <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                        </a>
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

            <div class="grid gap-4 lg:grid-cols-2">
                <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                    <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
                        <div>
                            <h2 class="text-base font-semibold text-gray-900">Revenue Overview</h2>
                            <p class="text-xs text-gray-500">Last 6 months</p>
                        </div>
                    </div>
                    <div class="flex h-56 items-end gap-3 px-5 pb-4 pt-6">
                        @foreach ($revenueMonths as $month)
                            @php
                                $height = max(8, (int) round(($month['amount'] / $maxRevenue) * 100));
                            @endphp
                            <div class="flex flex-1 flex-col items-center gap-2">
                                <div class="flex h-40 w-full items-end justify-center">
                                    <div class="w-full max-w-[2.5rem] rounded-t-md bg-indigo-500/90" style="height: {{ $height }}%" title="₹{{ number_format($month['amount']) }}"></div>
                                </div>
                                <p class="text-[10px] font-medium text-gray-500">{{ $month['label'] }}</p>
                            </div>
                        @endforeach
                    </div>
                    <div class="border-t border-gray-100 px-5 py-3 text-sm text-gray-600">
                        Total Revenue (6 Months):
                        <span class="font-semibold text-gray-900">₹{{ number_format($totalSixMonthRevenue) }}</span>
                    </div>
                </section>

                <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                    <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
                        <div>
                            <h2 class="text-base font-semibold text-gray-900">Attention Required</h2>
                            <p class="text-xs text-gray-500">Items that need follow-up</p>
                        </div>
                        <a href="{{ route('fees.index') }}" class="text-sm font-semibold text-indigo-600 hover:text-indigo-700">View all</a>
                    </div>
                    <div class="divide-y divide-gray-100">
                        <a href="{{ route('fees.index') }}" class="flex items-start gap-3 px-5 py-4 hover:bg-gray-50">
                            <span class="mt-0.5 inline-flex size-8 shrink-0 items-center justify-center rounded-full bg-red-50 text-red-600">
                                <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M12 5a7 7 0 100 14 7 7 0 000-14z"/></svg>
                            </span>
                            <div>
                                <p class="text-sm font-semibold text-gray-900">{{ $attention['expired_plans'] }} expired plans</p>
                                <p class="text-xs text-gray-500">Across {{ $attention['expired_branches'] }} branches</p>
                            </div>
                        </a>
                        <a href="{{ route('fees.index') }}" class="flex items-start gap-3 px-5 py-4 hover:bg-gray-50">
                            <span class="mt-0.5 inline-flex size-8 shrink-0 items-center justify-center rounded-full bg-amber-50 text-amber-600">
                                <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </span>
                            <div>
                                <p class="text-sm font-semibold text-gray-900">{{ $attention['expiring_plans'] }} plans expiring in next 7 days</p>
                                <p class="text-xs text-gray-500">Across {{ $attention['expiring_branches'] }} branches</p>
                            </div>
                        </a>
                        @if ($attention['low_occupancy'])
                            <a href="{{ route('seats.index') }}" class="flex items-start gap-3 px-5 py-4 hover:bg-gray-50">
                                <span class="mt-0.5 inline-flex size-8 shrink-0 items-center justify-center rounded-full bg-yellow-50 text-yellow-700">
                                    <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M5 6h14M7 14h10M9 18h6"/></svg>
                                </span>
                                <div>
                                    <p class="text-sm font-semibold text-gray-900">{{ $attention['low_occupancy']['available'] }} seats still available</p>
                                    <p class="text-xs text-gray-500">{{ $attention['low_occupancy']['name'] }} · {{ $attention['low_occupancy']['occupancy'] }}% occupancy</p>
                                </div>
                            </a>
                        @endif
                        <div class="flex items-start gap-3 px-5 py-4">
                            <span class="mt-0.5 inline-flex size-8 shrink-0 items-center justify-center rounded-full bg-indigo-50 text-indigo-600">
                                <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                            </span>
                            <div>
                                <p class="text-sm font-semibold text-gray-900">{{ $attention['inactive_admins'] }} branch admins inactive</p>
                                <p class="text-xs text-gray-500">No activity in the last 30 days</p>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    @else
        @php
            $stats = $branch['stats'];
            $today = $branch['today'];
        @endphp

        <header class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Dashboard</h1>
                <p class="mt-1 text-sm text-gray-600">{{ $scopeLabel }} · Overview</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('seats.index') }}" class="inline-flex h-9 items-center rounded-lg bg-indigo-600 px-4 text-sm font-semibold text-white hover:bg-indigo-700">Open Seat Map</a>
                <a href="{{ route('halls.index') }}" class="inline-flex h-9 items-center rounded-lg border border-gray-200 bg-white px-4 text-sm font-semibold text-gray-700 hover:bg-gray-50">Manage Halls</a>
            </div>
        </header>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500">Total Seats</p>
                        <p class="mt-2 text-3xl font-bold text-gray-900">{{ number_format($stats['total_seats']) }}</p>
                        <p class="mt-1 text-xs text-gray-500">All seats in branch</p>
                    </div>
                    <span class="inline-flex size-10 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600">
                        <svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 10h16M6 6h12M8 14h8M10 18h4"/></svg>
                    </span>
                </div>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500">Occupied</p>
                        <p class="mt-2 text-3xl font-bold text-gray-900">{{ number_format($stats['occupied'] + $stats['expiring_soon'] + $stats['on_trial']) }}</p>
                        <p class="mt-1 text-xs text-gray-500">Active assignments</p>
                    </div>
                    <span class="inline-flex size-10 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600">
                        <svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    </span>
                </div>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500">Vacant</p>
                        <p class="mt-2 text-3xl font-bold text-gray-900">{{ number_format($stats['available']) }}</p>
                        <p class="mt-1 text-xs text-gray-500">Ready to assign</p>
                    </div>
                    <span class="inline-flex size-10 items-center justify-center rounded-xl bg-orange-50 text-orange-600">
                        <svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 10h16M6 6h12M8 14h8M10 18h4"/></svg>
                    </span>
                </div>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500">On Trial</p>
                        <p class="mt-2 text-3xl font-bold text-gray-900">{{ number_format($stats['on_trial']) }}</p>
                        <p class="mt-1 text-xs text-gray-500">Trial allocations</p>
                    </div>
                    <span class="inline-flex size-10 items-center justify-center rounded-xl bg-sky-50 text-sky-600">
                        <svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </span>
                </div>
            </div>
        </div>

        <div class="grid gap-4 lg:grid-cols-5">
            <section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm lg:col-span-3">
                <h2 class="text-base font-semibold text-gray-900">Today’s Overview</h2>
                <div class="mt-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">New Enquiries</p>
                        <p class="mt-1 text-2xl font-bold text-gray-900">{{ number_format($today['enquiries']) }}</p>
                        @if ($today['enquiries_delta_pct'] !== null)
                            <p class="mt-1 text-xs font-semibold {{ $today['enquiries_delta_pct'] >= 0 ? 'text-emerald-600' : 'text-red-600' }}">
                                {{ $today['enquiries_delta_pct'] >= 0 ? '+' : '' }}{{ $today['enquiries_delta_pct'] }}% vs yesterday
                            </p>
                        @endif
                    </div>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">New Students</p>
                        <p class="mt-1 text-2xl font-bold text-gray-900">{{ number_format($today['students']) }}</p>
                        @if ($today['students_delta_pct'] !== null)
                            <p class="mt-1 text-xs font-semibold {{ $today['students_delta_pct'] >= 0 ? 'text-emerald-600' : 'text-red-600' }}">
                                {{ $today['students_delta_pct'] >= 0 ? '+' : '' }}{{ $today['students_delta_pct'] }}% vs yesterday
                            </p>
                        @endif
                    </div>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Today’s Revenue</p>
                        <p class="mt-1 text-2xl font-bold text-gray-900">₹{{ number_format($today['revenue']) }}</p>
                        @if ($today['revenue_delta_pct'] !== null)
                            <p class="mt-1 text-xs font-semibold {{ $today['revenue_delta_pct'] >= 0 ? 'text-emerald-600' : 'text-red-600' }}">
                                {{ $today['revenue_delta_pct'] >= 0 ? '+' : '' }}{{ $today['revenue_delta_pct'] }}% vs yesterday
                            </p>
                        @endif
                    </div>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Expiring Plans</p>
                        <p class="mt-1 text-2xl font-bold text-gray-900">{{ number_format($today['expiring_plans']) }}</p>
                        <p class="mt-1 text-xs text-gray-500">Next 7 days</p>
                    </div>
                </div>
            </section>

            <section class="rounded-xl border border-gray-200 bg-white shadow-sm lg:col-span-2">
                <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
                    <h2 class="text-base font-semibold text-gray-900">Recent Enquiries</h2>
                    <a href="{{ route('enquiries.index') }}" class="text-sm font-semibold text-indigo-600 hover:text-indigo-700">View all</a>
                </div>
                <div class="divide-y divide-gray-100">
                    @forelse ($branch['recent_enquiries'] as $enquiry)
                        <div class="flex items-start gap-3 px-5 py-3">
                            <span class="inline-flex size-9 shrink-0 items-center justify-center rounded-full bg-indigo-50 text-sm font-semibold text-indigo-700">{{ $enquiry['initial'] }}</span>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center justify-between gap-2">
                                    <p class="truncate text-sm font-medium text-gray-900">{{ $enquiry['name'] }}</p>
                                    <span class="shrink-0 text-[11px] text-gray-400">{{ $enquiry['ago'] }}</span>
                                </div>
                                <p class="truncate text-xs text-gray-500">{{ $enquiry['message'] }}</p>
                            </div>
                            <span class="shrink-0 rounded-full px-2 py-0.5 text-[11px] font-semibold
                                {{ $enquiry['status'] === 'new' ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }}">
                                {{ $enquiry['status_label'] }}
                            </span>
                        </div>
                    @empty
                        <p class="px-5 py-8 text-center text-sm text-gray-500">No enquiries yet.</p>
                    @endforelse
                </div>
            </section>
        </div>

        <div class="grid gap-4 lg:grid-cols-2">
            <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
                    <h2 class="text-base font-semibold text-gray-900">Expiring Plans (Next 7 Days)</h2>
                    <a href="{{ route('fees.index') }}" class="text-sm font-semibold text-indigo-600 hover:text-indigo-700">View all</a>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[520px] text-left text-sm">
                        <thead class="bg-gray-50 text-xs font-semibold uppercase tracking-wide text-gray-500">
                            <tr>
                                <th class="px-5 py-3">Student</th>
                                <th class="px-3 py-3">Plan</th>
                                <th class="px-3 py-3">Expires On</th>
                                <th class="px-3 py-3">Amount</th>
                                <th class="px-5 py-3">Days Left</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($branch['expiring_plans'] as $plan)
                                <tr>
                                    <td class="px-5 py-3">
                                        <p class="font-medium text-gray-900">{{ $plan['student_name'] }}</p>
                                        <p class="text-xs text-gray-500">{{ $plan['student_code'] }}</p>
                                    </td>
                                    <td class="px-3 py-3 text-gray-600">{{ $plan['plan_id'] }}</td>
                                    <td class="px-3 py-3 text-gray-600">{{ $plan['expires_on'] }}</td>
                                    <td class="px-3 py-3 font-medium text-gray-900">₹{{ number_format($plan['amount']) }}</td>
                                    <td class="px-5 py-3">
                                        <span @class([
                                            'inline-flex rounded-full px-2.5 py-1 text-xs font-semibold',
                                            'bg-red-50 text-red-700' => ($plan['days_left'] ?? 99) <= 2,
                                            'bg-amber-50 text-amber-700' => ($plan['days_left'] ?? 99) > 2,
                                        ])>{{ $plan['days_label'] }}</span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-5 py-10 text-center text-gray-500">No plans expiring soon.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-gray-100 px-5 py-3 text-xs text-gray-500">
                    Total {{ count($branch['expiring_plans']) }} records
                </div>
            </section>

            <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
                    <h2 class="text-base font-semibold text-gray-900">Active Seat Allocations</h2>
                    <a href="{{ route('seats.index') }}" class="text-sm font-semibold text-indigo-600 hover:text-indigo-700">View all</a>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[520px] text-left text-sm">
                        <thead class="bg-gray-50 text-xs font-semibold uppercase tracking-wide text-gray-500">
                            <tr>
                                <th class="px-5 py-3">Student</th>
                                <th class="px-3 py-3">Hall</th>
                                <th class="px-3 py-3">Seat</th>
                                <th class="px-3 py-3">Plan</th>
                                <th class="px-3 py-3">Since</th>
                                <th class="px-5 py-3">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($branch['active_allocations'] as $row)
                                <tr>
                                    <td class="px-5 py-3">
                                        <p class="font-medium text-gray-900">{{ $row['student_name'] }}</p>
                                        <p class="text-xs text-gray-500">{{ $row['student_code'] }}</p>
                                    </td>
                                    <td class="px-3 py-3 text-gray-600">{{ $row['hall_name'] }}</td>
                                    <td class="px-3 py-3 text-gray-600">{{ $row['seat_number'] }}</td>
                                    <td class="px-3 py-3 text-gray-600">{{ $row['plan_id'] }}</td>
                                    <td class="px-3 py-3 text-gray-600">{{ $row['since'] }}</td>
                                    <td class="px-5 py-3">
                                        <span class="inline-flex rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">{{ $row['status'] }}</span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-5 py-10 text-center text-gray-500">No active allocations.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-gray-100 px-5 py-3 text-xs text-gray-500">
                    Total {{ count($branch['active_allocations']) }} records
                </div>
            </section>
        </div>
    @endif
</x-admin-layout>
