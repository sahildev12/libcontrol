{{-- Read-only seat assignment details. Expects Alpine state from assignmentPage: viewOpen, viewAssignment, viewLoading. --}}
<div x-show="viewOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-gray-900/60" @click="closeView()"></div>
    <div class="relative flex max-h-[92vh] w-full max-w-2xl flex-col rounded-2xl bg-white shadow-2xl" @click.stop>
        <div class="flex shrink-0 items-start justify-between border-b border-gray-200 px-6 py-4">
            <div>
                <h3 class="text-lg font-bold text-gray-900">Assignment Details</h3>
                <p class="mt-0.5 text-sm text-gray-500" x-show="viewAssignment" x-text="`${viewAssignment?.hall_name} • Seat ${viewAssignment?.seat_number}`"></p>
            </div>
            <button type="button" @click="closeView()" class="inline-flex size-8 items-center justify-center rounded-lg text-gray-400 hover:bg-gray-100 hover:text-gray-600" aria-label="Close">
                <svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <div class="min-h-0 flex-1 overflow-y-auto px-6 py-5">
            <div x-show="viewLoading" class="py-12 text-center text-sm text-gray-500">Loading assignment details...</div>

            <div x-show="! viewLoading && viewAssignment" class="space-y-6">
                <section>
                    <h4 class="text-xs font-semibold uppercase tracking-wide text-gray-400">Seat</h4>
                    <div class="mt-3 grid gap-4 sm:grid-cols-2">
                        <div>
                            <div class="text-xs font-medium text-gray-400">Hall</div>
                            <div class="mt-1 text-sm font-medium text-gray-900" x-text="viewAssignment?.hall_name || '—'"></div>
                        </div>
                        <div>
                            <div class="text-xs font-medium text-gray-400">Seat Number</div>
                            <div class="mt-1 text-sm font-medium text-gray-900" x-text="viewAssignment?.seat_number || '—'"></div>
                        </div>
                        <div>
                            <div class="text-xs font-medium text-gray-400">Time Slot</div>
                            <div class="mt-1 text-sm font-medium capitalize text-gray-900" x-text="viewAssignment?.time_slot_label || viewAssignment?.time_slot?.replaceAll('_', ' ') || '—'"></div>
                        </div>
                        <div x-show="viewAssignment?.time_slot === 'custom_hours'">
                            <div class="text-xs font-medium text-gray-400">Custom Hours</div>
                            <div class="mt-1 text-sm font-medium text-gray-900" x-text="`${viewAssignment?.custom_start_time || '—'} to ${viewAssignment?.custom_end_time || '—'}`"></div>
                        </div>
                    </div>
                </section>

                <section class="border-t border-gray-100 pt-5">
                    <h4 class="text-xs font-semibold uppercase tracking-wide text-gray-400">Plan & Fee</h4>
                    <div class="mt-3 grid gap-4 sm:grid-cols-2">
                        <div>
                            <div class="text-xs font-medium text-gray-400">Joining Date</div>
                            <div class="mt-1 text-sm font-medium text-gray-900" x-text="viewAssignment?.joining_date || '—'"></div>
                        </div>
                        <div>
                            <div class="text-xs font-medium text-gray-400">Plan Expiry</div>
                            <div class="mt-1 text-sm font-medium text-gray-900" x-text="viewAssignment?.plan_expiry_date || '—'"></div>
                        </div>
                        <div>
                            <div class="text-xs font-medium text-gray-400">Fee Type</div>
                            <div class="mt-1 text-sm font-medium capitalize text-gray-900" x-text="viewAssignment?.fee_type || '—'"></div>
                        </div>
                        <div>
                            <div class="text-xs font-medium text-gray-400">Fee Amount</div>
                            <div class="mt-1 text-sm font-medium text-gray-900" x-text="viewAssignment?.fee_amount != null ? `₹${viewAssignment.fee_amount}` : '—'"></div>
                        </div>
                        <div x-show="viewAssignment?.fee_type === 'membership'">
                            <div class="text-xs font-medium text-gray-400">Membership Mode</div>
                            <div class="mt-1 text-sm font-medium capitalize text-gray-900" x-text="viewAssignment?.membership_mode_label || viewAssignment?.membership_mode?.replaceAll('_', ' ') || '—'"></div>
                        </div>
                        <div>
                            <div class="text-xs font-medium text-gray-400">Status</div>
                            <div class="mt-1 text-sm font-medium capitalize text-gray-900" x-text="viewAssignment?.status || '—'"></div>
                        </div>
                    </div>
                </section>

                <section class="border-t border-gray-100 pt-5" x-show="viewAssignment?.today_windows?.length">
                    <h4 class="text-xs font-semibold uppercase tracking-wide text-gray-400">Today's schedule</h4>
                    <ul class="mt-3 space-y-1.5">
                        <template x-for="(window, index) in viewAssignment?.today_windows || []" :key="index">
                            <li class="flex items-center justify-between rounded-lg border px-3 py-2 text-xs"
                                :class="{
                                    'border-gray-200 bg-gray-50 text-gray-700': window.type === 'free',
                                    'border-emerald-200 bg-emerald-50 text-emerald-800': window.type === 'booked',
                                    'border-cyan-200 bg-cyan-50 text-cyan-800': window.type === 'trial',
                                }"
                            >
                                <span x-text="`${window.from} – ${window.to}`"></span>
                                <span class="font-semibold" x-text="window.type === 'free' ? 'Vacant' : window.label"></span>
                            </li>
                        </template>
                    </ul>
                </section>

                <section class="border-t border-gray-100 pt-5" x-show="viewAssignment?.student">
                    <h4 class="text-xs font-semibold uppercase tracking-wide text-gray-400">Assigned Student</h4>
                    <div class="mt-3 flex items-start gap-4">
                        <img
                            x-show="viewAssignment?.student?.photo_url"
                            :src="viewAssignment?.student?.photo_url"
                            :alt="viewAssignment?.student?.name"
                            class="size-14 shrink-0 rounded-full border border-gray-200 object-cover"
                        >
                        <div
                            x-show="! viewAssignment?.student?.photo_url"
                            class="flex size-14 shrink-0 items-center justify-center rounded-full border border-indigo-200 bg-indigo-100 text-base font-bold text-indigo-700"
                            x-text="viewAssignment?.student?.initials || '?'"
                        ></div>
                        <div class="min-w-0 flex-1">
                            <div class="text-base font-semibold text-gray-900" x-text="viewAssignment?.student?.name"></div>
                            <div class="text-sm text-gray-500" x-text="viewAssignment?.student?.student_code"></div>
                        </div>
                    </div>

                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        <div>
                            <div class="text-xs font-medium text-gray-400">Contact</div>
                            <div class="mt-1 text-sm font-medium text-gray-900" x-text="viewAssignment?.student?.phone || '—'"></div>
                        </div>
                        <div>
                            <div class="text-xs font-medium text-gray-400">Email</div>
                            <div class="mt-1 text-sm font-medium text-gray-900" x-text="viewAssignment?.student?.email || '—'"></div>
                        </div>
                        <div>
                            <div class="text-xs font-medium text-gray-400">Gender</div>
                            <div class="mt-1 text-sm font-medium text-gray-900" x-text="viewAssignment?.student?.gender_label || '—'"></div>
                        </div>
                        <div>
                            <div class="text-xs font-medium text-gray-400">Father's Name</div>
                            <div class="mt-1 text-sm font-medium text-gray-900" x-text="viewAssignment?.student?.father_name || '—'"></div>
                        </div>
                        <div class="sm:col-span-2">
                            <div class="text-xs font-medium text-gray-400">Address</div>
                            <div class="mt-1 text-sm font-medium text-gray-900" x-text="viewAssignment?.student?.address || '—'"></div>
                        </div>
                        <div>
                            <div class="text-xs font-medium text-gray-400">ID Document Type</div>
                            <div class="mt-1 text-sm font-medium text-gray-900" x-text="viewAssignment?.student?.id_proof_type || '—'"></div>
                        </div>
                        <div>
                            <div class="text-xs font-medium text-gray-400">ID Document</div>
                            <div class="mt-1">
                                <a
                                    x-show="viewAssignment?.student?.id_proof_url"
                                    :href="viewAssignment?.student?.id_proof_url"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="inline-flex items-center gap-1 text-sm font-semibold text-indigo-600 hover:text-indigo-800"
                                >
                                    View Doc
                                    <svg class="size-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                </a>
                                <span x-show="! viewAssignment?.student?.id_proof_url" class="text-sm text-gray-500">Not uploaded</span>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </div>

        <div class="flex shrink-0 justify-end gap-2 border-t border-gray-200 px-6 py-4">
            <button type="button" @click="closeView()" class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Close</button>
            <button
                type="button"
                x-show="viewAssignment"
                @click="cancelFromView()"
                class="rounded-lg border border-red-200 bg-red-50 px-4 py-2 text-sm font-semibold text-red-700 hover:bg-red-100"
            >
                Cancel Assignment
            </button>
        </div>
    </div>
</div>
