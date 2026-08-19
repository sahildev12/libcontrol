<x-admin-layout>
    <div x-data="feeTable({ rows: @js($rows) })" x-init="init()">
        <header>
            <h1 class="text-2xl font-bold text-gray-900">Fee Management</h1>
            <p class="mt-1 text-sm text-gray-600">All payment and plan records for {{ Auth::user()->branch?->name }}.</p>
        </header>

        <section class="mt-4 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <x-admin.data-table-toolbar search-placeholder="Search fees by student, hall, status..." />

            <div class="overflow-x-auto">
                <table class="w-full min-w-[1100px]">
                    <thead class="border-b border-gray-200 bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                        <tr>
                            <th class="w-10 px-4 py-3">
                                <input type="checkbox" @change="toggleSelectAll($event)" :checked="allPageSelected()" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                            </th>
                            <th class="px-4 py-3">Student</th>
                            <th class="px-4 py-3">Hall / Seat</th>
                            <th class="px-4 py-3">Time Slot</th>
                            <th class="px-4 py-3">Fee Type</th>
                            <th class="px-4 py-3">Amount</th>
                            <th class="px-4 py-3">Joining</th>
                            <th class="px-4 py-3">Plan Expiry</th>
                            <th class="px-4 py-3">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-sm text-gray-700">
                        <template x-for="row in paginatedRows()" :key="row.id">
                            <tr class="hover:bg-indigo-50/40">
                                <td class="px-4 py-3">
                                    <input type="checkbox" :value="row.id" x-model="selectedIds" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                </td>
                                <td class="px-4 py-3">
                                    <div class="font-medium text-gray-900" x-text="row.student_name"></div>
                                    <div class="text-xs text-gray-500" x-text="row.student_code"></div>
                                </td>
                                <td class="px-4 py-3" x-text="`${row.hall_name} • Seat ${row.seat_number}`"></td>
                                <td class="px-4 py-3 capitalize" x-text="row.time_slot?.replaceAll('_', ' ')"></td>
                                <td class="px-4 py-3 capitalize" x-text="row.fee_type"></td>
                                <td class="px-4 py-3" x-text="`₹${row.fee_amount}`"></td>
                                <td class="px-4 py-3" x-text="row.joining_date"></td>
                                <td class="px-4 py-3" x-text="row.plan_expiry_date"></td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold capitalize"
                                          :class="{
                                            'bg-amber-100 text-amber-800': row.payment_status === 'expiring_soon',
                                            'bg-red-100 text-red-800': row.payment_status === 'expired',
                                            'bg-emerald-100 text-emerald-800': row.payment_status === 'active',
                                          }"
                                          x-text="row.payment_status?.replaceAll('_', ' ')"></span>
                                </td>
                            </tr>
                        </template>
                        <tr x-show="paginatedRows().length === 0">
                            <td colspan="9" class="px-4 py-10 text-center text-gray-500">No fee records found.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <x-admin.data-table-footer />
        </section>
    </div>
</x-admin-layout>
