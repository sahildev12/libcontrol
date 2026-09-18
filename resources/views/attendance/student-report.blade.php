<x-admin-layout>
    <div
        x-data="attendanceStudentReport({
            payload: @js($payload),
            reportDataUrl: @js($reportDataUrl),
            exportUrl: @js($exportUrl),
            studentsUrl: @js($studentsUrl),
            attendanceUrl: @js($attendanceUrl),
            dateFrom: @js($dateFrom),
            dateTo: @js($dateTo),
        })"
        x-init="init()"
        class="space-y-5"
    >
        <header class="flex flex-wrap items-start justify-between gap-4">
            <div class="min-w-0">
                <a :href="studentsUrl" class="inline-flex items-center gap-1 text-sm font-medium text-gray-500 hover:text-indigo-600">
                    <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    Back to Students
                </a>
                <h1 class="mt-2 text-2xl font-bold tracking-tight text-gray-900">Attendance Report</h1>
                <p class="mt-1 text-sm text-gray-500">View attendance history and check-in details for the selected student.</p>
            </div>

            <div class="flex w-full flex-col gap-2 sm:w-auto sm:flex-row sm:flex-wrap sm:items-center">
                <div class="relative" x-data="{ rangeOpen: false }" @click.outside="rangeOpen = false">
                    <button
                        type="button"
                        @click="rangeOpen = !rangeOpen"
                        class="inline-flex h-10 w-full items-center gap-2 rounded-lg border border-gray-200 bg-white px-3.5 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 sm:w-auto"
                    >
                        <svg class="size-4 shrink-0 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3M5 11h14M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        <span x-text="rangeLabel()"></span>
                    </button>
                    <form
                        method="get"
                        :action="reportPageUrl()"
                        x-show="rangeOpen"
                        x-cloak
                        @click.stop
                        class="absolute right-0 z-20 mt-2 w-72 rounded-xl border border-gray-200 bg-white p-4 shadow-xl"
                    >
                        <input type="hidden" name="calendar_month" :value="calendarMonth">
                        <input type="hidden" name="selected_date" :value="selectedDate">
                        <div class="space-y-3">
                            <div>
                                <label class="mb-1 block text-xs font-semibold text-gray-500">From</label>
                                <input type="date" name="date_from" x-model="dateFrom" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm">
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-semibold text-gray-500">To</label>
                                <input type="date" name="date_to" x-model="dateTo" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm">
                            </div>
                            <button type="submit" class="inline-flex h-9 w-full items-center justify-center rounded-lg bg-indigo-600 text-sm font-semibold text-white hover:bg-indigo-700">
                                Apply range
                            </button>
                        </div>
                    </form>
                </div>

                <a
                    :href="exportHref()"
                    class="inline-flex h-10 items-center justify-center gap-2 rounded-lg border border-gray-200 bg-white px-4 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50"
                >
                    <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5m0 0l5-5m-5 5V4"/></svg>
                    Export
                </a>
            </div>
        </header>

        <section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex min-w-0 items-center gap-4">
                    <img x-show="student.photo_url" :src="student.photo_url" :alt="student.name" class="size-16 shrink-0 rounded-full border border-gray-200 object-cover">
                    <div x-show="! student.photo_url" class="flex size-16 shrink-0 items-center justify-center rounded-full border border-indigo-200 bg-indigo-100 text-xl font-bold text-indigo-700" x-text="student.initials"></div>
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <h2 class="text-lg font-bold text-gray-900" x-text="student.name"></h2>
                            <span class="inline-flex rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-semibold capitalize text-emerald-700" x-text="student.status_label"></span>
                        </div>
                        <p class="text-sm text-gray-500" x-text="student.student_code"></p>
                    </div>
                </div>

                <div class="grid flex-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-400">Membership Plan</p>
                        <p class="mt-1 text-sm font-semibold text-gray-900" x-text="student.membership_plan"></p>
                        <p class="text-xs text-gray-500">Valid till <span x-text="student.valid_till_label"></span></p>
                    </div>
                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-400">Branch</p>
                        <p class="mt-1 text-sm font-semibold text-gray-900" x-text="student.branch_name || '—'"></p>
                    </div>
                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-400">Seat No.</p>
                        <p class="mt-1 text-sm font-semibold text-gray-900" x-text="student.seat_no || '—'"></p>
                    </div>
                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-400">Contact</p>
                        <p class="mt-1 text-sm font-semibold text-gray-900" x-text="student.contact || '—'"></p>
                    </div>
                </div>
            </div>
        </section>

        <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
            <template x-for="card in summaryCards()" :key="card.key">
                <div class="rounded-xl border p-4 shadow-sm" :class="card.borderClass">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-[11px] font-semibold uppercase tracking-wide" :class="card.labelClass" x-text="card.label"></p>
                            <p class="mt-2 text-3xl font-bold tabular-nums" :class="card.valueClass" x-text="card.count"></p>
                            <!-- <p class="mt-1 text-sm font-semibold tabular-nums" :class="card.percentClass" x-text="`${card.percent}%`"></p> -->
                        </div>
                        <span class="inline-flex size-10 items-center justify-center rounded-xl" :class="card.iconWrapClass">
                            <span x-html="card.icon"></span>
                        </span>
                    </div>
                </div>
            </template>
        </section>

        <div x-show="! hasData" class="rounded-xl border border-dashed border-gray-200 bg-white px-4 py-10 text-center text-sm text-gray-500 shadow-sm">
            No attendance records found for this period.
        </div>

        <div x-show="hasData" class="grid gap-4 xl:grid-cols-5">
            <section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm xl:col-span-3">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <h2 class="text-sm font-semibold text-gray-900">Attendance Calendar</h2>
                        <p class="text-xs text-gray-500" x-text="calendarMonthLabel"></p>
                    </div>
                    <div class="flex items-center gap-1">
                        <button type="button" @click="changeCalendarMonth(-1)" class="inline-flex size-8 items-center justify-center rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50" :disabled="loading">
                            <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                        </button>
                        <button type="button" @click="changeCalendarMonth(1)" class="inline-flex size-8 items-center justify-center rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50" :disabled="loading">
                            <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </button>
                    </div>
                </div>

                <div class="mt-4 grid grid-cols-7 gap-1 text-center text-[11px] font-semibold uppercase tracking-wide text-gray-400">
                    <template x-for="label in calendar.weekday_labels" :key="label">
                        <div class="py-1" x-text="label"></div>
                    </template>
                </div>

                <div class="mt-1 space-y-1">
                    <template x-for="(week, weekIndex) in calendar.weeks" :key="weekIndex">
                        <div class="grid grid-cols-7 gap-1">
                            <template x-for="day in week" :key="day.date">
                                <button
                                    type="button"
                                    @click="selectDate(day)"
                                    :disabled="! day.in_month || ! day.in_range"
                                    class="flex min-h-[3.25rem] flex-col items-center justify-center rounded-lg border text-sm transition"
                                    :class="calendarDayClass(day)"
                                >
                                    <span x-text="day.day"></span>
                                    <span x-show="day.in_month && day.in_range && day.status" class="mt-1 size-1.5 rounded-full" :class="statusDotClass(day.status)"></span>
                                </button>
                            </template>
                        </div>
                    </template>
                </div>

                <div class="mt-4 flex flex-wrap gap-4 border-t border-gray-100 pt-4 text-xs text-gray-600">
                    <span class="inline-flex items-center gap-1.5"><span class="size-2 rounded-full bg-emerald-500"></span> Present</span>
                    <span class="inline-flex items-center gap-1.5"><span class="size-2 rounded-full bg-orange-500"></span> Absent</span>
                    <span class="inline-flex items-center gap-1.5"><span class="size-2 rounded-full bg-violet-500"></span> On Trial / Half Day</span>
                    <span class="inline-flex items-center gap-1.5"><span class="size-2 rounded-full bg-red-500"></span> Late</span>
                </div>
            </section>

            <div class="space-y-4 xl:col-span-2">
                <section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h2 class="text-sm font-semibold text-gray-900">Selected Day Details</h2>
                            <p class="mt-1 text-sm text-gray-600" x-text="selectedDay.date_heading || '—'"></p>
                        </div>
                        <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold" :class="statusBadgeClass(selectedDay.status)" x-text="selectedDay.status_label"></span>
                    </div>

                    <div x-show="selectedDay.status === 'absent'" class="mt-5 rounded-lg border border-orange-100 bg-orange-50 px-4 py-3 text-sm text-orange-800">
                        <p x-text="selectedDay.message || 'No attendance recorded for this date.'"></p>
                    </div>

                    <div x-show="selectedDay.status !== 'absent'" class="mt-5 space-y-5">
                        <div class="relative pl-6">
                            <div class="absolute bottom-2 left-[0.45rem] top-2 w-px bg-gray-200"></div>
                            <div class="relative">
                                <span class="absolute -left-6 top-1 size-2.5 rounded-full bg-indigo-500 ring-4 ring-white"></span>
                                <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Check-in</p>
                                <p class="mt-1 text-sm font-semibold text-gray-900" x-text="selectedDay.check_in_at || '—'"></p>
                                <p class="mt-1 inline-flex items-center gap-1 text-xs text-sky-700">
                                    <svg class="size-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a2 2 0 01-2.828 0l-4.243-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                    <span x-text="selectedDay.location_name || '—'"></span>
                                </p>
                            </div>
                            <div class="relative mt-6">
                                <span class="absolute -left-6 top-1 size-2.5 rounded-full bg-gray-300 ring-4 ring-white"></span>
                                <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Check-out</p>
                                <p class="mt-1 text-sm font-semibold text-gray-900" x-text="selectedDay.check_out_at || 'Not recorded'"></p>
                                <p class="mt-1 text-xs text-gray-500" x-show="! selectedDay.check_out_at">Check-out is not tracked in the current attendance system.</p>
                            </div>
                        </div>

                        <div class="rounded-lg border border-sky-100 bg-sky-50 px-4 py-3">
                            <p class="text-xs font-semibold uppercase tracking-wide text-sky-700">Total Study Time</p>
                            <p class="mt-1 text-sm font-semibold text-sky-900" x-text="selectedDay.study_time_label || 'Not available'"></p>
                            <p class="mt-1 text-xs text-sky-800/80" x-show="selectedDay.study_time_note" x-text="selectedDay.study_time_note"></p>
                        </div>

                        <div class="rounded-lg border border-gray-100 bg-gray-50 px-4 py-3">
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Attendance Method</p>
                            <div class="mt-2 flex items-center gap-2 text-sm font-semibold text-gray-900">
                                <span class="inline-flex size-8 items-center justify-center rounded-lg bg-white text-indigo-600 shadow-sm" x-html="methodIcon(selectedDay.method)"></span>
                                <span x-text="selectedDay.method_label || '—'"></span>
                            </div>
                            <p class="mt-2 text-xs text-gray-500" x-show="selectedDay.marked_by_name">
                                Marked by <span x-text="selectedDay.marked_by_name"></span>
                            </p>
                            <button
                                type="button"
                                x-show="selectedDay.has_location"
                                @click="locationOpen = true"
                                class="mt-3 inline-flex items-center gap-1 text-xs font-semibold text-sky-700 hover:underline"
                            >
                                View Location
                            </button>
                        </div>
                    </div>
                </section>
            </div>
        </div>

        <div
            x-show="locationOpen"
            x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/40 p-4"
            @click.self="locationOpen = false"
        >
            <div class="w-full max-w-md rounded-xl border border-gray-200 bg-white p-5 shadow-xl">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h3 class="text-sm font-semibold text-gray-900">Attendance Location</h3>
                        <p class="mt-1 text-xs text-gray-500" x-text="selectedDay.location_name"></p>
                    </div>
                    <button type="button" @click="locationOpen = false" class="rounded-lg p-1 text-gray-400 hover:bg-gray-100">
                        <svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <p class="mt-4 text-sm text-gray-700" x-show="selectedDay.latitude && selectedDay.longitude">
                    GPS: <span x-text="`${selectedDay.latitude}, ${selectedDay.longitude}`"></span>
                </p>
                <a
                    x-show="selectedDay.maps_url"
                    :href="selectedDay.maps_url"
                    target="_blank"
                    rel="noopener"
                    class="mt-4 inline-flex h-10 w-full items-center justify-center rounded-lg bg-indigo-600 text-sm font-semibold text-white hover:bg-indigo-700"
                >
                    Open in Maps
                </a>
            </div>
        </div>

        <x-admin.student-view-modal />
    </div>
</x-admin-layout>
