<x-admin-layout>
    <div
        x-data="seatMap({
            halls: @js($halls),
            seats: @js($seats),
            students: @js($students),
            assignedStudents: @js($assignedStudents ?? []),
            timeSlotOptions: @js($timeSlotOptions),
            storeUrl: @js(route('seat-assignments.store')),
            transferUrl: @js(route('seat-assignments.transfer')),
            availableSeatsUrl: @js(route('seat-assignments.available-seats')),
            dataUrl: @js(route('seats.data')),
            storeStudentUrl: @js(route('students.store')),
            inviteStoreUrl: @js(route('students.registration-invites.store')),
            selectedHallId: 'all',
            branches: @js($branches ?? []),
            defaultBranchId: @js($defaultBranchId ?? null),
            viewingAll: @js($viewingAll ?? false),
        })"
    >
        <header class="flex flex-wrap items-start justify-between gap-4 p-4">
            <div class="flex items-center gap-2">
                <button
                    type="button"
                    @click="openTransferModal()"
                    class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-3.5 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700"
                >
                    <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                    </svg>
                    Transfer Seat
                </button>
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
                <!-- <div class="mx-auto mb-6 max-w-4xl rounded-lg border border-dashed border-gray-400 bg-white px-4 py-2 text-center text-xs font-semibold uppercase tracking-[0.2em] text-gray-500">
                    Entrance / Front Desk
                </div> -->

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
                    <p x-show="canAddAnotherStudent()" class="mt-2 text-xs text-gray-500">This seat still has free hours. Use Add Student to assign another student to an open slot.</p>
                </div>

                    <form id="seat-assign-form" x-show="assignMode" novalidate @submit.prevent="submitAssign()" class="flex flex-col gap-3">
                        <template x-if="occupiedSchedule(selectedSeat).length">
                            <div class="flex flex-wrap gap-1">
                                <template x-for="(window, index) in occupiedSchedule(selectedSeat)" :key="index">
                                    <span class="inline-flex items-center gap-1 rounded-md px-2 py-0.5 text-[11px] font-medium"
                                        :class="window.time_slot === 'custom_hours' ? 'bg-indigo-50 text-indigo-800' : 'bg-emerald-50 text-emerald-800'"
                                    >
                                        <span x-text="`${window.from} – ${window.to}`"></span>
                                        <span x-text="window.label"></span>
                                    </span>
                                </template>
                            </div>
                        </template>
                        <x-admin.student-search-select required />
                        <!-- <p class="text-xs text-gray-500">Only students without an active seat assignment are shown.</p> -->
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
                                    Library hours:
                                    (<span x-text="selectedSeat?.library_open_time || '09:00'"></span>
                                    –
                                    <span x-text="selectedSeat?.library_close_time || '18:00'"></span>).
                                </p>
                            </div>
                        </div>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Joining Date</label>
                                <input type="date" x-model="assignForm.joining_date" required class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm">
                            </div>
                                <div>
                                <label class="block text-sm font-medium text-gray-700">Plan Expiry Date <span class="text-red-500">*</span></label>
                                <input type="date" x-model="assignForm.plan_expiry_date" required class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm">
                            </div>
                        </div>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Fee Type</label>
                                <select x-model="assignForm.fee_type" required class="admin-select mt-1 block w-full px-3 py-2">
                                    <option value="monthly">Monthly</option>
                                    <option value="yearly">Yearly</option>
                                    <option value="custom">Custom</option>
                                    <option value="membership">Membership</option>
                                    <option value="one_time">One-time</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Fee Amount</label>
                                <input type="number" min="0" step="0.01" x-model.number="assignForm.fee_amount" required class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm">
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Payment plan</label>
                            <select x-model="assignForm.payment_plan" class="admin-select mt-1 block w-full px-3 py-2">
                                <option value="full">Full payment</option>
                                <option value="installments" :disabled="assignForm.fee_type === 'one_time'">Installments</option>
                            </select>
                            <p x-show="assignForm.fee_type === 'one_time'" class="mt-1 text-xs text-gray-500">One-time fees use full payment only.</p>
                            <!-- <p x-show="assignForm.payment_plan === 'installments'" class="mt-1 text-xs text-gray-500">No need to set installment count. Add payments as you receive them.</p> -->
                        </div>

                        <div class="grid grid-cols-3 gap-2 rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-center text-xs">
                            <div>
                                <p class="text-gray-500">Total Fee</p>
                                <p class="font-semibold text-gray-900" x-text="`₹${Number(assignForm.fee_amount || 0).toFixed(2)}`"></p>
                            </div>
                            <div>
                                <p class="text-gray-500">Received</p>
                                <p class="font-semibold text-emerald-800" x-text="`₹${Number(assignPaymentAmount()).toFixed(2)}`"></p>
                            </div>
                            <div>
                                <p class="text-gray-500">Remaining</p>
                                <p class="font-semibold text-red-700" x-text="`₹${Number(assignRemainingAmount()).toFixed(2)}`"></p>
                            </div>
                        </div>

                        <div class="rounded-lg border border-indigo-100 p-3">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <p class="text-sm font-semibold text-indigo-700">Receive Payment</p>
                                    <p class="text-xs text-gray-500">Optional — leave off to create an unpaid fee.</p>
                                </div>
                                <button
                                    type="button"
                                    role="switch"
                                    :aria-checked="assignForm.receive_payment"
                                    @click="toggleAssignReceivePayment()"
                                    class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer items-center rounded-full p-0.5 transition-colors"
                                    :class="assignForm.receive_payment ? 'bg-indigo-600' : 'bg-gray-300'"
                                >
                                    <span
                                        class="pointer-events-none inline-block size-5 rounded-full bg-white shadow transition duration-200 ease-in-out"
                                        :class="assignForm.receive_payment ? 'translate-x-5' : 'translate-x-0'"
                                    ></span>
                                </button>
                            </div>

                            <div x-show="assignForm.receive_payment" x-cloak class="mt-3 space-y-3">
                                <div class="grid gap-3 sm:grid-cols-3">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Amount Received <span class="text-red-500">*</span></label>
                                        <input
                                            type="number"
                                            min="0"
                                            step="0.01"
                                            :max="Math.max(0, Number(assignForm.fee_amount || 0))"
                                            x-model.number="assignForm.amount_received"
                                            :required="assignForm.receive_payment"
                                            class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm"
                                        >
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Payment Method <span class="text-red-500">*</span></label>
                                        <select x-model="assignForm.payment_method" class="admin-select mt-1 block w-full px-3 py-2">
                                            <option value="cash">Cash</option>
                                            <option value="upi">UPI</option>
                                            <option value="card">Card</option>
                                            <option value="bank_transfer">Bank Transfer</option>
                                            <option value="other">Other</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Payment Date <span class="text-red-500">*</span></label>
                                        <input type="date" x-model="assignForm.payment_date" class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm">
                                    </div>
                                </div>
                                <div class="grid gap-3 sm:grid-cols-2">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Reference (Optional)</label>
                                        <input type="text" x-model="assignForm.payment_reference" placeholder="e.g. Receipt No." class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Notes (Optional)</label>
                                        <input type="text" x-model="assignForm.payment_notes" placeholder="Add notes..." class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm">
                                    </div>
                                </div>
                                <p x-show="assignPaymentError()" class="text-xs text-red-600" x-text="assignPaymentError()"></p>
                            </div>
                        </div>
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
                        form="seat-assign-form"
                        x-show="assignMode"
                        :disabled="saving || Boolean(assignmentTimeError())"
                        class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700 disabled:opacity-60"
                    >
                        <span x-show="! saving">Assign Student</span>
                        <span x-show="saving">Assigning...</span>
                    </button>
                    <button
                        type="button"
                        x-show="canCancel() && ! assignMode"
                        @click="cancelAssignment()"
                        :disabled="saving"
                        class="rounded-lg border border-red-200 bg-red-50 px-4 py-2 text-sm font-semibold text-red-700 hover:bg-red-100 disabled:opacity-60"
                    >
                        <span x-show="! saving">Cancel Assignment</span>
                        <span x-show="saving">Cancelling...</span>
                    </button>
                </div>
            </div>
        </div>

        <x-admin.student-create-modal />
        <x-admin.seat-schedule-modal />

        {{-- Transfer Seat modal --}}
        <div x-show="transferOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-gray-900/50" @click="closeTransferModal()"></div>
            <div class="relative flex max-h-[90vh] w-full max-w-md flex-col overflow-hidden rounded-xl bg-white shadow-xl" @click.stop>
                <div class="flex shrink-0 items-center justify-between border-b border-gray-200 px-4 py-3">
                    <div>
                        <h3 class="text-base font-semibold text-gray-900" x-text="transferStep === 'confirm' ? 'Confirm Transfer' : (transferStep === 'success' ? 'Transfer Successful' : 'Transfer Seat')"></h3>
                        <p x-show="transferStep === 'form'" class="mt-0.5 text-xs text-gray-500">Move a student to another seat without changing fees.</p>
                    </div>
                    <button
                        type="button"
                        @click="closeTransferModal()"
                        class="inline-flex size-8 items-center justify-center rounded-lg text-gray-400 hover:bg-gray-100 hover:text-gray-600"
                        aria-label="Close"
                    >
                        <svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="min-h-0 flex-1 overflow-y-auto px-4 py-4 text-sm">
                    <template x-if="transferStep === 'form'">
                        <div class="space-y-3">
                            <div class="relative" @click.outside="transferStudentOpen = false">
                                <label class="block text-sm font-medium text-gray-700">Student <span class="text-red-500">*</span></label>
                                <button
                                    type="button"
                                    @click="transferStudentOpen = ! transferStudentOpen; transferStudentQuery = ''; if (transferStudentOpen) { $nextTick(() => $refs.transferStudentSearch?.focus()) }"
                                    class="mt-1 flex w-full items-center justify-between rounded-lg border border-gray-300 bg-white px-3 py-2 text-left text-sm shadow-sm hover:border-gray-400"
                                    :class="transferForm.booking_id ? 'text-gray-900' : 'text-gray-500'"
                                >
                                    <span class="truncate" x-text="transferSelectedStudentLabel() || 'Search student with active seat'"></span>
                                    <svg class="size-4 shrink-0 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                </button>
                                <div
                                    x-show="transferStudentOpen"
                                    x-cloak
                                    class="absolute z-30 mt-1 w-full overflow-hidden rounded-lg border border-gray-200 bg-white shadow-lg"
                                >
                                    <div class="border-b border-gray-100 p-2">
                                        <input
                                            type="search"
                                            x-model="transferStudentQuery"
                                            x-ref="transferStudentSearch"
                                            @keydown.escape="transferStudentOpen = false"
                                            placeholder="Search by name, code, or phone..."
                                            class="block w-full rounded-md border border-gray-300 px-3 py-1.5 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                                        >
                                    </div>
                                    <div class="max-h-44 overflow-y-auto py-1">
                                        <template x-if="filteredAssignedStudents().length === 0">
                                            <p class="px-3 py-2 text-xs text-gray-500">No matching students with an active seat.</p>
                                        </template>
                                        <template x-for="student in filteredAssignedStudents()" :key="student.booking_id">
                                            <button
                                                type="button"
                                                class="block w-full px-3 py-2 text-left hover:bg-indigo-50"
                                                @click="selectTransferStudent(student)"
                                            >
                                                <span class="block text-sm font-medium text-gray-900" x-text="`${student.student_code} — ${student.name}`"></span>
                                                <span class="block text-xs text-gray-500" x-text="`Seat ${student.seat_number} · ${student.hall_name}`"></span>
                                            </button>
                                        </template>
                                    </div>
                                </div>
                                <p x-show="assignedStudents.length === 0" class="mt-1.5 text-xs text-amber-700">No students currently have an active seat assignment.</p>
                            </div>

                            <div class="rounded-lg border border-indigo-100 bg-indigo-50/70 px-3 py-2">
                                <p class="text-[11px] font-semibold uppercase tracking-wide text-indigo-700">Current assignment</p>
                                <p class="mt-0.5 text-sm font-medium text-gray-900" x-text="transferForm.booking_id ? transferCurrentSummary() : 'Select a student to see their current seat.'"></p>
                                <p x-show="transferForm.booking_id && transferCurrentStudent()?.joining_date_label" class="text-xs text-gray-600" x-text="`Joined ${transferCurrentStudent()?.joining_date_label}`"></p>
                            </div>

                            <div class="space-y-3 rounded-lg border border-gray-200 p-3">
                                <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-500">New assignment</p>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700">1. Hall</label>
                                    <select
                                        x-model="transferForm.hall_id"
                                        @change="onTransferHallChange()"
                                        :disabled="! transferForm.booking_id"
                                        class="admin-select mt-1 block w-full px-3 py-2 disabled:cursor-not-allowed disabled:bg-gray-100 disabled:text-gray-400"
                                    >
                                        <option value="">Select hall</option>
                                        <template x-for="hall in halls" :key="hall.id">
                                            <option :value="hall.id" x-text="hall.name"></option>
                                        </template>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700">2. Seat</label>
                                    <select
                                        x-model="transferForm.seat_id"
                                        @change="onTransferSeatChange()"
                                        :disabled="! transferForm.booking_id || ! transferForm.hall_id"
                                        class="admin-select mt-1 block w-full px-3 py-2 disabled:cursor-not-allowed disabled:bg-gray-100 disabled:text-gray-400"
                                    >
                                        <option value="" x-text="! transferForm.booking_id ? 'Select a student first' : (transferForm.hall_id ? 'Select seat' : 'Select a hall first')"></option>
                                        <template x-for="seat in transferSeatsForHall()" :key="seat.id">
                                            <option
                                                :value="seat.id"
                                                :disabled="transferSeatFullyBooked(seat)"
                                                x-text="transferSeatOptionLabel(seat)"
                                            ></option>
                                        </template>
                                    </select>
                                    <p class="mt-1 text-xs text-gray-500">Seats are listed in number order. Fully booked seats cannot be selected.</p>
                                </div>

                                <div class="rounded-md border border-sky-100 bg-sky-50 px-2.5 py-2">
                                    <p class="text-[11px] font-semibold uppercase tracking-wide text-sky-800">Booked slots on this seat</p>
                                    <template x-if="! transferForm.seat_id">
                                        <p class="mt-1 text-xs text-sky-900">Select a seat to see existing bookings.</p>
                                    </template>
                                    <template x-if="transferForm.seat_id && occupiedSchedule(selectedTransferSeat()).length === 0">
                                        <p class="mt-1 text-xs text-sky-900">No bookings — seat is fully vacant.</p>
                                    </template>
                                    <ul x-show="transferForm.seat_id && occupiedSchedule(selectedTransferSeat()).length" class="mt-1 space-y-0.5">
                                        <template x-for="(window, index) in occupiedSchedule(selectedTransferSeat())" :key="index">
                                            <li class="text-xs text-sky-900">
                                                <span class="font-medium" x-text="`${window.from} – ${window.to}`"></span>
                                                <span class="opacity-80" x-text="window.label ? ` · ${window.label}` : ''"></span>
                                            </li>
                                        </template>
                                    </ul>
                                </div>

                                <div class="space-y-2">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">3. Time Slot</label>
                                        <select
                                            x-model="transferForm.time_slot"
                                            @change="onTransferTimeChange()"
                                            :disabled="! transferForm.seat_id"
                                            class="admin-select mt-1 block w-full px-3 py-2 disabled:cursor-not-allowed disabled:bg-gray-100 disabled:text-gray-400"
                                        >
                                            <template x-for="option in transferSlotOptions()" :key="option.value">
                                                <option :value="option.value" :disabled="option.disabled" x-text="option.label"></option>
                                            </template>
                                        </select>
                                    </div>
                                    <div x-show="transferForm.seat_id && transferForm.time_slot === 'custom_hours'" class="grid gap-3 sm:grid-cols-2">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700">Start</label>
                                            <input
                                                type="time"
                                                x-model="transferForm.custom_start_time"
                                                :min="selectedTransferSeat()?.is_open_24_hours ? '00:00' : (selectedTransferSeat()?.library_open_time || '09:00')"
                                                :max="selectedTransferSeat()?.is_open_24_hours ? '23:59' : (selectedTransferSeat()?.library_close_time || '18:00')"
                                                @change="snapTransferTimes()"
                                                @blur="snapTransferTimes()"
                                                class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm"
                                            >
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700">End</label>
                                            <input
                                                type="time"
                                                x-model="transferForm.custom_end_time"
                                                :min="selectedTransferSeat()?.is_open_24_hours ? '00:00' : (selectedTransferSeat()?.library_open_time || '09:00')"
                                                :max="selectedTransferSeat()?.is_open_24_hours ? '23:59' : (selectedTransferSeat()?.library_close_time || '18:00')"
                                                @change="snapTransferTimes()"
                                                @blur="snapTransferTimes()"
                                                class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm"
                                            >
                                        </div>
                                    </div>
                                    <p x-show="transferForm.seat_id && transferTimeError()" class="text-xs text-red-600" x-text="transferTimeError()"></p>
                                    <div
                                        x-show="transferForm.seat_id && ! transferTimeError()"
                                        class="rounded-md border px-2.5 py-2 text-xs font-medium"
                                        :class="transferConflictError() ? 'border-red-200 bg-red-50 text-red-800' : 'border-emerald-200 bg-emerald-50 text-emerald-800'"
                                        x-text="transferConflictError() || 'Selected time is available on this seat.'"
                                    ></div>
                                </div>
                            </div>
                        </div>
                    </template>

                    <template x-if="transferStep === 'confirm'">
                        <div class="space-y-3">
                            <p class="text-sm font-medium text-gray-900" x-text="transferCurrentStudent()?.name"></p>
                            <div class="grid gap-2 sm:grid-cols-2">
                                <div class="rounded-lg border border-red-200 bg-red-50 px-3 py-2">
                                    <p class="text-[11px] font-semibold uppercase tracking-wide text-red-700">From</p>
                                    <p class="mt-1 text-xs text-gray-800" x-text="transferCurrentSummary()"></p>
                                </div>
                                <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2">
                                    <p class="text-[11px] font-semibold uppercase tracking-wide text-emerald-700">To</p>
                                    <p class="mt-1 text-xs text-gray-800" x-text="transferNewSummary()"></p>
                                </div>
                            </div>
                            <div class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-950">
                                Current assignment will be closed and a new one created. Fee and payment history stay unchanged.
                            </div>
                        </div>
                    </template>

                    <template x-if="transferStep === 'success'">
                        <div class="space-y-2 py-2 text-center">
                            <div class="mx-auto flex size-11 items-center justify-center rounded-full bg-emerald-100 text-emerald-600">
                                <svg class="size-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            </div>
                            <p class="text-sm font-semibold text-gray-900" x-text="transferSuccessMessage"></p>
                            <p class="text-xs text-gray-600" x-text="transferNewSummary()"></p>
                        </div>
                    </template>
                </div>

                <div class="flex shrink-0 flex-wrap justify-end gap-2 border-t border-gray-200 px-4 py-3">
                    <button type="button" @click="closeTransferModal()" class="rounded-lg border border-gray-200 px-3.5 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                        <span x-text="transferStep === 'success' ? 'Close' : 'Cancel'"></span>
                    </button>
                    <button
                        type="button"
                        x-show="transferStep === 'form'"
                        @click="goTransferConfirm()"
                        :disabled="! canPreviewTransfer() || transferSaving"
                        class="rounded-lg bg-indigo-600 px-3.5 py-2 text-sm font-semibold text-white hover:bg-indigo-700 disabled:opacity-50"
                    >
                        Preview &amp; Confirm
                    </button>
                    <button
                        type="button"
                        x-show="transferStep === 'confirm'"
                        @click="transferStep = 'form'"
                        :disabled="transferSaving"
                        class="rounded-lg border border-gray-200 px-3.5 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                    >
                        Back
                    </button>
                    <button
                        type="button"
                        x-show="transferStep === 'confirm'"
                        @click="submitTransfer()"
                        :disabled="transferSaving"
                        class="rounded-lg bg-indigo-600 px-3.5 py-2 text-sm font-semibold text-white hover:bg-indigo-700 disabled:opacity-50"
                    >
                        <span x-show="! transferSaving">Confirm Transfer</span>
                        <span x-show="transferSaving">Transferring...</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>
