<x-admin-layout>
    <div
        x-data="profitLossPanel({
            rows: @js($expenses),
            ledger: @js($ledger),
            summary: @js($summary),
            categories: @js($categories),
            branches: @js($branches),
            viewingAll: @js($viewingAll),
            defaultBranchId: @js($defaultBranchId),
            storeUrl: @js(route('finance.expenses.store')),
            bulkDeleteUrl: @js(route('finance.expenses.bulk-destroy')),
            chartsUrl: @js(route('finance.charts')),
            dateFrom: @js($dateFrom),
            dateTo: @js($dateTo),
            activeTab: @js($activeTab),
        })"
        x-init="init()"
    >
        <header class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-gray-900">Finance</h1>
                <p class="mt-1 text-sm text-gray-500">Track fee income, expenses, and net profit{{ isset($scopeLabel) && $scopeLabel !== '' ? ' for '.$scopeLabel : '' }}.</p>
            </div>

            <div class="flex w-full flex-col gap-2 sm:w-auto sm:flex-row sm:flex-wrap sm:items-center sm:gap-3">
                <x-admin.date-range-picker
                    :action="route('finance.index')"
                    :label="$rangeLabel"
                    from-name="date_from"
                    to-name="date_to"
                    :from-value="$dateFrom"
                    :to-value="$dateTo"
                >
                    <input type="hidden" name="tab" :value="activeTab">
                </x-admin.date-range-picker>

                <a
                    href="{{ route('finance.index', array_filter(['date_from' => $dateFrom, 'date_to' => $dateTo, 'tab' => $activeTab !== 'overview' ? $activeTab : null])) }}"
                    class="inline-flex h-10 items-center gap-2 rounded-lg border border-gray-200 bg-white px-3.5 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50"
                >
                    <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v6h6M20 20v-6h-6M5 19a9 9 0 0014-7M19 5a9 9 0 00-14 7"/></svg>
                    Refresh
                </a>

                <button type="button" x-show="activeTab === 'expenses'" @click="openCreate()" class="inline-flex h-10 items-center rounded-lg bg-indigo-600 px-4 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700">
                    Add Expense
                </button>
            </div>
        </header>

        <div class="mt-4 flex flex-wrap gap-2 border-b border-gray-200">
            <button type="button" @click="setTab('overview')" class="border-b-2 px-4 py-2 text-sm font-semibold transition-colors" :class="activeTab === 'overview' ? 'border-indigo-600 text-indigo-700' : 'border-transparent text-gray-500 hover:text-gray-700'">Overview</button>
            <button type="button" @click="setTab('statement')" class="border-b-2 px-4 py-2 text-sm font-semibold transition-colors" :class="activeTab === 'statement' ? 'border-indigo-600 text-indigo-700' : 'border-transparent text-gray-500 hover:text-gray-700'">Statement</button>
            <button type="button" @click="setTab('expenses')" class="border-b-2 px-4 py-2 text-sm font-semibold transition-colors" :class="activeTab === 'expenses' ? 'border-indigo-600 text-indigo-700' : 'border-transparent text-gray-500 hover:text-gray-700'">Expenses</button>
        </div>

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

        <section x-show="activeTab === 'overview'" x-cloak class="mt-4 flex gap-3 overflow-x-auto pb-1 md:overflow-visible">
            <div class="min-w-[200px] flex-1 rounded-xl border border-emerald-200 bg-emerald-50 p-4 shadow-sm">
                <div class="mb-3 inline-flex size-10 items-center justify-center rounded-xl bg-emerald-100 text-emerald-600">
                    <svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <p class="text-[11px] font-semibold uppercase tracking-wide text-emerald-700">Fee Income</p>
                <div class="mt-1 flex flex-wrap items-center gap-2">
                    <p class="text-2xl font-bold tabular-nums text-emerald-900">₹{{ number_format($summary['fee_income_total'], 2) }}</p>
                    @if ($summary['fee_income_delta_pct'] !== null)
                        <span class="inline-flex rounded-full bg-emerald-100 px-2 py-0.5 text-[11px] font-semibold {{ $deltaClass($summary['fee_income_delta_pct'], 'text-emerald-700', 'text-emerald-800') }}">
                            {{ $summary['fee_income_delta_pct'] >= 0 ? '↑' : '↓' }} {{ $formatDelta($summary['fee_income_delta_pct']) }}
                        </span>
                    @endif
                </div>
                <p class="mt-1 text-[11px] text-emerald-700/80">{{ $summary['fee_payment_count'] }} student {{ $summary['fee_payment_count'] === 1 ? 'payment' : 'payments' }}</p>
            </div>

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

            <div class="min-w-[200px] flex-1 rounded-xl border border-indigo-200 bg-indigo-50 p-4 shadow-sm">
                <div class="mb-3 inline-flex size-10 items-center justify-center rounded-xl bg-indigo-100 text-indigo-600">
                    <svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                </div>
                <p class="text-[11px] font-semibold uppercase tracking-wide text-indigo-700">Net Profit</p>
                <div class="mt-1 flex flex-wrap items-center gap-2">
                    <p class="text-2xl font-bold tabular-nums {{ $summary['net_profit'] >= 0 ? 'text-indigo-900' : 'text-red-900' }}">₹{{ number_format($summary['net_profit'], 2) }}</p>
                    @if ($summary['net_profit_delta_pct'] !== null)
                        <span class="inline-flex rounded-full bg-indigo-100 px-2 py-0.5 text-[11px] font-semibold {{ $deltaClass($summary['net_profit_delta_pct'], 'text-indigo-700', 'text-red-700') }}">
                            {{ $summary['net_profit_delta_pct'] >= 0 ? '↑' : '↓' }} {{ $formatDelta($summary['net_profit_delta_pct']) }}
                        </span>
                    @endif
                </div>
                <p class="mt-1 text-[11px] text-indigo-700/80">Fee income minus expenses</p>
            </div>

            <div class="min-w-[200px] flex-1 rounded-xl border border-amber-200 bg-amber-50 p-4 shadow-sm">
                <div class="mb-3 inline-flex size-10 items-center justify-center rounded-xl bg-amber-100 text-amber-600">
                    <svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"/></svg>
                </div>
                <p class="text-[11px] font-semibold uppercase tracking-wide text-amber-700">Top Expense Category</p>
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

        <section x-show="activeTab === 'overview'" x-cloak class="mt-4 grid min-w-0 gap-4 md:grid-cols-2 xl:grid-cols-3">
            <div class="min-w-0 rounded-xl border border-gray-200 bg-white p-4 shadow-sm xl:col-span-1">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h2 class="text-sm font-semibold text-gray-900">Student Fee Collection</h2>
                    </div>
                    <select x-model="collectionPeriod" class="rounded-lg border border-gray-200 bg-white py-1.5 pl-2 pr-8 text-xs font-medium text-gray-700">
                        <option value="this_month">This Month</option>
                        <option value="last_3_months">Last 3 Months</option>
                        <option value="last_6_months">Last 6 Months</option>
                        <option value="this_year">This Year</option>
                    </select>
                </div>

                <div class="relative mt-4 h-56 min-w-0 w-full">
                    <div x-show="chartsLoading" class="absolute inset-0 z-10 flex items-center justify-center rounded-lg bg-white/80 text-xs text-gray-500">Loading chart...</div>
                    <canvas x-ref="feeCollectionChart"></canvas>
                    <div x-show="! chartsLoading && ! feeCollectionHasData()" class="absolute inset-0 flex items-center justify-center rounded-lg bg-gray-50 px-4 text-center text-xs text-gray-500">
                        No student fee data available.
                    </div>
                </div>

                <div class="mt-4 grid grid-cols-2 gap-3 border-t border-gray-100 pt-4">
                    <div class="rounded-lg bg-gray-50 px-3 py-2.5">
                        <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-500">Fees Due</p>
                        <p class="mt-1 text-base font-bold text-gray-900" x-text="formatInr(feeCollectionSummary.scheduled_due ?? feeCollectionSummary.expected)"></p>
                        <p class="mt-1 text-[11px] text-gray-500" x-show="(Number(feeCollectionSummary.pending) || 0) > 0">
                            <span x-text="formatInr(feeCollectionSummary.pending)"></span> still outstanding
                        </p>
                    </div>
                    <div class="rounded-lg bg-indigo-50 px-3 py-2.5">
                        <p class="text-[11px] font-semibold uppercase tracking-wide text-indigo-600">Fee Collected</p>
                        <p class="mt-1 text-base font-bold text-indigo-900" x-text="formatInr(feeCollectionSummary.received)"></p>
                        <p class="mt-1 text-[11px] text-indigo-700/80" x-show="(Number(feeCollectionSummary.other_collected) || 0) > 0">
                            Includes <span x-text="formatInr(feeCollectionSummary.other_collected)"></span> beyond current dues
                        </p>
                    </div>
                </div>
            </div>

            <div class="min-w-0 rounded-xl border border-gray-200 bg-white p-4 shadow-sm xl:col-span-1">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h2 class="text-sm font-semibold text-gray-900">Student Fee Income vs Expenses</h2>
                    </div>
                    <select x-model.number="trendMonths" class="rounded-lg border border-gray-200 bg-white py-1.5 pl-2 pr-8 text-xs font-medium text-gray-700">
                        <option value="3">Last 3 Months</option>
                        <option value="6">Last 6 Months</option>
                        <option value="12">Last 12 Months</option>
                    </select>
                </div>

                <div class="relative mt-4 h-72 min-w-0 w-full">
                    <div x-show="chartsLoading" class="absolute inset-0 z-10 flex items-center justify-center rounded-lg bg-white/80 text-xs text-gray-500">Loading chart...</div>
                    <canvas x-ref="lineChart"></canvas>
                    <div x-show="! chartsLoading && ! lineChartHasData()" class="pointer-events-none absolute inset-0 z-10 flex items-center justify-center rounded-lg bg-gray-50 px-4 text-center text-xs text-gray-500">
                        No financial data available for this period.
                    </div>
                </div>
            </div>

            <div class="min-w-0 rounded-xl border border-gray-200 bg-white p-4 shadow-sm md:col-span-2 xl:col-span-1">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h2 class="text-sm font-semibold text-gray-900">Income, Expenses &amp; Profit</h2>
                    </div>
                    <select x-model.number="trendMonths" class="rounded-lg border border-gray-200 bg-white py-1.5 pl-2 pr-8 text-xs font-medium text-gray-700">
                        <option value="3">Last 3 Months</option>
                        <option value="6">Last 6 Months</option>
                        <option value="12">Last 12 Months</option>
                    </select>
                </div>

                <div class="relative mt-4 h-72 min-w-0 w-full">
                    <div x-show="chartsLoading" class="absolute inset-0 z-10 flex items-center justify-center rounded-lg bg-white/80 text-xs text-gray-500">Loading chart...</div>
                    <canvas x-ref="barChart"></canvas>
                    <div x-show="! chartsLoading && ! barChartHasData()" class="pointer-events-none absolute inset-0 z-10 flex items-center justify-center rounded-lg bg-gray-50 px-4 text-center text-xs text-gray-500">
                        No financial data available for this period.
                    </div>
                </div>
            </div>
        </section>

        <section x-show="activeTab === 'statement'" x-cloak class="mt-4 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <div class="border-b border-gray-100 px-4 py-3">
                <p class="text-sm font-semibold text-gray-900">Financial statement</p>
                <p class="text-xs text-gray-500">Combined fee receipts and operating expenses for the selected period.</p>
            </div>
            <div class="flex flex-wrap items-end gap-3 border-b border-gray-100 px-4 py-3">
                <div class="min-w-[12rem] flex-1">
                    <label class="block text-[11px] font-semibold uppercase tracking-wide text-gray-400">Search</label>
                    <input type="search" x-model="statementSearch" placeholder="Search description, student, branch..." class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-[11px] font-semibold uppercase tracking-wide text-gray-400">Type</label>
                    <select x-model="statementTypeFilter" class="mt-1 min-w-[9rem] rounded-lg border border-gray-300 py-2 pl-3 pr-10 text-sm">
                        <option value="">All types</option>
                        <option value="income">Fee income</option>
                        <option value="expense">Expenses</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[11px] font-semibold uppercase tracking-wide text-gray-400">Payment</label>
                    <select x-model="statementPaymentFilter" class="mt-1 min-w-[9rem] rounded-lg border border-gray-300 py-2 pl-3 pr-10 text-sm">
                        <option value="">All methods</option>
                        <option value="cash">Cash</option>
                        <option value="upi">UPI</option>
                        <option value="card">Card</option>
                        <option value="bank_transfer">Bank transfer</option>
                        <option value="other">Other</option>
                    </select>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[980px]">
                    <thead class="border-b border-gray-200 bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                        <tr>
                            <th class="px-4 py-3">Date</th>
                            <th class="px-4 py-3" x-show="viewingAll">Branch</th>
                            <th class="px-4 py-3">Type</th>
                            <th class="px-4 py-3">Description</th>
                            <th class="px-4 py-3">Payment</th>
                            <th class="px-4 py-3">Recorded by</th>
                            <th class="px-4 py-3 text-right">Amount</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-sm text-gray-700">
                        <template x-for="entry in filteredLedger()" :key="entry.id">
                            <tr class="hover:bg-indigo-50/40">
                                <td class="px-4 py-3" x-text="entry.date"></td>
                                <td class="px-4 py-3" x-show="viewingAll" x-text="entry.branch_name || '—'"></td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex rounded-full px-2 py-0.5 text-[11px] font-semibold" :class="entry.entry_type === 'income' ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-700'" x-text="entry.entry_label"></span>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="font-medium text-gray-900" x-text="entry.title"></div>
                                    <div class="text-xs text-gray-500" x-show="entry.subtitle" x-text="entry.subtitle"></div>
                                </td>
                                <td class="px-4 py-3 capitalize" x-text="entry.payment_method_label"></td>
                                <td class="px-4 py-3" x-text="entry.recorded_by_name || '—'"></td>
                                <td class="px-4 py-3 text-right font-semibold tabular-nums" :class="entry.entry_type === 'income' ? 'text-emerald-700' : 'text-red-700'" x-text="`${entry.entry_type === 'income' ? '+' : '-'}₹${entry.amount}`"></td>
                            </tr>
                        </template>
                        <tr x-show="filteredLedger().length === 0">
                            <td colspan="7" class="px-4 py-10 text-center text-gray-500">No financial activity recorded for this period.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <section x-show="activeTab === 'expenses'" x-cloak class="mt-4 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
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
