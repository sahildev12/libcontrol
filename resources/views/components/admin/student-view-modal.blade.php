{{-- Read-only student details modal. Expects Alpine state from studentTable: viewOpen, viewStudent, viewLoading. --}}
<div x-show="viewOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-gray-900/60" @click="closeView()"></div>
    <div class="relative flex max-h-[92vh] w-full max-w-2xl flex-col rounded-2xl bg-white shadow-2xl" @click.stop>
        <div class="flex shrink-0 items-start justify-between border-b border-gray-200 px-6 py-4">
            <div class="flex items-center gap-4">
                <div class="relative shrink-0">
                    <img
                        x-show="viewStudent?.photo_url"
                        :src="viewStudent?.photo_url"
                        :alt="viewStudent?.name"
                        class="size-16 rounded-full border border-gray-200 object-cover"
                    >
                    <div
                        x-show="! viewStudent?.photo_url"
                        class="flex size-16 items-center justify-center rounded-full border border-indigo-200 bg-indigo-100 text-lg font-bold text-indigo-700"
                        x-text="viewStudent?.initials || '?'"
                    ></div>
                </div>
                <div>
                    <h3 class="text-lg font-bold text-gray-900" x-text="viewStudent?.name || 'Student Details'"></h3>
                    <p class="text-sm text-gray-500" x-text="viewStudent?.student_code"></p>
                </div>
            </div>
            <button type="button" @click="closeView()" class="inline-flex size-8 items-center justify-center rounded-lg text-gray-400 hover:bg-gray-100 hover:text-gray-600" aria-label="Close">
                <svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <div class="min-h-0 flex-1 overflow-y-auto px-6 py-5">
            <div x-show="viewLoading" class="py-12 text-center text-sm text-gray-500">Loading student details...</div>

            <div x-show="! viewLoading && viewStudent" class="space-y-5">
                <div class="flex items-center gap-2">
                    <span
                        class="inline-flex rounded-full px-3 py-1 text-xs font-semibold capitalize"
                        :class="viewStudent?.status === 'active' ? 'bg-emerald-100 text-emerald-800' : 'bg-gray-100 text-gray-700'"
                        x-text="viewStudent?.status"
                    ></span>
                    <span class="inline-flex rounded-full bg-indigo-50 px-3 py-1 text-xs font-semibold text-indigo-800" x-text="viewStudent?.student_type_label || (viewStudent?.student_type === 'trial' ? 'Trial Student' : 'Regular Student')"></span>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <div class="text-xs font-medium uppercase tracking-wide text-gray-400">Gender</div>
                        <div class="mt-1 text-sm font-medium text-gray-900" x-text="viewStudent?.gender_label || '—'"></div>
                    </div>
                    <div>
                        <div class="text-xs font-medium uppercase tracking-wide text-gray-400">Date of Birth</div>
                        <div class="mt-1 text-sm font-medium text-gray-900" x-text="viewStudent?.date_of_birth_label || viewStudent?.date_of_birth || '—'"></div>
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <div class="text-xs font-medium uppercase tracking-wide text-gray-400">Contact</div>
                        <div class="mt-1 text-sm font-medium text-gray-900" x-text="viewStudent?.phone || '—'"></div>
                    </div>
                    <div>
                        <div class="text-xs font-medium uppercase tracking-wide text-gray-400">Email</div>
                        <div class="mt-1 text-sm font-medium text-gray-900" x-text="viewStudent?.email || '—'"></div>
                    </div>
                </div>

                <div>
                    <div class="text-xs font-medium uppercase tracking-wide text-gray-400">Father's Name</div>
                    <div class="mt-1 text-sm font-medium text-gray-900" x-text="viewStudent?.father_name || '—'"></div>
                </div>

                <div>
                    <div class="text-xs font-medium uppercase tracking-wide text-gray-400">Address</div>
                    <div class="mt-1 text-sm font-medium text-gray-900" x-text="viewStudent?.address || '—'"></div>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <div class="text-xs font-medium uppercase tracking-wide text-gray-400">ID Document Type</div>
                        <div class="mt-1 text-sm font-medium text-gray-900" x-text="viewStudent?.id_proof_type || '—'"></div>
                    </div>
                    <div>
                        <div class="text-xs font-medium uppercase tracking-wide text-gray-400">ID Document</div>
                        <div class="mt-1 flex items-center gap-2">
                            <span class="text-sm font-medium text-gray-900" x-text="viewStudent?.has_id_proof ? 'On file' : 'Not uploaded'"></span>
                            <a
                                x-show="viewStudent?.id_proof_url"
                                :href="viewStudent?.id_proof_url"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="inline-flex items-center gap-1 text-sm font-semibold text-indigo-600 hover:text-indigo-800"
                            >
                                View Doc
                                <svg class="size-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                            </a>
                        </div>
                    </div>
                </div>

                <div class="grid gap-4 border-t border-gray-100 pt-4 sm:grid-cols-2">
                    <div>
                        <div class="text-xs font-medium uppercase tracking-wide text-gray-400">Created</div>
                        <div class="mt-1 text-sm text-gray-700" x-text="viewStudent?.created_at || '—'"></div>
                    </div>
                    <div>
                        <div class="text-xs font-medium uppercase tracking-wide text-gray-400">Last Updated</div>
                        <div class="mt-1 text-sm text-gray-700" x-text="viewStudent?.updated_at || '—'"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex shrink-0 justify-end gap-2 border-t border-gray-200 px-6 py-4">
            <button type="button" @click="closeView()" class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Close</button>
            <button
                type="button"
                x-show="viewStudent"
                @click="openEditFromView()"
                class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700"
            >
                Edit Student
            </button>
        </div>
    </div>
</div>
