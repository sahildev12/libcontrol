<x-admin-layout>
    <div
        x-data="trialSeatMap({
            halls: @js($halls),
            seats: @js($seats),
            students: @js($students),
            timeSlotOptions: @js($timeSlotOptions),
            storeUrl: @js(route('trial-seats.store')),
            dataUrl: @js(route('trial-seats.data')),
            availableSeatsUrl: @js(route('trial-seats.available-seats')),
            storeStudentUrl: @js(route('students.store')),
            inviteStoreUrl: @js(route('students.registration-invites.store')),
            selectedHallId: 'all',
        })"
    >
        <header class="flex flex-wrap items-start justify-between gap-4 p-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Trial Seats</h1>
                <p class="mt-1 text-sm text-gray-600">Assign short trial windows using current library hours.</p>
            </div>
            <div class="flex items-center gap-2">
                <button type="button" @click="zoomOut()" class="inline-flex size-9 items-center justify-center rounded-lg border border-gray-200 bg-gray-50 text-gray-700 hover:bg-white">−</button>
                <span class="min-w-12 text-center text-sm font-medium text-gray-700" x-text="`${zoom}%`"></span>
                <button type="button" @click="zoomIn()" class="inline-flex size-9 items-center justify-center rounded-lg border border-gray-200 bg-gray-50 text-gray-700 hover:bg-white">+</button>
            </div>
        </header>

        <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <div class="flex flex-wrap gap-2 border-b border-gray-200 bg-gray-50 px-4 py-3">
                <button
                    type="button"
                    @click="setHall('all')"
                    class="rounded-full px-3 py-1.5 text-xs font-semibold transition-colors"
                    :class="selectedHallId === 'all' ? 'bg-indigo-600 text-white' : 'bg-white text-gray-700 border border-gray-200 hover:bg-gray-100'"
                >
                    All Halls
                </button>
                <template x-for="hall in halls" :key="hall.id">
                    <button
                        type="button"
                        @click="setHall(hall.id)"
                        class="rounded-full px-3 py-1.5 text-xs font-semibold transition-colors"
                        :class="String(selectedHallId) === String(hall.id) ? 'bg-indigo-600 text-white' : 'bg-white text-gray-700 border border-gray-200 hover:bg-gray-100'"
                        x-text="hall.name"
                    ></button>
                </template>
            </div>

            <div class="flex flex-wrap gap-x-4 gap-y-2 border-b border-gray-200 px-4 py-3 text-xs font-medium text-gray-700">
                <span class="inline-flex items-center gap-1.5"><span class="size-3 rounded border border-gray-400 bg-gray-300"></span> Vacant</span>
                <span class="inline-flex items-center gap-1.5"><span class="size-3 rounded border border-emerald-600 bg-emerald-500"></span> Occupied</span>
                <span class="inline-flex items-center gap-1.5"><span class="size-3 rounded border border-amber-500 bg-amber-400"></span> Expiring Soon</span>
                <span class="inline-flex items-center gap-1.5"><span class="size-3 rounded border border-red-500 bg-red-200 ring-1 ring-red-300 ring-dashed"></span> Expired</span>
                <span class="inline-flex items-center gap-1.5"><span class="size-3 rounded border border-cyan-500 bg-cyan-400"></span> Trial</span>
            </div>

            <div class="overflow-auto bg-[#eceff3] p-4 md:p-6">
                <div
                    class="mx-auto origin-top transition-transform duration-150"
                    :style="`transform: scale(${zoom / 100}); width: ${zoom}%;`"
                >
                    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4 md:grid-cols-6 lg:grid-cols-8 xl:grid-cols-10">
                        <template x-for="seat in filteredSeats()" :key="seat.id">
                            <button
                                type="button"
                                @click="openSeat(seat)"
                                :class="seatClasses(seat)"
                                :title="`${seat.hall_name} • Seat ${seat.seat_number} • ${statusLabel(displayStatus(seat))}`"
                            >
                                <div class="flex flex-1 flex-col items-center justify-center gap-1">
                                    <svg class="size-6 opacity-70" fill="currentColor" viewBox="0 0 24 24"><path d="M5 12V7a2 2 0 012-2h10a2 2 0 012 2v5M5 12l-1 8h16l-1-8M5 12h14"/></svg>
                                    <span class="text-lg font-bold leading-none" x-text="seat.seat_number"></span>
                                </div>

                                <div class="w-full truncate text-[10px] font-semibold">
                                    <span x-show="seat.student_code && displayStatus(seat) !== 'available'" x-text="seat.student_code"></span>
                                    <span x-show="!seat.student_code || displayStatus(seat) === 'available'" x-text="statusLabel(displayStatus(seat))"></span>
                                </div>
                            </button>
                        </template>
                    </div>

                    <p x-show="filteredSeats().length === 0" class="py-16 text-center text-sm text-gray-500">
                        No seats found for this hall.
                    </p>
                </div>
            </div>
        </section>

        <div x-show="detailOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-gray-900/50" @click="closeDetail()"></div>
            <div class="relative flex max-h-[90vh] w-full max-w-lg flex-col rounded-xl bg-white shadow-xl" @click.stop>
                <div class="flex shrink-0 items-center justify-between border-b border-gray-200 px-5 py-4">
                    <h3 class="text-lg font-semibold text-gray-900" x-text="assignMode ? 'Assign Trial Seat' : 'Seat Details'"></h3>
                    <button
                        type="button"
                        @click="closeDetail()"
                        class="inline-flex size-8 items-center justify-center rounded-lg text-gray-400 hover:bg-gray-100 hover:text-gray-600"
                        aria-label="Close"
                    >
                        <svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="min-h-0 flex-1 overflow-y-auto p-5 text-sm">
                    <div class="space-y-4">
                        <div class="flex items-center justify-between gap-4">
                            <div>
                                <div class="text-xs font-medium uppercase tracking-wide text-gray-400">Seat Number</div>
                                <div class="mt-1 text-2xl font-bold text-gray-900">
                                    <span x-text="selectedSeat?.seat_number"></span>
                                </div>
                            </div>
                            <div
                                class="inline-flex rounded-full px-3 py-1.5 text-xs font-semibold"
                                :class="{
                                    'bg-gray-300 text-gray-800': displayStatus(selectedSeat) === 'available',
                                    'bg-emerald-500 text-white': displayStatus(selectedSeat) === 'occupied',
                                    'bg-amber-400 text-amber-950': displayStatus(selectedSeat) === 'expiring_soon',
                                    'bg-red-200 text-red-900': displayStatus(selectedSeat) === 'expired',
                                    'bg-cyan-400 text-cyan-950': displayStatus(selectedSeat) === 'on_trial',
                                }"
                                x-text="statusLabel(displayStatus(selectedSeat))"
                            ></div>
                        </div>

                        <div x-show="selectedSeat?.student_name" class="grid grid-cols-2 gap-3 border-t border-gray-100 pt-3">
                            <div>
                                <div class="text-xs font-medium text-gray-400">Student</div>
                                <div class="mt-1 text-sm font-medium text-gray-900" x-text="selectedSeat?.student_name"></div>
                            </div>
                            <div x-show="selectedSeat?.student_code">
                                <div class="text-xs font-medium text-gray-400">Student Code</div>
                                <div class="mt-1 text-sm font-medium text-gray-900" x-text="selectedSeat?.student_code"></div>
                            </div>
                        </div>

                        <div x-show="selectedSeat?.today_windows?.length" class="border-t border-gray-100 pt-3">
                            <div class="text-xs font-medium uppercase tracking-wide text-gray-400">Today's schedule</div>
                            <ul class="mt-2 space-y-1.5">
                                <template x-for="(window, index) in selectedSeat?.today_windows || []" :key="index">
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
                        </div>
                    </div>

                    <form id="trial-assign-form" x-show="assignMode" @submit.prevent="submitAssign()" class="mt-5 space-y-4 border-t border-gray-200 pt-5">
                        <x-admin.student-search-select required />
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Trial start</label>
                                <input type="date" x-model="assignForm.trial_start" required class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Duration (days)</label>
                                <input type="number" min="1" max="14" x-model.number="assignForm.trial_days" required class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm">
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Time Slot</label>
                            <select x-model="assignForm.time_slot" required class="admin-select mt-1 block w-full px-3 py-2">
                                <template x-for="option in timeSlotOptions" :key="option.value">
                                    <option :value="option.value" x-text="option.label"></option>
                                </template>
                            </select>
                            <p x-show="assignmentTimeError()" class="mt-1 text-xs text-red-600" x-text="assignmentTimeError()"></p>
                        </div>
                        <div x-show="assignForm.time_slot === 'custom_hours'" class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Start Time</label>
                                <input type="time" x-model="assignForm.custom_start_time" class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">End Time</label>
                                <input type="time" x-model="assignForm.custom_end_time" class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm">
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Trial fee (optional)</label>
                            <input type="number" min="0" step="0.01" x-model.number="assignForm.fee_amount" class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm">
                        </div>
                        <p class="text-xs text-gray-500" x-show="selectedSeat?.today_windows?.length">
                            Free hours today:
                            <span x-text="freeHoursLabel(selectedSeat)"></span>
                        </p>
                    </form>
                </div>

                <div class="flex shrink-0 flex-wrap justify-end gap-2 border-t border-gray-200 px-5 py-4">
                    <button type="button" @click="closeDetail()" class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Close</button>
                    <button
                        type="submit"
                        form="trial-assign-form"
                        x-show="assignMode"
                        :disabled="saving || Boolean(assignmentTimeError())"
                        class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700 disabled:opacity-60"
                    >
                        <span x-show="! saving">Assign Trial</span>
                        <span x-show="saving">Assigning...</span>
                    </button>
                </div>
            </div>
        </div>

        <x-admin.student-create-modal />
    </div>
</x-admin-layout>
