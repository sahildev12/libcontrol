<x-admin-layout>
    <div
        class="relative"
        x-data="attendancePanel({
            rows: @js($rows),
            summary: @js($summary),
            halls: @js($halls),
            date: @js($date),
            markPresentUrl: @js(route('attendance.mark-present')),
            studentProfileUrlTemplate: @js(route('attendance.student-profile', ['student' => '__ID__'])),
            indexUrl: @js(route('attendance.index')),
            reportsUrl: @js(route('attendance.reports')),
            settingsUrl: @js(route('attendance.settings')),
        })"
        x-init="init()"
    >
        <header class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-gray-900">Attendance</h1>
                <p class="mt-1 text-sm text-gray-500">Today's register{{ $scopeLabel !== '' ? ' for '.$scopeLabel : '' }}.</p>
                <div class="mt-3 flex flex-wrap items-center gap-4 text-xs text-gray-500">
                    <span class="inline-flex items-center gap-1.5">
                        <svg class="size-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        {{ $dateLabel }}
                    </span>
                    <span>Last synced: {{ $lastSyncedAt }}</span>
                    <span>Library hours: {{ $libraryHours }}</span>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <input
                    type="date"
                    x-model="date"
                    @change="changeDate()"
                    class="h-10 rounded-lg border-gray-200 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                >
                <a href="{{ route('attendance.settings') }}" class="inline-flex h-10 items-center gap-2 rounded-lg bg-indigo-600 px-4 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700">
                    <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    Setup
                </a>
            </div>
        </header>

        <section class="mt-5 grid gap-3 lg:grid-cols-[1fr_auto]">
            <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 shadow-sm">
                    <div class="flex items-center justify-between">
                        <p class="text-[11px] font-semibold uppercase tracking-wide text-emerald-700">Present</p>
                        <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-bold text-emerald-700" x-text="`${percentOf(summary.present)}%`"></span>
                    </div>
                    <p class="mt-2 text-3xl font-bold tabular-nums text-emerald-900" x-text="summary.present"></p>
                    <p class="mt-1 text-xs text-emerald-700" x-text="`${percentOf(summary.present)}% of total`"></p>
                </div>
                <div class="rounded-xl border border-rose-200 bg-rose-50 p-4 shadow-sm">
                    <div class="flex items-center justify-between">
                        <p class="text-[11px] font-semibold uppercase tracking-wide text-rose-700">Absent</p>
                        <span class="rounded-full bg-rose-100 px-2 py-0.5 text-[10px] font-bold text-rose-700" x-text="`${percentOf(summary.absent)}%`"></span>
                    </div>
                    <p class="mt-2 text-3xl font-bold tabular-nums text-rose-900" x-text="summary.absent"></p>
                    <p class="mt-1 text-xs text-rose-700" x-text="`${percentOf(summary.absent)}% of total`"></p>
                </div>
                <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 shadow-sm">
                    <div class="flex items-center justify-between">
                        <p class="text-[11px] font-semibold uppercase tracking-wide text-amber-700">Late</p>
                        <span class="rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-bold text-amber-700" x-text="`${percentOf(summary.late)}%`"></span>
                    </div>
                    <p class="mt-2 text-3xl font-bold tabular-nums text-amber-900" x-text="summary.late ?? 0"></p>
                    <p class="mt-1 text-xs text-amber-700" x-text="`${percentOf(summary.late)}% of total`"></p>
                </div>
                <div class="rounded-xl border border-sky-200 bg-sky-50 p-4 shadow-sm">
                    <div class="flex items-center justify-between">
                        <p class="text-[11px] font-semibold uppercase tracking-wide text-sky-700">Not Marked</p>
                        <span class="rounded-full bg-sky-100 px-2 py-0.5 text-[10px] font-bold text-sky-700" x-text="`${percentOf(summary.not_marked)}%`"></span>
                    </div>
                    <p class="mt-2 text-3xl font-bold tabular-nums text-sky-900" x-text="summary.not_marked ?? 0"></p>
                    <p class="mt-1 text-xs text-sky-700" x-text="`${percentOf(summary.not_marked)}% of total`"></p>
                </div>
            </div>

            <!-- <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm lg:min-w-[220px]">
                <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-400">Today's Marking</p>
                <p class="text-xs text-gray-500">By Method</p>
                <div class="mt-3 space-y-2 text-sm">
                    <div class="flex items-center justify-between"><span class="text-gray-600">Student QR</span><span class="font-semibold text-gray-900" x-text="summary.qr_check_ins"></span></div>
                    <div class="flex items-center justify-between"><span class="text-gray-600">Staff GPS</span><span class="font-semibold text-gray-900" x-text="summary.staff_check_ins"></span></div>
                    <div class="flex items-center justify-between"><span class="text-gray-600">Biometric</span><span class="font-semibold text-gray-900" x-text="summary.biometric_check_ins ?? 0"></span></div>
                    <div class="flex items-center justify-between"><span class="text-gray-600">Manual</span><span class="font-semibold text-gray-900" x-text="summary.manual_check_ins ?? 0"></span></div>
                </div>
            </div> -->
        </section>

        <div class="mt-6 flex flex-col gap-4 xl:flex-row xl:items-start">
            <section class="min-w-0 flex-1 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="flex flex-wrap items-center gap-3 border-b border-gray-200 px-4 py-3">
                    <div class="relative min-w-[220px] flex-1">
                        <svg class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        <input type="search" x-model="search" placeholder="Search students by name or ID..." class="w-full rounded-lg border-gray-200 py-2 pl-9 pr-3 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <select x-model="statusFilter" class="rounded-lg border-gray-200 py-2 pl-3 pr-8 text-sm">
                        <option value="">All Statuses</option>
                        <option value="present">Present</option>
                        <option value="absent">Absent</option>
                        <option value="late">Late</option>
                    </select>
                    <select x-model="hallFilter" class="rounded-lg border-gray-200 py-2 pl-3 pr-8 text-sm">
                        <option value="">All Halls</option>
                        <template x-for="hall in halls" :key="hall.id">
                            <option :value="hall.id" x-text="hall.name"></option>
                        </template>
                    </select>
                    <select x-model="methodFilter" class="rounded-lg border-gray-200 py-2 pl-3 pr-8 text-sm">
                        <option value="">All Methods</option>
                        <option value="student_qr">Student QR</option>
                        <option value="staff_gps">Staff GPS</option>
                        <option value="manual">Manual</option>
                        <option value="biometric">Biometric</option>
                    </select>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="w-10 px-4 py-3"><input type="checkbox" @change="toggleSelectAll($event)" :checked="allPageSelected()" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"></th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Student</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Seat</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Status</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Check-in</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Method</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <template x-for="row in paginatedRows" :key="row.student_id">
                                <tr
                                    class="cursor-pointer transition-colors"
                                    :class="selectedStudentId === row.student_id ? 'bg-indigo-50/70' : 'hover:bg-indigo-50/40'"
                                    @click="selectStudent(row)"
                                >
                                    <td class="px-4 py-3" @click.stop>
                                        <input type="checkbox" :value="row.student_id" x-model="selectedIds" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-3">
                                            <img x-show="row.photo_url" :src="row.photo_url" :alt="row.student_name" class="size-9 shrink-0 rounded-full border border-gray-200 object-cover">
                                            <div x-show="! row.photo_url" class="flex size-9 shrink-0 items-center justify-center rounded-full border border-indigo-200 bg-indigo-100 text-xs font-bold text-indigo-700" x-text="row.initials || '?'"></div>
                                            <div>
                                                <p class="font-medium text-gray-900" x-text="row.student_name"></p>
                                                <p class="text-xs text-gray-500" x-text="row.student_code"></p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-gray-600" x-text="row.seat_label || '—'"></td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold"
                                              :class="statusClass(row.status)"
                                              x-text="row.status_label"></span>
                                    </td>
                                    <td class="px-4 py-3 text-gray-600" x-text="row.check_in_at || '—'"></td>
                                    <td class="px-4 py-3 text-gray-600" x-text="row.method_label"></td>
                                    <td class="px-4 py-3 text-right" @click.stop>
                                        <div class="inline-flex items-center justify-end gap-1.5">
                                            <button
                                                type="button"
                                                x-show="row.status === 'absent'"
                                                @click="markPresent(row)"
                                                :disabled="busy"
                                                class="inline-flex h-8 items-center rounded-lg bg-indigo-600 px-3 text-xs font-semibold text-white hover:bg-indigo-700 disabled:opacity-50"
                                            >Mark Present</button>
                                            <button type="button" @click="openReports()" class="inline-flex size-8 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-600 hover:bg-gray-50" title="History">
                                                <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                            <tr x-show="filteredRows.length === 0">
                                <td colspan="7" class="px-4 py-10 text-center text-sm text-gray-500">No students found.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="flex flex-wrap items-center justify-between gap-3 border-t border-gray-200 px-4 py-3 text-sm">
                    <p class="text-xs text-gray-500"><span x-text="selectedIds.length"></span> of <span x-text="filteredRows.length"></span> selected</p>
                    <div class="flex items-center gap-3">
                        <label class="flex items-center gap-2 text-xs text-gray-500">
                            Rows per page
                            <select x-model.number="perPage" class="rounded-lg border-gray-200 py-1 pl-2 pr-7 text-sm">
                                <option value="10">10</option>
                                <option value="25">25</option>
                                <option value="50">50</option>
                            </select>
                        </label>
                        <button type="button" @click="prevPage()" :disabled="page <= 1" class="rounded-lg border border-gray-200 px-3 py-1.5 disabled:opacity-40">Previous</button>
                        <span class="text-gray-500">Page <span x-text="page"></span> of <span x-text="totalPages"></span></span>
                        <button type="button" @click="nextPage()" :disabled="page >= totalPages" class="rounded-lg border border-gray-200 px-3 py-1.5 disabled:opacity-40">Next</button>
                    </div>
                </div>
            </section>

            <aside
                x-show="profile"
                x-cloak
                class="w-full shrink-0 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm xl:w-[320px]"
            >
                <div class="flex items-start justify-between border-b border-gray-100 px-4 py-4">
                    <div>
                        <h2 class="text-lg font-bold text-gray-900" x-text="profile?.student_name"></h2>
                        <p class="text-xs text-gray-500" x-text="profile?.student_code"></p>
                    </div>
                    <button type="button" @click="closeProfile()" class="rounded-lg p-1 text-gray-400 hover:bg-gray-100">
                        <svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="px-4 py-4">
                    <div class="flex flex-col items-center text-center">
                        <img x-show="profile?.photo_url" :src="profile?.photo_url" :alt="profile?.student_name" class="size-20 rounded-full border border-gray-200 object-cover">
                        <div x-show="! profile?.photo_url" class="flex size-20 items-center justify-center rounded-full border border-indigo-200 bg-indigo-100 text-2xl font-bold text-indigo-700" x-text="profile?.initials"></div>
                        <span class="mt-3 inline-flex rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700" x-text="profile?.student_type_label"></span>
                    </div>

                    <dl class="mt-5 space-y-3 text-sm">
                        <div class="flex items-start gap-3"><dt class="w-20 shrink-0 text-gray-400">Seat</dt><dd class="text-gray-800" x-text="profile?.seat_label || '—'"></dd></div>
                        <div class="flex items-start gap-3"><dt class="w-20 shrink-0 text-gray-400">Phone</dt><dd class="text-gray-800" x-text="profile?.phone || '—'"></dd></div>
                        <div class="flex items-start gap-3"><dt class="w-20 shrink-0 text-gray-400">Email</dt><dd class="break-all text-gray-800" x-text="profile?.email || '—'"></dd></div>
                        <div class="flex items-start gap-3"><dt class="w-20 shrink-0 text-gray-400">Joining</dt><dd class="text-gray-800" x-text="profile?.joining_date_label || '—'"></dd></div>
                    </dl>

                    <div class="mt-5 rounded-xl border border-gray-100 bg-gray-50 p-4">
                        <div class="flex items-center justify-between">
                            <p class="text-sm font-semibold text-gray-900">Attendance Rate</p>
                            <p class="text-lg font-bold text-emerald-600"><span x-text="profile?.attendance_rate ?? 0"></span>%</p>
                        </div>
                        <div class="mt-2 h-2 overflow-hidden rounded-full bg-gray-200">
                            <div class="h-full rounded-full bg-emerald-500 transition-all" :style="`width: ${profile?.attendance_rate ?? 0}%`"></div>
                        </div>
                        <div class="mt-3 grid grid-cols-3 gap-2 text-center text-xs">
                            <div><p class="font-bold text-gray-900" x-text="profile?.stats?.present ?? 0"></p><p class="text-gray-500">Present</p></div>
                            <div><p class="font-bold text-gray-900" x-text="profile?.stats?.absent ?? 0"></p><p class="text-gray-500">Absent</p></div>
                            <div><p class="font-bold text-gray-900" x-text="profile?.stats?.late ?? 0"></p><p class="text-gray-500">Late</p></div>
                        </div>
                    </div>

                    <button type="button" @click="openReports()" class="mt-4 inline-flex h-10 w-full items-center justify-center rounded-lg bg-indigo-600 text-sm font-semibold text-white hover:bg-indigo-700">
                        View Full History
                    </button>

                    <div class="mt-5">
                        <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-400">Recent Attendance</p>
                        <div class="mt-2 space-y-2">
                            <template x-for="item in (profile?.recent || [])" :key="item.date_label">
                                <div class="flex items-center justify-between rounded-lg border border-gray-100 px-3 py-2 text-xs">
                                    <div>
                                        <p class="font-medium text-gray-800" x-text="item.date_label"></p>
                                        <p class="text-gray-500" x-text="item.check_in_at || '—'"></p>
                                    </div>
                                    <span class="rounded-full px-2 py-0.5 font-semibold"
                                          :class="statusClass(item.status)"
                                          x-text="item.status_label"></span>
                                </div>
                            </template>
                            <p x-show="(profile?.recent || []).length === 0" class="text-xs text-gray-500">No recent records.</p>
                        </div>
                    </div>

                    <button
                        type="button"
                        @click="openViewStudent()"
                        class="mt-4 inline-flex h-10 w-full items-center justify-center gap-2 rounded-lg border border-gray-200 text-sm font-semibold text-gray-700 hover:bg-gray-50"
                    >
                        <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        View Student Profile
                    </button>
                </div>
            </aside>
        </div>

        <x-admin.student-view-modal />
    </div>
</x-admin-layout>
