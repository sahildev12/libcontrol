<x-admin-layout>
    <div
        x-data="feeTable({
            rows: @js($rows),
            students: @js($students ?? []),
            storeUrl: @js(route('fees.store')),
            bulkDeleteUrl: @js(route('fees.bulk-destroy')),
            seatAssignmentsUrl: @js(route('seat-assignments.index')),
        })"
        x-init="init()"
    >
        <header class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Fee Management</h1>
                <p class="mt-1 text-sm text-gray-600">Student fees{{ isset($scopeLabel) && $scopeLabel !== '' ? ' for '.$scopeLabel : '' }}.</p>
            </div>
            <button type="button" @click="openCreate()" class="inline-flex h-9 items-center rounded-lg bg-indigo-600 px-4 text-sm font-semibold text-white hover:bg-indigo-700">
                Set Up Fee
            </button>
        </header>

        <section class="mt-4 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <x-admin.data-table-toolbar search-placeholder="Search fees by student, hall, status..." :show-bulk-delete="true" />

            <div class="flex flex-wrap items-end gap-3 border-b border-gray-100 px-4 py-3">
                <div>
                    <label class="block text-[11px] font-semibold uppercase tracking-wide text-gray-400">Plan status</label>
                    <select x-model="planStatusFilter" class="mt-1 min-w-[11rem] rounded-lg border border-gray-300 py-2 pl-3 pr-10 text-sm">
                        <option value="">All plans</option>
                        <option value="expiring_or_expired">Expiring soon &amp; Expired</option>
                        <option value="upcoming">Upcoming</option>
                        <option value="active">Active</option>
                        <option value="expiring_soon">Expiring soon</option>
                        <option value="expired">Expired</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[11px] font-semibold uppercase tracking-wide text-gray-400">Payment status</label>
                    <select x-model="paymentStatusFilter" class="mt-1 min-w-[10rem] rounded-lg border border-gray-300 py-2 pl-3 pr-10 text-sm">
                        <option value="">All payments</option>
                        <option value="unpaid">Unpaid</option>
                        <option value="pending">Pending</option>
                        <option value="partial">Partial</option>
                        <option value="paid">Paid</option>
                        <option value="overdue">Overdue</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[11px] font-semibold uppercase tracking-wide text-gray-400">From date</label>
                    <input type="date" x-model="dateFrom" class="mt-1 rounded-lg border border-gray-300 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-[11px] font-semibold uppercase tracking-wide text-gray-400">To date</label>
                    <input type="date" x-model="dateTo" class="mt-1 rounded-lg border border-gray-300 px-3 py-2 text-sm">
                </div>
                <button type="button" @click="planStatusFilter = 'expiring_or_expired'; paymentStatusFilter = ''; dateFrom = ''; dateTo = ''" class="rounded-lg border border-gray-200 px-3 py-2 text-xs font-semibold text-gray-600 hover:bg-gray-50">Clear filters</button>
            </div>

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
                            <th class="px-4 py-3">Payment</th>
                            <th class="px-4 py-3">Plan</th>
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
                                <td class="px-4 py-3 capitalize" x-text="row.time_slot_label || row.time_slot?.replaceAll('_', ' ')"></td>
                                <td class="px-4 py-3" x-text="row.fee_type_label || row.fee_type"></td>
                                <td class="px-4 py-3">
                                    <div x-text="`₹${row.fee_amount}`"></div>
                                    <div class="text-xs text-gray-500" x-text="`Paid ₹${row.amount_paid ?? 0} / ₹${row.fee_amount}`"></div>
                                    <div class="text-xs text-gray-500" x-show="row.is_installment && ! row.is_flexible_installment" x-text="`${row.installments_paid || 0}/${row.installment_count || 0} installments`"></div>
                                </td>
                                <td class="px-4 py-3" x-text="row.joining_date"></td>
                                <td class="px-4 py-3" x-text="row.plan_expiry_date || '—'"></td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold capitalize"
                                          :class="{
                                            'bg-amber-100 text-amber-800': row.payment_status === 'pending' || row.payment_status === 'unpaid' || row.payment_status === 'partial',
                                            'bg-red-100 text-red-800': row.payment_status === 'overdue',
                                            'bg-emerald-100 text-emerald-800': row.payment_status === 'paid',
                                          }"
                                          x-text="row.payment_status_label || row.payment_status?.replaceAll('_', ' ')"></span>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold capitalize"
                                          :class="{
                                            'bg-blue-100 text-blue-800': row.plan_status === 'upcoming',
                                            'bg-amber-100 text-amber-800': row.plan_status === 'expiring_soon',
                                            'bg-red-100 text-red-800': row.plan_status === 'expired' || row.plan_status === 'cancelled',
                                            'bg-emerald-100 text-emerald-800': row.plan_status === 'active',
                                          }"
                                          x-text="row.plan_status_label || row.plan_status?.replaceAll('_', ' ')"></span>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <div class="inline-flex flex-wrap justify-end gap-1.5">
                                        <button
                                            type="button"
                                            @click="openReceivePayment(row)"
                                            class="inline-flex items-center rounded-lg border border-emerald-200 bg-emerald-50 px-2.5 py-1.5 text-xs font-semibold text-emerald-700 hover:bg-emerald-100"
                                        >
                                            Add fee
                                        </button>
                                        <button type="button" @click="openEdit(row)" class="rounded-lg border border-indigo-200 bg-indigo-50 px-2.5 py-1.5 text-xs font-semibold text-indigo-700 hover:bg-indigo-100">Edit</button>
                                        <x-admin.icon-button tone="gray" @click="openView(row)">View</x-admin.icon-button>
                                    </div>
                                </td>
                            </tr>
                        </template>
                        <tr x-show="paginatedRows().length === 0">
                            <td colspan="11" class="px-4 py-10 text-center text-gray-500">No fee records found.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <x-admin.data-table-footer />
        </section>

        <div x-show="viewOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-gray-900/50" @click="closeView()"></div>
            <div class="relative w-full max-w-xl overflow-hidden rounded-2xl bg-white shadow-2xl" @click.stop>
                <div class="flex items-center justify-between border-b border-gray-200 px-5 py-4">
                    <h3 class="text-lg font-semibold text-gray-900">Fee details</h3>
                    <button type="button" @click="closeView()" class="rounded-lg p-1 text-gray-400 hover:bg-gray-100" aria-label="Close">
                        <svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="max-h-[70vh] space-y-3 overflow-y-auto px-5 py-4 text-sm">
                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-400">Student</p>
                        <p class="mt-0.5 font-medium text-gray-900" x-text="viewRow?.student_name"></p>
                        <p class="text-xs text-gray-500" x-text="viewRow?.student_code"></p>
                    </div>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div>
                            <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-400">Hall / Seat</p>
                            <p class="mt-0.5 text-gray-900" x-text="viewRow ? `${viewRow.hall_name} • Seat ${viewRow.seat_number}` : ''"></p>
                        </div>
                        <div>
                            <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-400">Hours</p>
                            <p class="mt-0.5 capitalize text-gray-900" x-text="viewRow?.time_slot_label || viewRow?.time_slot?.replaceAll('_', ' ')"></p>
                        </div>
                    </div>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div>
                            <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-400">Fee type</p>
                            <p class="mt-0.5 text-gray-900" x-text="viewRow?.fee_type_label || viewRow?.fee_type"></p>
                        </div>
                        <div>
                            <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-400">Amount</p>
                            <p class="mt-0.5 text-gray-900" x-text="viewRow?.fee_amount != null ? `₹${viewRow.fee_amount}` : ''"></p>
                        </div>
                    </div>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div>
                            <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-400">Start date</p>
                            <p class="mt-0.5 text-gray-900" x-text="viewRow?.joining_date"></p>
                        </div>
                        <div x-show="viewRow?.fee_type !== 'one_time'">
                            <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-400">Plan end</p>
                            <p class="mt-0.5 text-gray-900" x-text="viewRow?.plan_expiry_date || '—'"></p>
                        </div>
                        <div>
                            <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-400">Payment plan</p>
                            <p class="mt-0.5 text-gray-900" x-text="viewRow?.payment_plan_label"></p>
                            <p class="text-xs text-gray-500" x-show="viewRow?.is_installment" x-text="viewRow?.installment_frequency_label"></p>
                        </div>
                    </div>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div>
                            <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-400">Payment status</p>
                            <p class="mt-0.5 capitalize text-gray-900" x-text="viewRow?.payment_status_label || viewRow?.payment_status?.replaceAll('_', ' ')"></p>
                        </div>
                        <div>
                            <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-400">Plan status</p>
                            <p class="mt-0.5 capitalize text-gray-900" x-text="viewRow?.plan_status_label || viewRow?.plan_status?.replaceAll('_', ' ')"></p>
                        </div>
                    </div>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div>
                            <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-400">Paid</p>
                            <p class="mt-0.5 text-gray-900" x-text="viewRow?.amount_paid != null ? `₹${viewRow.amount_paid}` : '₹0'"></p>
                        </div>
                        <div>
                            <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-400">Remaining</p>
                            <p class="mt-0.5 text-gray-900" x-text="viewRow?.amount_due != null ? `₹${viewRow.amount_due}` : ''"></p>
                        </div>
                    </div>
                    <div x-show="(viewRow?.payments || []).length > 0">
                        <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-400">Payment history</p>
                        <div class="mt-2 overflow-hidden rounded-lg border border-gray-100">
                            <table class="w-full text-left text-xs">
                                <thead class="bg-gray-50 text-gray-500">
                                    <tr>
                                        <th class="px-3 py-2">Received</th>
                                        <th class="px-3 py-2">Amount</th>
                                        <th class="px-3 py-2">Method</th>
                                        <th class="px-3 py-2">Note</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="payment in (viewRow?.payments || [])" :key="payment.id">
                                        <tr class="border-t border-gray-100">
                                            <td class="px-3 py-2 text-gray-700">
                                                <span x-text="payment.recorded_at || payment.payment_date"></span>
                                            </td>
                                            <td class="px-3 py-2 font-medium text-gray-900" x-text="`₹${payment.amount}`"></td>
                                            <td class="px-3 py-2 capitalize text-gray-700" x-text="payment.payment_method_label"></td>
                                            <td class="px-3 py-2 text-gray-600" x-text="payment.notes || payment.reference || '—'"></td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div x-show="viewRow?.is_flexible_installment" class="rounded-lg border border-indigo-100 bg-indigo-50 px-3 py-2 text-xs text-indigo-800">
                        Flexible payments can be received at any time before the plan end date.
                    </div>
                    <div x-show="viewRow?.is_installment && ! viewRow?.is_flexible_installment">
                        <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-400">Installment schedule</p>
                        <p class="mt-1 text-xs text-gray-500">To pay early or more than one installment, use Add fee and enter the amount received.</p>
                        <div class="mt-2 overflow-hidden rounded-lg border border-gray-100">
                            <table class="w-full text-left text-xs">
                                <thead class="bg-gray-50 text-gray-500">
                                    <tr>
                                        <th class="px-3 py-2">#</th>
                                        <th class="px-3 py-2">Due date</th>
                                        <th class="px-3 py-2">Amount</th>
                                        <th class="px-3 py-2">Paid</th>
                                        <th class="px-3 py-2">Remaining</th>
                                        <th class="px-3 py-2">Status</th>
                                        <th class="px-3 py-2"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="item in (viewRow?.installments || [])" :key="item.id">
                                        <tr class="border-t border-gray-100">
                                            <td class="px-3 py-2" x-text="item.number"></td>
                                            <td class="px-3 py-2" x-text="item.due_date"></td>
                                            <td class="px-3 py-2" x-text="`₹${item.amount}`"></td>
                                            <td class="px-3 py-2" x-text="`₹${item.paid_amount}`"></td>
                                            <td class="px-3 py-2" x-text="`₹${item.remaining_amount}`"></td>
                                            <td class="px-3 py-2 capitalize" x-text="item.status"></td>
                                            <td class="px-3 py-2 text-right">
                                                <button
                                                    type="button"
                                                    x-show="! item.paid"
                                                    @click="payInstallment(item)"
                                                    class="rounded-lg border border-emerald-200 bg-emerald-50 px-2 py-1 text-xs font-semibold text-emerald-700 hover:bg-emerald-100"
                                                >Mark paid</button>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="flex justify-end gap-2 border-t border-gray-200 px-5 py-3">
                    <button type="button" @click="closeView()" class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Close</button>
                    <button type="button" @click="openReceivePayment(viewRow); closeView()" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">Add fee</button>
                </div>
            </div>
        </div>

        <div x-show="receiveOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-gray-900/50" @click="closeReceivePayment()"></div>
            <div class="relative w-full max-w-md overflow-hidden rounded-xl bg-white shadow-xl" @click.stop>
                <div class="border-b border-gray-200 px-5 py-4">
                    <h3 class="text-lg font-semibold text-gray-900">Add fee</h3>
                    <p class="mt-1 text-xs text-gray-500">Record a payment received for this student.</p>
                </div>
                <form @submit.prevent="submitReceivePayment()" class="space-y-4 p-5">
                    <div class="rounded-lg border border-gray-100 bg-gray-50 px-3 py-2 text-sm">
                        <p class="font-medium text-gray-900" x-text="receiveRow?.student_name"></p>
                        <p class="text-xs text-gray-500" x-text="receiveRow ? `${receiveRow.hall_name} • Seat ${receiveRow.seat_number}` : ''"></p>
                        <div class="mt-2 grid grid-cols-3 gap-2 text-xs">
                            <div>
                                <p class="uppercase tracking-wide text-gray-400">Total</p>
                                <p class="font-semibold text-gray-800" x-text="`₹${receiveForm.fee_amount || receiveRow?.fee_amount || 0}`"></p>
                            </div>
                            <div>
                                <p class="uppercase tracking-wide text-gray-400">Paid</p>
                                <p class="font-semibold text-gray-800" x-text="`₹${receiveRow?.amount_paid ?? 0}`"></p>
                            </div>
                            <div>
                                <p class="uppercase tracking-wide text-gray-400">Due</p>
                                <p class="font-semibold text-gray-800" x-text="`₹${receiveRemainingPreview()}`"></p>
                            </div>
                        </div>
                    </div>
                    <div x-show="Number(receiveRow?.fee_amount || 0) <= 0">
                        <label class="block text-sm font-medium text-gray-700">Total fee amount <span class="text-red-500">*</span></label>
                        <input type="number" min="0.01" step="0.01" x-model.number="receiveForm.fee_amount" required class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm">
                        <p class="mt-1 text-xs text-gray-500">This booking has ₹0 fee. Set the total fee before recording payment.</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Amount received <span class="text-red-500">*</span></label>
                        <input type="number" min="0.01" step="0.01" :max="receiveMaxAmount() || undefined" x-model.number="receiveForm.amount" required class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm">
                    </div>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Payment method</label>
                            <select x-model="receiveForm.payment_method" class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm">
                                <option value="cash">Cash</option>
                                <option value="upi">UPI</option>
                                <option value="card">Card</option>
                                <option value="bank_transfer">Bank Transfer</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Payment date</label>
                            <input type="date" x-model="receiveForm.payment_date" class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Note <span class="font-normal text-gray-400">(optional)</span></label>
                        <input type="text" maxlength="255" x-model="receiveForm.note" placeholder="e.g. Receipt No." class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm">
                    </div>
                    <div class="flex justify-end gap-2 border-t border-gray-200 pt-4">
                        <button type="button" @click="closeReceivePayment()" class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Cancel</button>
                        <button type="submit" :disabled="receiveSaving || ! receiveForm.amount" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700 disabled:opacity-50" x-text="receiveSaving ? 'Saving...' : 'Record payment'"></button>
                    </div>
                </form>
            </div>
        </div>

        <div x-show="formOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-gray-900/50" @click="formOpen = false"></div>
            <div class="relative max-h-[90vh] w-full max-w-xl overflow-y-auto rounded-xl bg-white shadow-xl">
                <div class="border-b border-gray-200 px-5 py-4">
                    <h3 class="text-lg font-semibold text-gray-900" x-text="formMode === 'create' ? 'Set Up Fee' : 'Edit Fee'"></h3>
                    <p class="mt-1 text-xs text-gray-500" x-show="formMode === 'create'">Fees are linked to an existing seat assignment. Assign a seat first if needed.</p>
                </div>
                <form @submit.prevent="submitForm()" class="space-y-4 p-5">
                    <div x-show="formMode === 'create'">
                        <x-admin.student-search-select required />
                    </div>
                    <div x-show="formMode === 'edit'" class="rounded-lg border border-gray-100 bg-gray-50 px-3 py-2 text-sm">
                        <p class="font-medium text-gray-900" x-text="editSummary()"></p>
                    </div>
                    <div x-show="formMode === 'create' && form.student_id && ! assignmentLocked" class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-900">
                        <p>This student has no active seat assignment.</p>
                        <a :href="seatAssignmentsUrl" class="mt-1 inline-flex font-semibold text-amber-800 underline">Go to Seat Assignments</a>
                    </div>
                    <div x-show="assignmentSummary" class="rounded-lg border border-indigo-100 bg-indigo-50 px-3 py-2 text-sm text-indigo-900">
                        <p class="text-[11px] font-semibold uppercase tracking-wide text-indigo-600">Seat assignment</p>
                        <p class="mt-0.5 font-medium" x-text="assignmentSummary"></p>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Fee Type</label>
                            <select x-model="form.fee_type" @change="onFeeOptionsChanged()" required class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm">
                                <option value="monthly">Monthly</option>
                                <option value="yearly">Yearly</option>
                                <option value="custom">Custom</option>
                                <option value="membership">Membership</option>
                                <option value="one_time">One-time</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Fee Amount</label>
                            <input type="number" min="0.01" step="0.01" x-model.number="form.fee_amount" required class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm">
                        </div>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Joining Date</label>
                            <input type="date" x-model="form.joining_date" @change="onFeeOptionsChanged()" required class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm">
                        </div>
                        <div x-show="showsEndDate()">
                            <label class="block text-sm font-medium text-gray-700">
                                Plan End Date
                                <span class="text-red-500" x-show="endDateRequired()">*</span>
                            </label>
                            <input
                                type="date"
                                x-model="form.plan_expiry_date"
                                @change="onFeeOptionsChanged()"
                                :readonly="! endDateEditable()"
                                :required="endDateRequired()"
                                :class="endDateEditable() ? 'mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm' : lockedFieldClass()"
                            >
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Payment plan</label>
                        <select x-model="form.payment_plan" @change="onFeeOptionsChanged()" class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm">
                            <option value="full">Full payment</option>
                            <option value="installments" :disabled="form.fee_type === 'one_time'">Installments</option>
                        </select>
                    </div>
                    <div x-show="form.fee_type === 'membership'">
                        <label class="block text-sm font-medium text-gray-700">Membership Mode</label>
                        <select x-model="form.membership_mode" class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm">
                            <option value="assigned_seat">Assigned Seat</option>
                            <option value="any_seat">Any Available Seat</option>
                        </select>
                    </div>
                    <div x-show="form.payment_plan === 'installments'" class="space-y-4 rounded-lg border border-gray-100 p-3">
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Installment frequency</label>
                                <select x-model="form.installment_frequency" @change="onFeeOptionsChanged()" class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm">
                                    <option value="weekly">Weekly</option>
                                    <option value="monthly" :disabled="isFrequencyDisabled('monthly')">Monthly</option>
                                    <option value="quarterly">Quarterly</option>
                                    <option value="half_yearly">Half-yearly</option>
                                    <option value="yearly">Yearly</option>
                                    <option value="custom">Flexible (custom dates)</option>
                                </select>
                            </div>
                            <div x-show="form.installment_frequency !== 'custom'">
                                <label class="block text-sm font-medium text-gray-700">Installment count</label>
                                <input type="number" min="2" max="12" x-model.number="form.installment_count" @change="onFeeOptionsChanged()" class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm">
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">First due date</label>
                            <input type="date" x-model="form.first_due_date" @change="onFeeOptionsChanged()" class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm">
                        </div>
                        <div x-show="form.installment_frequency !== 'custom' && installmentPreview().length > 0">
                            <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-400">Installment preview</p>
                            <div class="mt-2 space-y-1 text-xs text-gray-600">
                                <template x-for="item in installmentPreview()" :key="item.number">
                                    <div class="flex justify-between rounded border border-gray-100 px-2 py-1">
                                        <span x-text="`#${item.number} — ${item.label}`"></span>
                                        <span class="font-medium" x-text="`₹${item.amount}`"></span>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                    <div class="flex justify-end gap-2 border-t border-gray-200 pt-4">
                        <button type="button" @click="formOpen = false" class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Cancel</button>
                        <button type="submit" :disabled="saving || ! feeFormReady()" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700 disabled:opacity-50" x-text="saving ? 'Saving...' : 'Save'"></button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-admin-layout>
