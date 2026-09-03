<x-admin-layout>
    <div
        x-data="profitLossPanel({
            rows: @js($expenses),
            summary: @js($summary),
            categories: @js($categories),
            branches: @js($branches),
            viewingAll: @js($viewingAll),
            defaultBranchId: @js($defaultBranchId),
            storeUrl: @js(route('profit-loss.expenses.store')),
            bulkDeleteUrl: @js(route('profit-loss.expenses.bulk-destroy')),
            dateFrom: @js($dateFrom),
            dateTo: @js($dateTo),
        })"
        x-init="init()"
    >
        <header class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Profit-Loss Management</h1>
                <p class="mt-1 text-sm text-gray-600">Track revenue, expenses, and profit{{ isset($scopeLabel) && $scopeLabel !== '' ? ' for '.$scopeLabel : '' }}.</p>
            </div>
            <button type="button" @click="openCreate()" class="inline-flex h-9 items-center rounded-lg bg-indigo-600 px-4 text-sm font-semibold text-white hover:bg-indigo-700">
                Add Expense
            </button>
        </header>

        <form method="GET" action="{{ route('profit-loss.index') }}" class="mt-4 flex flex-wrap items-end gap-3 rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
            <div>
                <label class="block text-[11px] font-semibold uppercase tracking-wide text-gray-400">From</label>
                <input type="date" name="date_from" value="{{ $dateFrom }}" class="mt-1 rounded-lg border border-gray-300 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-[11px] font-semibold uppercase tracking-wide text-gray-400">To</label>
                <input type="date" name="date_to" value="{{ $dateTo }}" class="mt-1 rounded-lg border border-gray-300 px-3 py-2 text-sm">
            </div>
            <button type="submit" class="rounded-lg bg-gray-900 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-800">Apply</button>
        </form>

        <section class="mt-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-emerald-700">Revenue</p>
                <p class="mt-2 text-2xl font-bold text-emerald-900">₹{{ number_format($summary['revenue'], 2) }}</p>
                <p class="mt-1 text-xs text-emerald-700">Fee payments received</p>
            </div>
            <div class="rounded-xl border border-red-200 bg-red-50 p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-red-700">Expenses</p>
                <p class="mt-2 text-2xl font-bold text-red-900">₹{{ number_format($summary['expenses'], 2) }}</p>
                <p class="mt-1 text-xs text-red-700">Operating costs recorded</p>
            </div>
            <div class="rounded-xl border border-indigo-200 bg-indigo-50 p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-indigo-700">Net Profit</p>
                <p class="mt-2 text-2xl font-bold {{ $summary['profit'] >= 0 ? 'text-indigo-900' : 'text-red-900' }}">₹{{ number_format($summary['profit'], 2) }}</p>
                <p class="mt-1 text-xs text-indigo-700">Revenue minus expenses</p>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Margin</p>
                <p class="mt-2 text-2xl font-bold text-gray-900">{{ $summary['margin_percent'] }}%</p>
                <p class="mt-1 text-xs text-gray-500">Profit as % of revenue</p>
            </div>
        </section>

        @if (($summary['expense_by_category'] ?? collect())->isNotEmpty())
            <section class="mt-4 rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                <h2 class="text-sm font-semibold text-gray-900">Expenses by category</h2>
                <div class="mt-3 grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($summary['expense_by_category'] as $item)
                        <div class="flex items-center justify-between rounded-lg border border-gray-100 bg-gray-50 px-3 py-2 text-sm">
                            <span class="text-gray-700">{{ $item['label'] }}</span>
                            <span class="font-semibold text-gray-900">₹{{ number_format($item['total'], 2) }}</span>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif

        <section class="mt-4 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <x-admin.data-table-toolbar search-placeholder="Search expenses..." :show-bulk-delete="true" />

            <div class="flex flex-wrap items-end gap-3 border-b border-gray-100 px-4 py-3">
                <div>
                    <label class="block text-[11px] font-semibold uppercase tracking-wide text-gray-400">Category</label>
                    <select x-model="categoryFilter" class="mt-1 min-w-[11rem] rounded-lg border border-gray-300 py-2 pl-3 pr-10 text-sm">
                        <option value="">All categories</option>
                        <template x-for="(label, key) in categories" :key="key">
                            <option :value="key" x-text="label"></option>
                        </template>
                    </select>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full min-w-[900px]">
                    <thead class="border-b border-gray-200 bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                        <tr>
                            <th class="w-10 px-4 py-3">
                                <input type="checkbox" @change="toggleSelectAll($event)" :checked="allPageSelected()" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                            </th>
                            <th class="px-4 py-3">Date</th>
                            <th class="px-4 py-3" x-show="viewingAll">Branch</th>
                            <th class="px-4 py-3">Category</th>
                            <th class="px-4 py-3">Title</th>
                            <th class="px-4 py-3">Amount</th>
                            <th class="px-4 py-3">Payment</th>
                            <th class="px-4 py-3">Recorded by</th>
                            <th class="px-4 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-sm text-gray-700">
                        <template x-for="row in paginatedRows()" :key="row.id">
                            <tr class="hover:bg-indigo-50/40">
                                <td class="px-4 py-3">
                                    <input type="checkbox" :value="row.id" x-model="selectedIds" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                </td>
                                <td class="px-4 py-3" x-text="row.expense_date"></td>
                                <td class="px-4 py-3" x-show="viewingAll" x-text="row.branch_name || '—'"></td>
                                <td class="px-4 py-3" x-text="row.category_label"></td>
                                <td class="px-4 py-3">
                                    <div class="font-medium text-gray-900" x-text="row.title"></div>
                                    <div class="text-xs text-gray-500" x-show="row.notes" x-text="row.notes"></div>
                                </td>
                                <td class="px-4 py-3 font-medium text-gray-900" x-text="`₹${row.amount}`"></td>
                                <td class="px-4 py-3 capitalize" x-text="row.payment_method_label"></td>
                                <td class="px-4 py-3" x-text="row.recorded_by_name || '—'"></td>
                                <td class="px-4 py-3 text-right">
                                    <div class="inline-flex gap-1.5">
                                        <button type="button" @click="openEdit(row)" class="rounded-lg border border-indigo-200 bg-indigo-50 px-2.5 py-1.5 text-xs font-semibold text-indigo-700 hover:bg-indigo-100">Edit</button>
                                        <button type="button" @click="deleteOne(row)" class="rounded-lg border border-red-200 bg-red-50 px-2.5 py-1.5 text-xs font-semibold text-red-700 hover:bg-red-100">Delete</button>
                                    </div>
                                </td>
                            </tr>
                        </template>
                        <tr x-show="paginatedRows().length === 0">
                            <td colspan="9" class="px-4 py-10 text-center text-gray-500">No expenses recorded for this period.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <x-admin.data-table-footer />
        </section>

        <div x-show="formOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-gray-900/50" @click="formOpen = false"></div>
            <div class="relative max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-xl bg-white shadow-xl">
                <div class="border-b border-gray-200 px-5 py-4">
                    <h3 class="text-lg font-semibold text-gray-900" x-text="formMode === 'create' ? 'Add Expense' : 'Edit Expense'"></h3>
                </div>
                <form @submit.prevent="submitForm()" class="space-y-4 p-5">
                    <div x-show="viewingAll" x-cloak>
                        <label class="block text-sm font-medium text-gray-700">Branch <span class="text-red-500">*</span></label>
                        <select x-model="form.branch_id" :required="viewingAll" class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm">
                            <option value="">Select branch</option>
                            <template x-for="branch in branches" :key="branch.id">
                                <option :value="branch.id" x-text="branch.name"></option>
                            </template>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Category <span class="text-red-500">*</span></label>
                        <select x-model="form.category" required class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm">
                            <option value="">Select category</option>
                            <template x-for="(label, key) in categories" :key="key">
                                <option :value="key" x-text="label"></option>
                            </template>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Title <span class="text-red-500">*</span></label>
                        <input type="text" x-model="form.title" required maxlength="160" class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm">
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Amount <span class="text-red-500">*</span></label>
                            <input type="number" min="0.01" step="0.01" x-model.number="form.amount" required class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Expense date <span class="text-red-500">*</span></label>
                            <input type="date" x-model="form.expense_date" required class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Payment method <span class="text-red-500">*</span></label>
                        <select x-model="form.payment_method" required class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm">
                            <option value="cash">Cash</option>
                            <option value="upi">UPI</option>
                            <option value="card">Card</option>
                            <option value="bank_transfer">Bank Transfer</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Notes <span class="font-normal text-gray-400">(optional)</span></label>
                        <textarea x-model="form.notes" rows="3" maxlength="1000" class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm"></textarea>
                    </div>
                    <div class="flex justify-end gap-2 border-t border-gray-200 pt-4">
                        <button type="button" @click="formOpen = false" class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Cancel</button>
                        <button type="submit" :disabled="saving" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700 disabled:opacity-50" x-text="saving ? 'Saving...' : 'Save'"></button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-admin-layout>
