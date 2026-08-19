<x-admin-layout>
    <header class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Dashboard</h1>
            <p class="mt-1 text-sm text-gray-600">{{ Auth::user()->branch?->name }} · Overview</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('seats.index') }}" class="inline-flex h-9 items-center rounded-lg bg-indigo-600 px-4 text-sm font-semibold text-white hover:bg-indigo-700">Open Seat Map</a>
            <a href="{{ route('halls.index') }}" class="inline-flex h-9 items-center rounded-lg border border-gray-200 bg-white px-4 text-sm font-semibold text-gray-700 hover:bg-gray-50">Manage Halls</a>
        </div>
    </header>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-admin.stat-card label="Total Seats" :value="$stats['total_seats']" tone="blue" hint="All seats in branch" />
        <x-admin.stat-card label="Occupied" :value="$stats['occupied']" tone="green" hint="Active assignments" />
        <x-admin.stat-card label="Vacant" :value="$stats['available']" tone="slate" hint="Ready to assign" />
        <x-admin.stat-card label="On Trial" :value="$stats['on_trial']" tone="cyan" hint="Trial allocations" />
        <x-admin.stat-card label="Expiring Soon" :value="$stats['expiring_soon']" tone="amber" hint="Within 7 days" />
        <x-admin.stat-card label="Expired" :value="$stats['expired']" tone="red" hint="Needs renewal" />
        <x-admin.stat-card label="Total Students" :value="$stats['total_students']" tone="purple" hint="Registered students" />
        <x-admin.stat-card label="New Enquiries" :value="$stats['new_enquiries']" tone="pink" hint="Pending follow-up" />
    </div>

    <section class="overflow-hidden rounded-xl border border-indigo-100 bg-gradient-to-r from-indigo-50 via-white to-cyan-50 p-5 shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h2 class="text-base font-semibold text-gray-900">Welcome back, {{ Auth::user()->name }}</h2>
                <p class="mt-1 text-sm text-gray-600">Manage halls, seats, and assignments from the sidebar.</p>
            </div>
            <div class="rounded-lg border border-white bg-white/80 px-4 py-2 text-sm text-gray-700">
                <span class="font-medium text-indigo-700">{{ $stats['total_halls'] }}</span> halls ·
                <span class="font-medium text-emerald-700">{{ $stats['occupied'] }}</span> occupied ·
                <span class="font-medium text-gray-700">{{ $stats['available'] }}</span> vacant
            </div>
        </div>
    </section>

    <div class="grid gap-4 lg:grid-cols-3">
        <section class="overflow-hidden rounded-xl border border-amber-200 bg-white shadow-sm">
            <div class="border-b border-amber-200 bg-amber-50 px-4 py-3">
                <h2 class="text-sm font-semibold text-amber-900">Expiring Soon (7 days)</h2>
            </div>
            <div class="max-h-72 divide-y divide-gray-100 overflow-y-auto">
                @forelse ($feeOverview['expiring_soon'] as $booking)
                    <div class="px-4 py-3 text-sm">
                        <p class="font-medium text-gray-900">{{ $booking->student?->name }} ({{ $booking->student?->student_code }})</p>
                        <p class="text-gray-600">Expires {{ $booking->plan_expiry_date?->format('M d, Y') }} • ₹{{ $booking->fee_amount }}</p>
                    </div>
                @empty
                    <p class="px-4 py-8 text-center text-sm text-gray-500">No plans expiring soon.</p>
                @endforelse
            </div>
        </section>

        <section class="overflow-hidden rounded-xl border border-red-200 bg-white shadow-sm">
            <div class="border-b border-red-200 bg-red-50 px-4 py-3">
                <h2 class="text-sm font-semibold text-red-900">Expired Plans</h2>
            </div>
            <div class="max-h-72 divide-y divide-gray-100 overflow-y-auto">
                @forelse ($feeOverview['expired'] as $booking)
                    <div class="px-4 py-3 text-sm">
                        <p class="font-medium text-gray-900">{{ $booking->student?->name }} ({{ $booking->student?->student_code }})</p>
                        <p class="text-gray-600">Expired {{ $booking->plan_expiry_date?->format('M d, Y') }}</p>
                    </div>
                @empty
                    <p class="px-4 py-8 text-center text-sm text-gray-500">No expired plans.</p>
                @endforelse
            </div>
        </section>

        <section class="overflow-hidden rounded-xl border border-emerald-200 bg-white shadow-sm">
            <div class="border-b border-emerald-200 bg-emerald-50 px-4 py-3">
                <h2 class="text-sm font-semibold text-emerald-900">Active Plans</h2>
            </div>
            <div class="max-h-72 divide-y divide-gray-100 overflow-y-auto">
                @forelse ($feeOverview['active'] as $booking)
                    <div class="px-4 py-3 text-sm">
                        <p class="font-medium text-gray-900">{{ $booking->student?->name }}</p>
                        <p class="text-gray-600">{{ $booking->seat?->hall?->name }} • Seat {{ $booking->seat?->seat_number }}</p>
                    </div>
                @empty
                    <p class="px-4 py-8 text-center text-sm text-gray-500">No active plans.</p>
                @endforelse
            </div>
        </section>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <a href="{{ route('halls.index') }}" class="group rounded-xl border border-blue-200 bg-blue-50 p-5 shadow-sm transition hover:shadow-md">
            <p class="text-sm font-semibold text-blue-700">Halls</p>
            <p class="mt-2 text-2xl font-bold text-blue-900">{{ $stats['total_halls'] }}</p>
            <p class="mt-1 text-xs text-blue-600">Add, edit, delete in popup</p>
        </a>
        <a href="{{ route('seats.index') }}" class="group rounded-xl border border-emerald-200 bg-emerald-50 p-5 shadow-sm transition hover:shadow-md">
            <p class="text-sm font-semibold text-emerald-700">Seat Map</p>
            <p class="mt-2 text-2xl font-bold text-emerald-900">{{ $stats['total_seats'] }}</p>
            <p class="mt-1 text-xs text-emerald-600">Movie-style live view</p>
        </a>
        <a href="{{ route('fees.index') }}" class="group rounded-xl border border-amber-200 bg-amber-50 p-5 shadow-sm transition hover:shadow-md">
            <p class="text-sm font-semibold text-amber-700">Fee Management</p>
            <p class="mt-2 text-2xl font-bold text-amber-900">{{ $feeOverview['expiring_soon']->count() + $feeOverview['expired']->count() }}</p>
            <p class="mt-1 text-xs text-amber-600">View all payment records</p>
        </a>
        <a href="{{ route('students.index') }}" class="group rounded-xl border border-purple-200 bg-purple-50 p-5 shadow-sm transition hover:shadow-md">
            <p class="text-sm font-semibold text-purple-700">Students</p>
            <p class="mt-2 text-2xl font-bold text-purple-900">{{ $stats['total_students'] }}</p>
            <p class="mt-1 text-xs text-purple-600">Manage student records</p>
        </a>
    </div>
</x-admin-layout>
