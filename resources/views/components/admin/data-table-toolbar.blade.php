@props([
    'searchPlaceholder' => 'Search...',
    'showBulkDelete' => false,
    'showBulkCancel' => false,
    'colspan' => 6,
])

<div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-200 px-4 py-3">
    <div class="flex flex-wrap items-center gap-2">
        <input
            type="search"
            x-model.debounce.250ms="search"
            placeholder="{{ $searchPlaceholder }}"
            class="w-full min-w-[220px] rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/30 sm:w-72"
        >
        <span class="text-xs text-gray-500" x-text="`${filteredRows().length} result(s)`"></span>
    </div>
    <div class="flex flex-wrap items-center gap-2">
        @if ($showBulkDelete)
            <button
                type="button"
                x-show="selectedIds.length > 1"
                x-cloak
                @click="bulkDelete()"
                class="inline-flex h-9 items-center rounded-lg border border-red-200 bg-red-50 px-3 text-sm font-semibold text-red-700 hover:bg-red-100"
            >
                Delete all (<span x-text="selectedIds.length"></span>)
            </button>
        @endif
        @if ($showBulkCancel)
            <button
                type="button"
                x-show="selectedIds.length > 1"
                x-cloak
                @click="bulkCancel()"
                class="inline-flex h-9 items-center rounded-lg border border-red-200 bg-red-50 px-3 text-sm font-semibold text-red-700 hover:bg-red-100"
            >
                Cancel Selected (<span x-text="selectedIds.length"></span>)
            </button>
        @endif
        <button
            type="button"
            @click="exportRows()"
            class="inline-flex h-9 items-center rounded-lg border border-emerald-200 bg-emerald-50 px-4 text-sm font-semibold text-emerald-700 hover:bg-emerald-100"
        >
            Export CSV
        </button>
        <label class="text-xs font-medium text-gray-600">Rows</label>
        <x-admin.select wrapper-class="relative inline-flex" class="h-9 pl-3 pr-9" x-model.number="perPage">
            <option value="5">5</option>
            <option value="10">10</option>
            <option value="25">25</option>
            <option value="50">50</option>
            <option value="100">100</option>
        </x-admin.select>
    </div>
</div>
