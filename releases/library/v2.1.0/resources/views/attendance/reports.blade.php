<x-admin-layout>
    <header class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-gray-900">Attendance Reports</h1>
            <p class="mt-1 text-sm text-gray-500">Daily attendance summary{{ $scopeLabel !== '' ? ' for '.$scopeLabel : '' }}.</p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <div class="relative">
                <form method="get" action="{{ route('attendance.reports') }}" class="flex flex-wrap items-end gap-2 rounded-xl border border-gray-200 bg-white p-3 shadow-sm">
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-gray-500">From</label>
                        <input type="date" name="date_from" value="{{ $dateFrom }}" class="rounded-lg border-gray-200 text-sm">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-gray-500">To</label>
                        <input type="date" name="date_to" value="{{ $dateTo }}" class="rounded-lg border-gray-200 text-sm">
                    </div>
                    <button type="submit" class="inline-flex h-9 items-center rounded-lg bg-indigo-600 px-4 text-sm font-semibold text-white hover:bg-indigo-700">
                        Apply
                    </button>
                </form>
            </div>
            <a href="{{ route('attendance.index') }}" class="inline-flex h-10 items-center rounded-lg border border-gray-200 bg-white px-3.5 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50">
                Today's register
            </a>
        </div>
    </header>

    <p class="mt-2 text-sm font-medium text-gray-600">{{ $rangeLabel }}</p>

    <section class="mt-6 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Date</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">Present</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">Absent</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">Student QR</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">Staff GPS</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($dailyStats as $day)
                        <tr>
                            <td class="px-4 py-3 font-medium text-gray-900">{{ $day['date_label'] }}</td>
                            <td class="px-4 py-3 text-right tabular-nums text-emerald-700">{{ $day['present'] }}</td>
                            <td class="px-4 py-3 text-right tabular-nums text-amber-700">{{ $day['absent'] }}</td>
                            <td class="px-4 py-3 text-right tabular-nums text-indigo-700">{{ $day['qr_check_ins'] }}</td>
                            <td class="px-4 py-3 text-right tabular-nums text-violet-700">{{ $day['staff_check_ins'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-sm text-gray-500">No data for this range.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</x-admin-layout>
