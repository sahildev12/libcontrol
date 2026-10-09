{{-- Bulk student import. Expects Alpine state from studentTable: importOpen, importFile, importing, importResult, importBranchId. --}}
<div x-show="importOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" @keydown.escape.window="closeImport()">
    <div class="absolute inset-0 bg-gray-900/60" @click="closeImport()"></div>
    <div class="relative flex max-h-[92vh] w-full max-w-xl flex-col rounded-2xl bg-white shadow-2xl" @click.stop>
        <div class="flex shrink-0 items-start justify-between border-b border-gray-200 px-6 py-4">
            <div>
                <h3 class="text-lg font-bold text-gray-900">Import students</h3>
                <p class="mt-0.5 text-sm text-gray-500">Add many students at once from an Excel or CSV file.</p>
            </div>
            <button type="button" @click="closeImport()" class="inline-flex size-8 items-center justify-center rounded-lg text-gray-400 hover:bg-gray-100 hover:text-gray-600" aria-label="Close">
                <svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <form class="min-h-0 flex-1 space-y-5 overflow-y-auto px-6 py-5" @submit.prevent="submitImport()">
            <div class="flex items-start gap-3 rounded-xl border border-emerald-200 bg-emerald-50 p-4">
                <div class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-emerald-600 text-white">
                    <svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18M10 3v18M5 3h14a2 2 0 012 2v14a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2z"/></svg>
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-semibold text-emerald-900">Step 1 — Download the sample Excel file</p>
                    <a href="{{ route('students.import.template') }}" class="mt-3 inline-flex h-8 items-center gap-1.5 rounded-lg bg-emerald-600 px-3 text-xs font-semibold text-white hover:bg-emerald-700">
                        <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                        Download sample (.xlsx)
                    </a>
                </div>
            </div>

            <div x-show="viewingAll && branches.length > 0">
                <label for="import-branch" class="mb-1 block text-sm font-medium text-gray-700">Import into branch</label>
                <x-admin.select id="import-branch" x-model="importBranchId">
                    <template x-for="branch in branches" :key="branch.id">
                        <option :value="branch.id" x-text="branch.name"></option>
                    </template>
                </x-admin.select>
            </div>

            <div>
                <p class="mb-2 text-sm font-semibold text-gray-900">Step 2 — Upload your filled file</p>
                <label
                    class="flex cursor-pointer flex-col items-center justify-center rounded-xl border-2 border-dashed px-4 py-8 text-center transition"
                    :class="importDragging ? 'border-indigo-400 bg-indigo-50' : (importFile ? 'border-indigo-300 bg-indigo-50/50' : 'border-gray-300 hover:border-indigo-300 hover:bg-gray-50')"
                    @dragover.prevent="importDragging = true"
                    @dragleave.prevent="importDragging = false"
                    @drop.prevent="importDragging = false; pickImportFile($event.dataTransfer.files[0])"
                >
                    <input type="file" class="sr-only" accept=".xlsx,.xls,.csv" x-ref="importInput" @change="pickImportFile($event.target.files[0])">
                    <svg class="size-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                    <template x-if="! importFile">
                        <div class="mt-2">
                            <p class="text-sm font-semibold text-indigo-700">Click to choose a file <span class="font-normal text-gray-500">or drag it here</span></p>
                            <p class="mt-1 text-xs text-gray-500">.xlsx, .xls or .csv · up to 500 students · max 5 MB</p>
                        </div>
                    </template>
                    <template x-if="importFile">
                        <div class="mt-2">
                            <p class="text-sm font-semibold text-gray-900" x-text="importFile.name"></p>
                            <p class="mt-1 text-xs text-gray-500"><span x-text="(importFile.size / 1024).toFixed(1)"></span> KB · click to change</p>
                        </div>
                    </template>
                </label>
            </div>

            <template x-if="importResult && importResult.errors && importResult.errors.length">
                <div class="rounded-xl border border-red-200 bg-red-50 p-4">
                    <p class="text-sm font-semibold text-red-800" x-text="importResult.message"></p>
                    <ul class="mt-3 max-h-48 space-y-1.5 overflow-y-auto text-xs text-red-700">
                        <template x-for="item in importResult.errors" :key="item.row">
                            <li class="flex gap-2">
                                <span class="shrink-0 rounded bg-red-100 px-1.5 py-0.5 font-semibold" x-text="`Row ${item.row}`"></span>
                                <span x-text="item.messages.join(' ')"></span>
                            </li>
                        </template>
                    </ul>
                </div>
            </template>

            <template x-if="importResult && ! (importResult.errors && importResult.errors.length) && importResult.message">
                <p class="rounded-xl px-4 py-3 text-sm font-medium" :class="importResult.ok ? 'bg-emerald-50 text-emerald-800' : 'bg-red-50 text-red-700'" x-text="importResult.message"></p>
            </template>

            <div class="flex justify-end gap-2 border-t border-gray-100 pt-4">
                <button type="button" @click="closeImport()" class="inline-flex h-9 items-center rounded-lg border border-gray-300 bg-white px-4 text-sm font-semibold text-gray-700 hover:bg-gray-50">Cancel</button>
                <button type="submit" :disabled="! importFile || importing" class="inline-flex h-9 items-center gap-2 rounded-lg bg-indigo-600 px-4 text-sm font-semibold text-white hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-60">
                    <svg x-show="importing" class="size-4 animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/></svg>
                    <span x-text="importing ? 'Importing…' : 'Import students'"></span>
                </button>
            </div>
        </form>
    </div>
</div>
