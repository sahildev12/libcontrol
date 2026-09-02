@php
    $stats = $branch['stats'];
    $today = $branch['today'];
    $occupiedActive = $stats['occupied'] + $stats['expiring_soon'];
@endphp

<div class="space-y-6">
    <header class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-gray-900">Dashboard</h1>
            <p class="mt-1 text-sm text-gray-500">{{ $scopeLabel }} · Overview</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('seats.index') }}" class="inline-flex h-10 items-center gap-2 rounded-lg bg-indigo-600 px-4 text-sm font-semibold text-white hover:bg-indigo-700">
                <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A2 2 0 013 15.382V6.618a2 2 0 011.553-1.894L9 2m0 18l6-3m-6 3V2m6 15l5.447 2.724A2 2 0 0021 17.382V8.618a2 2 0 00-1.553-1.894L15 4m0 13V4m0 0L9 7"/></svg>
                Open Seat Map
            </a>
            <a href="{{ route('halls.index') }}" class="inline-flex h-10 items-center gap-2 rounded-lg border border-gray-200 bg-white px-4 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50">
                <svg class="size-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0H5m14 0h2M5 21H3m4-10h2m4 0h2"/></svg>
                Manage Halls
            </a>
        </div>
    </header>

    {{-- KPI cards: single row --}}
    <div class="flex gap-3 overflow-x-auto pb-1 md:overflow-visible">
        <div class="min-w-[160px] flex-1 rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <p class="text-xs font-medium text-gray-500">Total Seats</p>
                    <p class="mt-1 text-2xl font-bold tabular-nums text-gray-900">{{ number_format($stats['total_seats']) }}</p>
                    <p class="mt-0.5 text-[11px] text-gray-400">All seats in branch</p>
                </div>
                <span class="inline-flex size-10 shrink-0 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600">
                    <svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 10h16M6 6h12M8 14h8M10 18h4"/></svg>
                </span>
            </div>
        </div>
        <div class="min-w-[160px] flex-1 rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <p class="text-xs font-medium text-gray-500">Occupied</p>
                    <p class="mt-1 text-2xl font-bold tabular-nums text-gray-900">{{ number_format($occupiedActive) }}</p>
                    <p class="mt-0.5 text-[11px] text-gray-400">Active assignments</p>
                </div>
                <span class="inline-flex size-10 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600">
                    <svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                </span>
            </div>
        </div>
        <div class="min-w-[160px] flex-1 rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <p class="text-xs font-medium text-gray-500">Vacant</p>
                    <p class="mt-1 text-2xl font-bold tabular-nums text-gray-900">{{ number_format($stats['available']) }}</p>
                    <p class="mt-0.5 text-[11px] text-gray-400">Ready to assign</p>
                </div>
                <span class="inline-flex size-10 shrink-0 items-center justify-center rounded-xl bg-orange-50 text-orange-600">
                    <svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 10h16M6 6h12M8 14h8M10 18h4"/></svg>
                </span>
            </div>
        </div>
        <div class="min-w-[160px] flex-1 rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <p class="text-xs font-medium text-gray-500">On Trial</p>
                    <p class="mt-1 text-2xl font-bold tabular-nums text-gray-900">{{ number_format($stats['on_trial']) }}</p>
                    <p class="mt-0.5 text-[11px] text-gray-400">Trial allocations</p>
                </div>
                <span class="inline-flex size-10 shrink-0 items-center justify-center rounded-xl bg-sky-50 text-sky-600">
                    <svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </span>
            </div>
        </div>
    </div>

    {{-- Today's Overview @if enquiries enabled + Recent Enquiries --}}
    <div class="flex flex-col gap-4 md:flex-row md:items-start">
        <section class="min-w-0 rounded-xl border border-gray-200 bg-white shadow-sm {{ config('libspace.modules.enquiries') ? 'md:w-[58%] lg:w-[60%]' : 'w-full' }}">
            <div class="border-b border-gray-100 px-4 py-3">
                <h2 class="text-sm font-semibold text-gray-900">Today's Overview</h2>
            </div>
            <div class="grid grid-cols-2 gap-3 p-4 {{ config('libspace.modules.enquiries') ? 'lg:grid-cols-4' : 'lg:grid-cols-3' }}">
                @if (config('libspace.modules.enquiries'))
                <div class="rounded-lg border border-gray-100 bg-gray-50/70 p-3">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <p class="text-[10px] font-semibold uppercase tracking-wide text-gray-400">New Enquiries</p>
                            <p class="mt-0.5 text-xl font-bold tabular-nums leading-none text-gray-900">{{ number_format($today['enquiries']) }}</p>
                            @if ($today['enquiries_delta_pct'] !== null)
                                <p class="mt-1 text-[11px] font-semibold {{ $today['enquiries_delta_pct'] >= 0 ? 'text-emerald-600' : 'text-red-600' }}">
                                    {{ $today['enquiries_delta_pct'] >= 0 ? '↑' : '↓' }} {{ abs($today['enquiries_delta_pct']) }}% vs yesterday
                                </p>
                            @else
                                <p class="mt-1 text-[11px] text-gray-400">vs yesterday</p>
                            @endif
                        </div>
                        <span class="inline-flex size-8 shrink-0 items-center justify-center rounded-lg bg-emerald-100 text-emerald-600">
                            <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-4l-4 4z"/></svg>
                        </span>
                    </div>
                </div>
                @endif
                <div class="rounded-lg border border-gray-100 bg-gray-50/70 p-3">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <p class="text-[10px] font-semibold uppercase tracking-wide text-gray-400">New Students</p>
                            <p class="mt-0.5 text-xl font-bold tabular-nums leading-none text-gray-900">{{ number_format($today['students']) }}</p>
                            @if ($today['students_delta_pct'] !== null)
                                <p class="mt-1 text-[11px] font-semibold {{ $today['students_delta_pct'] >= 0 ? 'text-emerald-600' : 'text-red-600' }}">
                                    {{ $today['students_delta_pct'] >= 0 ? '↑' : '↓' }} {{ abs($today['students_delta_pct']) }}% vs yesterday
                                </p>
                            @else
                                <p class="mt-1 text-[11px] text-gray-400">vs yesterday</p>
                            @endif
                        </div>
                        <span class="inline-flex size-8 shrink-0 items-center justify-center rounded-lg bg-indigo-100 text-indigo-600">
                            <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a4 4 0 00-4-4h-1M9 20H4v-2a4 4 0 014-4h1m6-4a4 4 0 11-8 0 4 4 0 018 0zm6 0a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        </span>
                    </div>
                </div>
                <div class="rounded-lg border border-gray-100 bg-gray-50/70 p-3">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <p class="text-[10px] font-semibold uppercase tracking-wide text-gray-400">Today's Revenue</p>
                            <p class="mt-0.5 text-xl font-bold tabular-nums leading-none text-gray-900">₹{{ number_format($today['revenue']) }}</p>
                            @if ($today['revenue_delta_pct'] !== null)
                                <p class="mt-1 text-[11px] font-semibold {{ $today['revenue_delta_pct'] >= 0 ? 'text-emerald-600' : 'text-red-600' }}">
                                    {{ $today['revenue_delta_pct'] >= 0 ? '↑' : '↓' }} {{ abs($today['revenue_delta_pct']) }}% vs yesterday
                                </p>
                            @else
                                <p class="mt-1 text-[11px] text-gray-400">vs yesterday</p>
                            @endif
                        </div>
                        <span class="inline-flex size-8 shrink-0 items-center justify-center rounded-lg bg-violet-100 text-violet-600">
                            <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8V6m0 12v-2m8-4a8 8 0 11-16 0 8 8 0 0116 0z"/></svg>
                        </span>
                    </div>
                </div>
                <div class="rounded-lg border border-gray-100 bg-gray-50/70 p-3">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <p class="text-[10px] font-semibold uppercase tracking-wide text-gray-400">Expiring Plans</p>
                            <p class="mt-0.5 text-xl font-bold tabular-nums leading-none text-gray-900">{{ number_format($today['expiring_plans']) }}</p>
                            <p class="mt-1 text-[11px] text-gray-500">Next 7 days</p>
                        </div>
                        <span class="inline-flex size-8 shrink-0 items-center justify-center rounded-lg bg-amber-100 text-amber-600">
                            <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </span>
                    </div>
                </div>
            </div>
        </section>

        @if (config('libspace.modules.enquiries'))
        <section class="min-w-0 flex-1 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
                <h2 class="text-base font-semibold text-gray-900">Recent Enquiries</h2>
                <a href="{{ route('enquiries.index') }}" class="text-sm font-semibold text-indigo-600 hover:text-indigo-700">View all</a>
            </div>
            <div class="divide-y divide-gray-100">
                @forelse ($branch['recent_enquiries'] as $enquiry)
                    <div class="flex items-start gap-3 px-5 py-3.5">
                        <span class="inline-flex size-9 shrink-0 items-center justify-center rounded-full bg-indigo-50 text-sm font-semibold text-indigo-700">{{ $enquiry['initial'] }}</span>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-start justify-between gap-2">
                                <p class="truncate text-sm font-semibold text-gray-900">{{ $enquiry['name'] }}</p>
                                <span class="shrink-0 text-[11px] text-gray-400">{{ $enquiry['ago'] }}</span>
                            </div>
                            <p class="truncate text-xs text-gray-500">{{ $enquiry['detail'] }}</p>
                        </div>
                        <span @class([
                            'shrink-0 rounded-full px-2.5 py-1 text-[11px] font-semibold',
                            'bg-indigo-50 text-indigo-700' => $enquiry['status'] === 'new',
                            'bg-sky-50 text-sky-700' => str_contains((string) $enquiry['status'], 'follow'),
                            'bg-gray-100 text-gray-600' => $enquiry['status'] !== 'new' && ! str_contains((string) $enquiry['status'], 'follow'),
                        ])>{{ $enquiry['status_label'] }}</span>
                    </div>
                @empty
                    <p class="px-5 py-10 text-center text-sm text-gray-500">No enquiries yet.</p>
                @endforelse
            </div>
        </section>
        @endif
    </div>

    {{-- Expiring Plans + Active Allocations --}}
    <div class="flex flex-col gap-4 md:flex-row md:items-stretch">
        <section class="min-w-0 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm md:w-1/2">
            <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
                <h2 class="text-base font-semibold text-gray-900">Expiring Plans (Next 7 Days)</h2>
                <a href="{{ route('fees.index') }}" class="text-sm font-semibold text-indigo-600 hover:text-indigo-700">View all</a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[520px] text-left text-sm">
                    <thead class="border-b border-gray-200 bg-gray-50 text-xs font-semibold uppercase tracking-wide text-gray-500">
                        <tr>
                            <th class="px-5 py-3">Student</th>
                            <th class="px-3 py-3">Plan</th>
                            <th class="px-3 py-3">Expires On</th>
                            <th class="px-3 py-3">Amount</th>
                            <th class="px-5 py-3">Days Left</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-gray-700">
                        @forelse ($branch['expiring_plans'] as $plan)
                            <tr class="hover:bg-indigo-50/40">
                                <td class="px-5 py-3">
                                    <p class="font-medium text-gray-900">{{ $plan['student_name'] }}</p>
                                    <p class="text-xs text-gray-500">{{ $plan['student_code'] }}</p>
                                </td>
                                <td class="px-3 py-3">{{ $plan['plan_id'] }}</td>
                                <td class="px-3 py-3">{{ $plan['expires_on'] }}</td>
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

        <section class="min-w-0 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm md:w-1/2">
            <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
                <h2 class="text-base font-semibold text-gray-900">Active Seat Allocations</h2>
                <a href="{{ route('seats.index') }}" class="text-sm font-semibold text-indigo-600 hover:text-indigo-700">View all</a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[520px] text-left text-sm">
                    <thead class="border-b border-gray-200 bg-gray-50 text-xs font-semibold uppercase tracking-wide text-gray-500">
                        <tr>
                            <th class="px-5 py-3">Student</th>
                            <th class="px-3 py-3">Hall</th>
                            <th class="px-3 py-3">Seat No.</th>
                            <th class="px-3 py-3">Plan ID</th>
                            <th class="px-3 py-3">Since</th>
                            <th class="px-5 py-3">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-gray-700">
                        @forelse ($branch['active_allocations'] as $row)
                            <tr class="hover:bg-indigo-50/40">
                                <td class="px-5 py-3">
                                    <p class="font-medium text-gray-900">{{ $row['student_name'] }}</p>
                                    <p class="text-xs text-gray-500">{{ $row['student_code'] }}</p>
                                </td>
                                <td class="px-3 py-3">{{ $row['hall_name'] }}</td>
                                <td class="px-3 py-3">{{ $row['seat_number'] }}</td>
                                <td class="px-3 py-3">{{ $row['plan_id'] }}</td>
                                <td class="px-3 py-3">{{ $row['since'] }}</td>
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
</div>
