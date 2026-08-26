<x-admin-layout>
    @if ($mode === 'admin')
        @include('dashboard.admin')
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
