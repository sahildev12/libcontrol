@props(['colspan' => 6])

<div class="flex flex-wrap items-center justify-between gap-3 border-t border-gray-200 px-4 py-3 text-sm text-gray-600">
    <p x-text="`Showing ${pageStart()}–${pageEnd()} of ${filteredRows().length}`"></p>
    <div class="flex items-center gap-2">
        <button type="button" @click="prevPage()" :disabled="page === 1" class="rounded-lg border border-gray-200 px-3 py-1.5 hover:bg-gray-50 disabled:opacity-50">Prev</button>
        <span x-text="`Page ${page} / ${totalPages()}`"></span>
        <button type="button" @click="nextPage()" :disabled="page >= totalPages()" class="rounded-lg border border-gray-200 px-3 py-1.5 hover:bg-gray-50 disabled:opacity-50">Next</button>
    </div>
</div>
