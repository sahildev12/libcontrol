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
                <h1 class="text-2xl font-bold tracking-tight text-gray-900">Profit-Loss Management</h1>
                <p class="mt-1 text-sm text-gray-500">Track operating expenses{{ isset($scopeLabel) && $scopeLabel !== '' ? ' for '.$scopeLabel : '' }}.</p>
            </div>

            <div class="flex flex-wrap items-center gap-2 sm:gap-3">
                <div class="relative" @click.outside="rangeOpen = false">
                    <button
                        type="button"
                        @click="rangeOpen = !rangeOpen"
                        class="inline-flex h-10 items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50"
                    >
                        <svg class="size-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3M5 11h14M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        <span>{{ $rangeLabel }}</span>
                    </button>
                    <form
                        method="get"
                        action="{{ route('profit-loss.index') }}"
                        x-show="rangeOpen"
                        x-cloak
                        x-transition
                        class="absolute left-0 right-0 z-20 mt-2 w-full max-w-[min(18rem,calc(100vw-2rem))] rounded-xl border border-gray-200 bg-white p-4 shadow-lg sm:left-auto sm:right-0 sm:w-72"
                    >
                        <div class="space-y-3">
                            <div>
                                <label class="mb-1 block text-xs font-semibold text-gray-500">From</label>
                                <input type="date" name="date_from" value="{{ $dateFrom }}" class="w-full rounded-lg border-gray-200 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-semibold text-gray-500">To</label>
                                <input type="date" name="date_to" value="{{ $dateTo }}" class="w-full rounded-lg border-gray-200 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                            <button type="submit" class="inline-flex h-9 w-full items-center justify-center rounded-lg bg-indigo-600 text-sm font-semibold text-white hover:bg-indigo-700">
                                Apply range
                            </button>
                        </div>
                    </form>
                </div>

                <a
                    href="{{ route('profit-loss.index', request()->only(['date_from', 'date_to'])) }}"
                    class="inline-flex h-10 items-center gap-2 rounded-lg border border-gray-200 bg-white px-3.5 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50"
                >
                    <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v6h6M20 20v-6h-6M5 19a9 9 0 0014-7M19 5a9 9 0 00-14 7"/></svg>
                    Refresh
                </a>

                <button type="button" @click="openCreate()" class="inline-flex h-10 items-center rounded-lg bg-indigo-600 px-4 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700">
                    Add Expense
                </button>
            </div>
        </header>

        @php
            $formatDelta = static function (?float $value): string {
                if ($value === null) {
                    return '';
                }

                $sign = $value >= 0 ? '+' : '';

                return $sign.$value.'%';
            };
            $deltaClass = static function (?float $value, string $positive = 'text-emerald-700', string $negative = 'text-red-700'): string {
                if ($value === null) {
                    return 'text-gray-500';
                }

                return $value >= 0 ? $positive : $negative;
            };
        @endphp

        <section class="mt-4 flex gap-3 overflow-x-auto pb-1 md:overflow-visible">
            <div class="min-w-[200px] flex-1 rounded-xl border border-red-200 bg-red-50 p-4 shadow-sm">
                <div class="mb-3 inline-flex size-10 items-center justify-center rounded-xl bg-red-100 text-red-600">
                    <svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                </div>
                <p class="text-[11px] font-semibold uppercase tracking-wide text-red-700">Total Expenses</p>
                <div class="mt-1 flex flex-wrap items-center gap-2">
                    <p class="text-2xl font-bold tabular-nums text-red-900">₹{{ number_format($summary['total_expenses'], 2) }}</p>
                    @if ($summary['total_expenses_delta_pct'] !== null)
                        <span class="inline-flex rounded-full bg-red-100 px-2 py-0.5 text-[11px] font-semibold {{ $deltaClass($summary['total_expenses_delta_pct'], 'text-red-700', 'text-red-800') }}">
                            {{ $summary['total_expenses_delta_pct'] >= 0 ? '↑' : '↓' }} {{ $formatDelta($summary['total_expenses_delta_pct']) }}
                        </span>
                    @endif
                </div>
                <p class="mt-1 text-[11px] text-red-700/80">Recorded in selected period</p>
            </div>

            <!-- <div class="min-w-[200px] flex-1 rounded-xl border border-sky-200 bg-sky-50 p-4 shadow-sm">
                <div class="mb-3 inline-flex size-10 items-center justify-center rounded-xl bg-sky-100 text-sky-600">
                    <svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                </div>
                <p class="text-[11px] font-semibold uppercase tracking-wide text-sky-700">Expense Records</p>
                <div class="mt-1 flex flex-wrap items-center gap-2">
                    <p class="text-2xl font-bold tabular-nums text-sky-900">{{ number_format($summary['expense_count']) }}</p>
                    @if ($summary['expense_count_delta_pct'] !== null)
                        <span class="inline-flex rounded-full bg-sky-100 px-2 py-0.5 text-[11px] font-semibold {{ $deltaClass($summary['expense_count_delta_pct'], 'text-sky-700', 'text-sky-800') }}">
                            {{ $summary['expense_count_delta_pct'] >= 0 ? '↑' : '↓' }} {{ $formatDelta($summary['expense_count_delta_pct']) }}
                        </span>
                    @endif
                </div>
                <p class="mt-1 text-[11px] text-sky-700/80">Entries in selected period</p>
            </div> -->

            <div class="min-w-[200px] flex-1 rounded-xl border border-emerald-200 bg-emerald-50 p-4 shadow-sm">
                <div class="mb-3 inline-flex size-10 items-center justify-center rounded-xl bg-emerald-100 text-emerald-600">
                    <svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                </div>
                <p class="text-[11px] font-semibold uppercase tracking-wide text-emerald-700">Cash Payments</p>
                <div class="mt-1 flex flex-wrap items-center gap-2">
                    <p class="text-2xl font-bold tabular-nums text-emerald-900">₹{{ number_format($summary['cash_total'], 2) }}</p>
                    @if ($summary['cash_delta_pct'] !== null)
                        <span class="inline-flex rounded-full bg-emerald-100 px-2 py-0.5 text-[11px] font-semibold {{ $deltaClass($summary['cash_delta_pct'], 'text-emerald-700', 'text-emerald-800') }}">
                            {{ $summary['cash_delta_pct'] >= 0 ? '↑' : '↓' }} {{ $formatDelta($summary['cash_delta_pct']) }}
                        </span>
                    @endif
                </div>
                <p class="mt-1 text-[11px] text-emerald-700/80">{{ $summary['cash_count'] }} {{ $summary['cash_count'] === 1 ? 'transaction' : 'transactions' }}</p>
            </div>

            <div class="min-w-[200px] flex-1 rounded-xl border border-violet-200 bg-violet-50 p-4 shadow-sm">
                <div class="mb-3 inline-flex size-10 items-center justify-center rounded-xl bg-violet-100 text-violet-600">
                    <svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z"/></svg>
                </div>
                <p class="text-[11px] font-semibold uppercase tracking-wide text-violet-700">Bank Transfers</p>
                <div class="mt-1 flex flex-wrap items-center gap-2">
                    <p class="text-2xl font-bold tabular-nums text-violet-900">₹{{ number_format($summary['bank_transfer_total'], 2) }}</p>
                    @if ($summary['bank_transfer_delta_pct'] !== null)
                        <span class="inline-flex rounded-full bg-violet-100 px-2 py-0.5 text-[11px] font-semibold {{ $deltaClass($summary['bank_transfer_delta_pct'], 'text-violet-700', 'text-violet-800') }}">
                            {{ $summary['bank_transfer_delta_pct'] >= 0 ? '↑' : '↓' }} {{ $formatDelta($summary['bank_transfer_delta_pct']) }}
                        </span>
                    @endif
                </div>
                <p class="mt-1 text-[11px] text-violet-700/80">{{ $summary['bank_transfer_count'] }} {{ $summary['bank_transfer_count'] === 1 ? 'transaction' : 'transactions' }}</p>
            </div>

            <div class="min-w-[200px] flex-1 rounded-xl border border-amber-200 bg-amber-50 p-4 shadow-sm">
                <div class="mb-3 inline-flex size-10 items-center justify-center rounded-xl bg-amber-100 text-amber-600">
                    <svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"/></svg>
                </div>
                <p class="text-[11px] font-semibold uppercase tracking-wide text-amber-700">Top Category</p>
                <p class="mt-1 truncate text-xl font-bold text-amber-950">{{ $summary['top_category'] }}</p>
                <p class="mt-1 text-[11px] text-amber-800/80">
                    @if ($summary['top_category_amount'] > 0)
                        ₹{{ number_format($summary['top_category_amount'], 2) }} ({{ $summary['top_category_share_pct'] }}%)
                    @else
                        No expenses in this period
                    @endif
                </p>
            </div>
        </section>

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
                            <label class="block text-sm font-medium text-gray-700">Expense date</label>
                            <input type="date" x-model="form.expense_date" class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm">
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
