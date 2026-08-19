<x-admin-layout>
    <div
        x-data="assignmentPage({
            bookings: @js($bookings),
            students: @js($students),
            halls: @js($halls),
            timeSlotOptions: @js($timeSlotOptions),
            storeUrl: @js(route('seat-assignments.store')),
            availableSeatsUrl: @js(route('seat-assignments.available-seats')),
            bulkCancelUrl: @js(route('seat-assignments.bulk-cancel')),
        })"
        x-init="init()"
    >
        <header class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Seat Assignment</h1>
                <p class="mt-1 text-sm text-gray-600">Assign seats with time-slot conflict validation.</p>
            </div>
            <button type="button" @click="formOpen = true" class="inline-flex h-9 items-center rounded-lg bg-indigo-600 px-4 text-sm font-semibold text-white hover:bg-indigo-700">
                New Assignment
            </button>
        </header>

        <section class="mt-4 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <x-admin.data-table-toolbar search-placeholder="Search assignments..." :show-bulk-cancel="true" />

            <div class="overflow-x-auto">
                <table class="w-full min-w-[980px]">
                    <thead class="border-b border-gray-200 bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                        <tr>
                            <th class="w-10 px-4 py-3">
                                <input type="checkbox" @change="toggleSelectAll($event)" :checked="allPageSelected()" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                            </th>
                            <th class="px-4 py-3">Student</th>
                            <th class="px-4 py-3">Hall / Seat</th>
                            <th class="px-4 py-3">Time Slot</th>
                            <th class="px-4 py-3">Fee</th>
                            <th class="px-4 py-3">Plan Expiry</th>
                            <th class="px-4 py-3 text-right">Actions</th>
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
                                <td class="px-4 py-3" x-text="row.time_slot_label || row.time_slot?.replaceAll('_', ' ')"></td>
                                <td class="px-4 py-3" x-text="`${row.fee_type} • ₹${row.fee_amount}`"></td>
                                <td class="px-4 py-3" x-text="row.plan_expiry_date"></td>
                                <td class="px-4 py-3 text-right">
                                    <div class="inline-flex gap-1.5">
                                        <button type="button" @click="openView(row)" class="rounded-lg border border-gray-200 bg-white px-2.5 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-50">View</button>
                                        <button type="button" @click="cancel(row)" class="rounded-lg border border-red-200 bg-red-50 px-2.5 py-1.5 text-xs font-semibold text-red-700 hover:bg-red-100">Cancel</button>
                                    </div>
                                </td>
                            </tr>
                        </template>
                        <tr x-show="paginatedRows().length === 0">
                            <td colspan="7" class="px-4 py-10 text-center text-gray-500">No active assignments.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <x-admin.data-table-footer />
        </section>

        <x-admin.assignment-view-modal />

        <div x-show="formOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-gray-900/50" @click="formOpen = false"></div>
            <div class="relative max-h-[90vh] w-full max-w-xl overflow-y-auto rounded-xl bg-white shadow-xl">
                <div class="border-b border-gray-200 px-5 py-4">
                    <h3 class="text-lg font-semibold text-gray-900">New Seat Assignment</h3>
                </div>
                <form @submit.prevent="submitForm()" class="space-y-4 p-5">
                    <x-admin.student-search-select required />
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Hall</label>
                        <select x-model.number="form.hall_id" @change="loadSeats()" required class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm">
                            <option value="">Select hall</option>
                            <template x-for="hall in halls" :key="hall.id">
                                <option :value="hall.id" x-text="hall.name"></option>
                            </template>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Time Slot</label>
                            <select x-model="form.time_slot" @change="loadSeats()" required class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm">
                                <template x-for="option in timeSlotOptions" :key="option.value">
                                    <option :value="option.value" x-text="option.label"></option>
                                </template>
                            </select>
                            <p x-show="assignmentTimeError()" class="mt-1 text-xs text-red-600" x-text="assignmentTimeError()"></p>
                        </div>
                    <div x-show="form.time_slot === 'custom_hours'" class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Start Time</label>
                            <input type="time" x-model="form.custom_start_time" @change="loadSeats()" class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">End Time</label>
                            <input type="time" x-model="form.custom_end_time" @change="loadSeats()" class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm">
                        </div>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Joining Date</label>
                            <input type="date" x-model="form.joining_date" @change="loadSeats()" required class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Plan Expiry Date</label>
                            <input type="date" x-model="form.plan_expiry_date" @change="loadSeats()" class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Seat</label>
                        <select x-model.number="form.seat_id" required class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm">
                            <option value="">Select available seat</option>
                            <template x-for="seat in availableSeats" :key="seat.id">
                                <option :value="seat.id" x-text="`Seat ${seat.seat_number}`"></option>
                            </template>
                        </select>
                        <p class="mt-1 text-xs text-gray-500" x-text="`${availableSeats.length} seat(s) available for selected slot.`"></p>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Fee Type</label>
                            <select x-model="form.fee_type" required class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm">
                                <option value="monthly">Monthly</option>
                                <option value="yearly">Yearly</option>
                                <option value="custom">Custom</option>
                                <option value="membership">Membership</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Fee Amount</label>
                            <input type="number" min="0" step="0.01" x-model.number="form.fee_amount" required class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm">
                        </div>
                    </div>
                    <div x-show="form.fee_type === 'membership'">
                        <label class="block text-sm font-medium text-gray-700">Membership Mode</label>
                        <select x-model="form.membership_mode" class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm">
                            <option value="assigned_seat">Assigned Seat</option>
                            <option value="any_seat">Any Available Seat</option>
                        </select>
                    </div>
                    <div class="flex justify-end gap-2 border-t border-gray-200 pt-4">
                        <button type="button" @click="formOpen = false" class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Cancel</button>
                        <button type="submit" :disabled="saving || Boolean(assignmentTimeError())" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700 disabled:opacity-50" x-text="saving ? 'Saving...' : 'Assign Seat'"></button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-admin-layout>
