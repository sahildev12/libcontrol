{{-- Full seat schedule modal (shared by seats + trial-seats) --}}
<div x-show="scheduleOpen" x-cloak class="fixed inset-0 z-[70] flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-gray-900/50" @click="closeFullSchedule()"></div>
    <div class="relative flex max-h-[90vh] w-full max-w-2xl flex-col rounded-xl bg-white shadow-xl" @click.stop>
        <div class="flex shrink-0 items-center justify-between border-b border-gray-200 px-5 py-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-indigo-600">Full Seat Schedule</p>
                <h3 class="text-lg font-semibold text-gray-900">
                    Seat <span x-text="scheduleSeat?.seat_number"></span>
                    <span class="text-sm font-medium text-gray-500" x-text="scheduleSeat?.hall_name ? ` • ${scheduleSeat.hall_name}` : ''"></span>
                </h3>
            </div>
            <button
                type="button"
                @click="closeFullSchedule()"
                class="inline-flex size-8 items-center justify-center rounded-lg text-gray-400 hover:bg-gray-100 hover:text-gray-600"
                aria-label="Close"
            >
                <svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <!-- <div class="flex shrink-0 items-center justify-between gap-3 border-b border-gray-100 px-5 py-3">
            <p class="text-sm font-medium text-gray-700">Assignments</p>
            <div class="flex items-center gap-2">
                <button type="button" @click="shiftScheduleDate(-1)" class="inline-flex size-8 items-center justify-center rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50">‹</button>
                <input type="date" x-model="scheduleDate" @change="loadSeatSchedule()" class="rounded-lg border border-gray-300 px-2 py-1.5 text-sm">
                <button type="button" @click="shiftScheduleDate(1)" class="inline-flex size-8 items-center justify-center rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50">›</button>
            </div>
        </div> -->

        <div class="min-h-0 flex-1 overflow-y-auto px-5 py-4">
            <p x-show="scheduleLoading" class="py-10 text-center text-sm text-gray-500">Loading schedule...</p>
            <p x-show="! scheduleLoading && scheduleBookings.length === 0" class="py-10 text-center text-sm text-gray-500">No assignments for this date.</p>

            <div x-show="! scheduleLoading && scheduleBookings.length" class="space-y-3">
                <template x-for="booking in scheduleBookings" :key="booking.id">
                    <div class="rounded-xl border border-gray-200 p-3">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div class="min-w-0 flex-1">
                                <div class="mb-2 flex flex-wrap items-center gap-2">
                                    <p class="text-sm font-semibold text-gray-900" x-text="booking.time_slot_label || `${booking.from || ''} – ${booking.to || ''}`"></p>
                                    <span
                                        class="rounded-full px-2 py-0.5 text-[10px] font-semibold"
                                        :class="booking.is_trial ? 'bg-cyan-100 text-cyan-800' : 'bg-indigo-100 text-indigo-800'"
                                        x-text="booking.is_trial ? 'Trial' : 'Regular'"
                                    ></span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span
                                        class="inline-flex size-8 shrink-0 items-center justify-center rounded-full text-xs font-semibold text-white"
                                        :class="booking.is_trial ? 'bg-cyan-500' : 'bg-indigo-500'"
                                        x-text="booking.student_initial || (booking.student_name || '?').slice(0, 1).toUpperCase()"
                                    ></span>
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-medium text-gray-900">
                                            <span x-text="booking.student_name"></span>
                                            <span class="text-gray-500" x-text="booking.student_code ? ` (${booking.student_code})` : ''"></span>
                                        </p>
                                        <p class="text-[11px] text-gray-500">
                                            Booking ID: <span x-text="booking.id"></span>
                                            · Join: <span x-text="booking.joining_date || '—'"></span>
                                            · Expiry: <span x-text="booking.plan_expiry_date || '—'"></span>
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <div class="flex shrink-0 flex-col gap-2 sm:items-end">
                                <button
                                    type="button"
                                    x-show="booking.is_trial"
                                    @click="convertScheduleBookingToRegular(booking)"
                                    :disabled="scheduleSaving"
                                    class="inline-flex items-center gap-1.5 rounded-lg border border-emerald-200 bg-emerald-50 px-2.5 py-1.5 text-xs font-semibold text-emerald-800 hover:bg-emerald-100 disabled:opacity-50"
                                >
                                    Convert to Regular
                                </button>
                                <button
                                    type="button"
                                    @click="cancelScheduleBooking(booking)"
                                    :disabled="scheduleSaving"
                                    class="inline-flex items-center gap-1.5 rounded-lg border border-red-200 bg-red-50 px-2.5 py-1.5 text-xs font-semibold text-red-700 hover:bg-red-100 disabled:opacity-50"
                                >
                                    <svg class="size-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    Cancel Booking
                                </button>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <div class="flex shrink-0 flex-wrap items-center justify-between gap-3 border-t border-gray-200 px-5 py-4">
            <button type="button" @click="closeFullSchedule()" class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Close</button>
        </div>
    </div>
</div>
