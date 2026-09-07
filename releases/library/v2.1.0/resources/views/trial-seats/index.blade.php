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
            branches: @js($branches ?? []),
            defaultBranchId: @js($defaultBranchId ?? null),
            viewingAll: @js($viewingAll ?? false),
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

            <div class="flex flex-wrap gap-x-2 gap-y-2 border-b border-gray-200 px-4 py-3 text-xs font-medium text-gray-700">
                <button type="button" @click="toggleStatusFilter('')" class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 transition-colors" :class="! statusFilter ? 'border-indigo-500 bg-indigo-50 text-indigo-900' : 'border-transparent hover:bg-gray-100'">
                    All
                </button>
                <button type="button" @click="toggleStatusFilter('available')" class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 transition-colors" :class="statusFilter === 'available' ? 'border-gray-400 bg-[#E5E7EB] text-gray-900' : 'border-transparent hover:bg-gray-100'">
                    <span class="size-3 rounded bg-[#E5E7EB] ring-1 ring-gray-300"></span> Vacant
                </button>
                <button type="button" @click="toggleStatusFilter('occupied')" class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 transition-colors" :class="statusFilter === 'occupied' ? 'border-green-600 bg-green-50 text-green-900' : 'border-transparent hover:bg-gray-100'">
                    <span class="size-3 rounded bg-[#16A34A]"></span> Occupied (Full Day)
                </button>
                <button type="button" @click="toggleStatusFilter('occupied_custom')" class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 transition-colors" :class="statusFilter === 'occupied_custom' ? 'border-indigo-500 bg-indigo-50 text-indigo-900' : 'border-transparent hover:bg-gray-100'">
                    <span class="size-3 rounded bg-[#6366F1]"></span> Occupied (Custom Hours)
                </button>
                <button type="button" @click="toggleStatusFilter('expiring_soon')" class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 transition-colors" :class="statusFilter === 'expiring_soon' ? 'border-amber-500 bg-amber-50 text-amber-950' : 'border-transparent hover:bg-gray-100'">
                    <span class="size-3 rounded bg-[#F59E0B]"></span> Expiring Soon
                </button>
                <button type="button" @click="toggleStatusFilter('expired')" class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 transition-colors" :class="statusFilter === 'expired' ? 'border-red-500 bg-red-50 text-red-900' : 'border-transparent hover:bg-gray-100'">
                    <span class="size-3 rounded bg-[#EF4444]"></span> Expired
                </button>
                <button type="button" @click="toggleStatusFilter('on_trial')" class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 transition-colors" :class="statusFilter === 'on_trial' ? 'border-cyan-500 bg-cyan-50 text-cyan-950' : 'border-transparent hover:bg-gray-100'">
                    <span class="size-3 rounded bg-[#06B6D4]"></span> Trial
                </button>
            </div>

            <div class="overflow-auto bg-[#eceff3] p-4 md:p-6">
                <div
                    class="mx-auto origin-top transition-transform duration-150"
                    :style="`transform: scale(${zoom / 100}); width: ${zoom}%;`"
                >
                    <template x-for="group in filteredSeatGroups()" :key="group.hall_id">
                        <div class="mb-6 last:mb-0">
                            <div class="mb-3 flex items-center gap-3">
                                <h3 class="shrink-0 text-sm font-semibold text-gray-800" x-text="group.hall_name"></h3>
                                <hr class="min-w-0 flex-1 border-gray-300">
                            </div>
                            <div class="grid grid-cols-2 gap-3 sm:grid-cols-4 md:grid-cols-6 lg:grid-cols-8 xl:grid-cols-10">
                                <template x-for="seat in group.seats" :key="seat.id">
                                    <div
                                        class="relative w-full"
                                        @mouseenter="startSeatHover(seat, $event)"
                                        @mouseleave="cancelSeatHover()"
                                    >
                                        <button
                                            type="button"
                                            @click="openSeat(seat); cancelSeatHover(true)"
                                            :class="seatClasses(seat)"
                                            style="border: 1px solid lightgray;"
                                        >
                                            <span
                                                x-show="showsTrialDot(seat)"
                                                class="absolute right-1.5 top-1.5 size-2.5 rounded-full bg-[#06B6D4] ring-2 ring-white"
                                                title="Trial student also assigned"
                                            ></span>
                                            <div class="flex flex-1 flex-col items-center justify-center gap-1">
                                                <svg class="size-6 opacity-70" fill="currentColor" viewBox="0 0 24 24"><path d="M5 12V7a2 2 0 012-2h10a2 2 0 012 2v5M5 12l-1 8h16l-1-8M5 12h14"/></svg>
                                                <span class="text-lg font-bold leading-none" x-text="seat.seat_number"></span>
                                            </div>

                                            <div class="w-full truncate text-[10px] font-semibold">
                                                <span x-show="seat.student_code && displayStatus(seat) !== 'available'" x-text="seat.student_code"></span>
                                                <span x-show="!seat.student_code || displayStatus(seat) === 'available'" x-text="statusLabel(displayStatus(seat))"></span>
                                            </div>
                                        </button>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>

                    <p x-show="filteredSeatGroups().length === 0" class="py-16 text-center text-sm text-gray-500">
                        No seats found for this hall.
                    </p>
                </div>
            </div>
        </section>

        {{-- Seat hover details (shows after 2s on non-vacant seats) --}}
        <div
            x-show="hoverOpen && hoverSeat"
            x-cloak
            class="fixed z-[60] w-[320px] rounded-xl border border-gray-200 bg-white p-3 shadow-xl"
            :style="`top: ${hoverStyle.top}px; left: ${hoverStyle.left}px;`"
            @mouseenter="keepSeatHover()"
            @mouseleave="cancelSeatHover(true)"
        >
            <div class="mb-2">
                <h4 class="text-sm font-semibold text-gray-900">
                    Seat <span x-text="hoverSeat?.seat_number"></span>
                    <span class="font-medium text-gray-500" x-text="hoverSeat?.hall_name ? ` • ${hoverSeat.hall_name}` : ''"></span>
                </h4>
            </div>

            <div class="mb-3">
                <div class="mb-1.5 flex items-center justify-between gap-2">
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-400">Seat Schedule</p>
                    <p class="text-[11px] text-gray-500" x-text="hoverScheduleDateLabel()"></p>
                </div>
                <div class="max-h-44 space-y-2 overflow-y-auto">
                    <template x-for="(window, index) in occupiedSchedule(hoverSeat)" :key="index">
                        <div
                            class="rounded-lg border px-2.5 py-2"
                            :class="window.type === 'trial' ? 'border-cyan-100 bg-cyan-50/70' : 'border-indigo-100 bg-indigo-50/70'"
                        >
                            <div class="mb-1 flex items-center justify-between gap-2">
                                <p
                                    class="text-xs font-semibold"
                                    :class="window.type === 'trial' ? 'text-cyan-700' : 'text-indigo-700'"
                                    x-text="`${window.from} – ${window.to}`"
                                ></p>
                                <span
                                    class="rounded-full px-2 py-0.5 text-[10px] font-semibold"
                                    :class="window.type === 'trial' ? 'bg-cyan-100 text-cyan-800' : 'bg-indigo-100 text-indigo-800'"
                                    x-text="window.type === 'trial' ? 'Trial' : 'Regular'"
                                ></span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span
                                    class="inline-flex size-7 shrink-0 items-center justify-center rounded-full text-[11px] font-semibold text-white"
                                    :class="window.type === 'trial' ? 'bg-cyan-500' : 'bg-indigo-500'"
                                    x-text="window.student_initial || (window.student_name || window.label || '?').slice(0, 1).toUpperCase()"
                                ></span>
                                <div class="min-w-0">
                                    <p class="truncate text-xs font-medium text-gray-900" x-text="window.student_name || window.label"></p>
                                    <p class="truncate text-[11px] text-gray-500" x-text="window.student_code || ''"></p>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <div class="rounded-lg border border-gray-100 bg-gray-50 px-2.5 py-2 text-xs text-gray-700">
                <div class="mb-1.5 text-[11px] font-semibold uppercase tracking-wide text-gray-400">About this seat</div>
                <div class="space-y-1">
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-gray-500">Seat Type</span>
                        <span class="font-medium" x-text="hoverSeatTypeLabel()"></span>
                    </div>
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-gray-500">Today's Status</span>
                        <span class="inline-flex items-center gap-1.5 font-medium">
                            <span
                                class="size-2 rounded-full"
                                :class="{
                                    'bg-[#16A34A]': displayStatus(hoverSeat) === 'occupied',
                                    'bg-[#6366F1]': displayStatus(hoverSeat) === 'occupied_custom',
                                    'bg-[#F59E0B]': displayStatus(hoverSeat) === 'expiring_soon',
                                    'bg-[#EF4444]': displayStatus(hoverSeat) === 'expired',
                                    'bg-[#06B6D4]': displayStatus(hoverSeat) === 'on_trial',
                                }"
                            ></span>
                            <span x-text="statusLabel(displayStatus(hoverSeat))"></span>
                        </span>
                    </div>
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-gray-500">Trial Booking</span>
                        <span class="font-medium" x-text="seatHasTrial(hoverSeat) ? 'Yes' : 'No'"></span>
                    </div>
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-gray-500">Total Booked Slots</span>
                        <span class="font-medium" x-text="occupiedSchedule(hoverSeat).length"></span>
                    </div>
                </div>
            </div>

            <button
                type="button"
                class="mt-3 flex w-full items-center justify-center gap-2 rounded-lg border border-indigo-200 px-3 py-2 text-sm font-semibold text-indigo-700 hover:bg-indigo-50"
                @click="openFullSchedule(hoverSeat)"
            >
                <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3M5 11h14M5 21h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                View Full Schedule
            </button>
        </div>

        {{-- Seat detail / assign modal --}}
        <div x-show="detailOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-gray-900/50" @click="closeDetail()"></div>
            <div class="relative flex max-h-[90vh] w-full max-w-lg flex-col rounded-xl bg-white shadow-xl" @click.stop>
                <div class="flex shrink-0 items-center justify-between border-b border-gray-200 px-5 py-4">
                    <h3 class="text-lg font-semibold text-gray-900">
                        Seat <span x-text="selectedSeat?.seat_number"></span>
                        <span class="text-sm font-medium text-gray-500" x-text="selectedSeat?.hall_name ? ` · ${selectedSeat.hall_name}` : ''"></span>
                    </h3>
                    <button
                        type="button"
                        @click="closeDetail()"
                        class="inline-flex size-8 items-center justify-center rounded-lg text-gray-400 hover:bg-gray-100 hover:text-gray-600"
                        aria-label="Close"
                    >
                        <svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="min-h-0 flex-1 overflow-y-auto px-5 pb-5 pt-3 text-sm">
                    <div x-show="! assignMode" class="rounded-lg border border-gray-200 bg-gray-50 px-3 py-2.5">
                        <div class="flex items-center justify-between gap-3">
                            <div class="min-w-0">
                                <div class="mt-0 flex flex-wrap gap-x-3 gap-y-0.5 text-xs text-gray-700">
                                    <span x-show="selectedSeat?.student_name">
                                        <span class="text-gray-400">Student</span>
                                        <span class="font-medium" x-text="`${selectedSeat?.student_name}${selectedSeat?.student_code ? ' · ' + selectedSeat.student_code : ''}`"></span>
                                    </span>
                                    <span x-show="selectedSeat?.time_slot">
                                        <span class="text-gray-400">Slot</span>
                                        <span class="font-medium" x-text="selectedSeat?.time_slot_label || selectedSeat?.time_slot?.replace('_', ' ')"></span>
                                    </span>
                                    <span x-show="selectedSeat?.plan_expiry_date">
                                        <span class="text-gray-400">Expiry</span>
                                        <span class="font-medium" x-text="selectedSeat?.plan_expiry_date"></span>
                                    </span>
                                </div>
                            </div>
                            <div
                                class="shrink-0 inline-flex rounded-full px-2.5 py-1 text-[11px] font-semibold"
                                :class="{
                                    'bg-[#E5E7EB] text-gray-800': displayStatus(selectedSeat) === 'available',
                                    'bg-[#16A34A] text-white': displayStatus(selectedSeat) === 'occupied',
                                    'bg-[#6366F1] text-white': displayStatus(selectedSeat) === 'occupied_custom',
                                    'bg-[#F59E0B] text-amber-950': displayStatus(selectedSeat) === 'expiring_soon',
                                    'bg-[#EF4444] text-white': displayStatus(selectedSeat) === 'expired',
                                    'bg-[#06B6D4] text-cyan-950': displayStatus(selectedSeat) === 'on_trial',
                                }"
                                x-text="statusLabel(displayStatus(selectedSeat))"
                            ></div>
                        </div>
                        <div x-show="occupiedSchedule(selectedSeat).length" class="mt-2 border-t border-gray-200 pt-2">
                            <div class="mb-1 text-[10px] font-semibold uppercase tracking-wide text-gray-400">Occupied today</div>
                            <div class="flex flex-wrap gap-1">
                                <template x-for="(window, index) in occupiedSchedule(selectedSeat)" :key="index">
                                    <span class="inline-flex items-center gap-1 rounded-md px-2 py-0.5 text-[11px] font-medium"
                                        :class="window.type === 'trial' ? 'bg-cyan-100 text-cyan-900' : (window.time_slot === 'custom_hours' ? 'bg-indigo-100 text-indigo-900' : 'bg-emerald-100 text-emerald-900')"
                                    >
                                        <span x-text="`${window.from} – ${window.to}`"></span>
                                        <span class="opacity-80" x-text="window.label"></span>
                                    </span>
                                </template>
                            </div>
                        </div>
                        <p x-show="canAddAnotherStudent()" class="mt-2 text-xs text-gray-500">This seat still has free hours. Use Add Student to assign another trial to an open slot.</p>
                    </div>

                    <form id="trial-assign-form" x-show="assignMode" @submit.prevent="submitAssign()" class="flex flex-col gap-3">
                        <template x-if="occupiedSchedule(selectedSeat).length">
                            <div class="flex flex-wrap gap-1">
                                <template x-for="(window, index) in occupiedSchedule(selectedSeat)" :key="'a'+index">
                                    <span class="inline-flex items-center gap-1 rounded-md px-2 py-0.5 text-[11px] font-medium"
                                        :class="window.type === 'trial' ? 'bg-cyan-50 text-cyan-900' : (window.time_slot === 'custom_hours' ? 'bg-indigo-50 text-indigo-800' : 'bg-emerald-50 text-emerald-800')"
                                    >
                                        <span x-text="`${window.from} – ${window.to}`"></span>
                                        <span x-text="window.label"></span>
                                    </span>
                                </template>
                            </div>
                        </template>
                        <x-admin.student-search-select required />
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Trial start <span class="text-red-500">*</span></label>
                                <input type="date" x-model="assignForm.trial_start" required class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Duration (days) <span class="text-red-500">*</span></label>
                                <input type="number" min="1" max="14" x-model.number="assignForm.trial_days" required class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm">
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Time Slot</label>
                            <select x-model="assignForm.time_slot" required class="admin-select mt-1 block w-full px-3 py-2">
                                <template x-for="option in assignableSlotOptions()" :key="option.value">
                                    <option :value="option.value" :disabled="option.disabled" x-text="option.label"></option>
                                </template>
                            </select>
                            <p x-show="assignmentTimeError()" class="mt-1 text-xs text-red-600" x-text="assignmentTimeError()"></p>
                        </div>
                        <div x-show="assignForm.time_slot === 'custom_hours'" class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Start Time</label>
                                <input
                                    type="time"
                                    x-model="assignForm.custom_start_time"
                                    :min="selectedSeat?.is_open_24_hours ? '00:00' : (selectedSeat?.library_open_time || '09:00')"
                                    :max="selectedSeat?.is_open_24_hours ? '23:59' : (selectedSeat?.library_close_time || '18:00')"
                                    @change="snapAssignTimes()"
                                    @blur="snapAssignTimes()"
                                    class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm"
                                >
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">End Time</label>
                                <input
                                    type="time"
                                    x-model="assignForm.custom_end_time"
                                    :min="selectedSeat?.is_open_24_hours ? '00:00' : (selectedSeat?.library_open_time || '09:00')"
                                    :max="selectedSeat?.is_open_24_hours ? '23:59' : (selectedSeat?.library_close_time || '18:00')"
                                    @change="snapAssignTimes()"
                                    @blur="snapAssignTimes()"
                                    class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm"
                                >
                                <p class="mt-1 text-xs text-gray-500" x-show="selectedSeat && ! selectedSeat.is_open_24_hours">
                                    Allowed:
                                    <span x-text="selectedSeat?.library_open_time || '09:00'"></span>
                                    –
                                    <span x-text="selectedSeat?.library_close_time || '18:00'"></span>
                                </p>
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
                        type="button"
                        x-show="canAddAnotherStudent()"
                        @click="startAddStudent()"
                        :disabled="saving"
                        class="rounded-lg border border-indigo-200 bg-indigo-50 px-4 py-2 text-sm font-semibold text-indigo-700 hover:bg-indigo-100 disabled:opacity-60"
                    >
                        Add Student
                    </button>
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
        <x-admin.seat-schedule-modal />
    </div>
</x-admin-layout>
