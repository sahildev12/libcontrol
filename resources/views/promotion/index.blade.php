<x-admin-layout>
    <div
        x-data="studentOffersPage({
            students: @js($students),
            sendUrl: @js(route('promotion.send')),
            offersEnabled: @js($offersEnabled),
            mailConfigured: @js($mailConfigured),
            mailIssue: @js($mailIssue),
            viewingAll: @js($viewingAll),
            emailSettingsUrl: @js($emailSettingsUrl),
        })"
    >
        <header>
            <h1 class="text-2xl font-bold text-gray-900">Promotion</h1>
            <p class="mt-1 text-sm text-gray-600">Send promotional emails to students{{ $scopeLabel !== '' ? ' for '.$scopeLabel : '' }}.</p>
        </header>

        @if (! $offersEnabled || ! $mailConfigured || $viewingAll)
            <div class="mt-6 overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
                <div class="mx-auto flex max-w-xl flex-col items-center px-6 py-12 text-center sm:px-10 sm:py-14">
                    @if (! $offersEnabled)
                        <div class="relative mb-5" aria-hidden="true">
                            <span class="flex h-20 w-20 items-center justify-center rounded-2xl bg-[#0b3680]/10 text-[#0b3680]">
                                <svg class="h-10 w-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                </svg>
                            </span>
                            <span class="absolute -bottom-1 -right-1 flex h-8 w-8 items-center justify-center rounded-full bg-[#FFCC00] text-[#0b3680] ring-4 ring-white">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                                </svg>
                            </span>
                        </div>
                        <span class="inline-flex items-center rounded-full bg-amber-100 px-3 py-1 text-[11px] font-bold uppercase tracking-[0.08em] text-amber-900">Feature disabled</span>
                        <h2 class="mt-4 text-xl font-bold tracking-tight text-[#0b3680] sm:text-2xl">Promotion emails are turned off</h2>
                        <p class="mt-2 text-sm leading-relaxed text-gray-600">
                            Enable promotion emails in <span class="font-semibold text-gray-800">Settings → Emails</span> to create and send campaigns to your students.
                        </p>
                        <a href="{{ $emailSettingsUrl }}" class="lc-web-btn lc-web-btn--yellow mt-6">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            <span>Open Email Settings</span>
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.25" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/>
                            </svg>
                        </a>
                        <a href="{{ route('help-support.index') }}" class="mt-4 inline-flex items-center gap-1.5 text-sm font-semibold text-[#2563EB] hover:underline">
                            <span aria-hidden="true">?</span>
                            Need help setting up email?
                        </a>
                    @elseif (! $mailConfigured)
                        <div class="relative mb-5" aria-hidden="true">
                            <span class="flex h-20 w-20 items-center justify-center rounded-2xl bg-red-50 text-red-600">
                                <svg class="h-10 w-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                </svg>
                            </span>
                            <span class="absolute -bottom-1 -right-1 flex h-8 w-8 items-center justify-center rounded-full bg-red-500 text-white ring-4 ring-white">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                                </svg>
                            </span>
                        </div>
                        <span class="inline-flex items-center rounded-full bg-red-100 px-3 py-1 text-[11px] font-bold uppercase tracking-[0.08em] text-red-800">Email not ready</span>
                        <h2 class="mt-4 text-xl font-bold tracking-tight text-[#0b3680] sm:text-2xl">Email delivery is not configured</h2>
                        <p class="mt-2 text-sm leading-relaxed text-gray-600">
                            {{ $mailIssue ?: 'LibControl cannot send promotion emails until SMTP mail settings are configured on the server.' }}
                        </p>
                        <p class="mt-3 rounded-xl bg-gray-50 px-4 py-3 text-left text-xs leading-relaxed text-gray-600">
                            Ask your developer or hosting support to set
                            <code class="rounded bg-white px-1 font-semibold text-gray-800">MAIL_HOST</code>,
                            <code class="rounded bg-white px-1 font-semibold text-gray-800">MAIL_USERNAME</code>,
                            <code class="rounded bg-white px-1 font-semibold text-gray-800">MAIL_PASSWORD</code>, and
                            <code class="rounded bg-white px-1 font-semibold text-gray-800">MAIL_FROM_ADDRESS</code>
                            in the server <code class="rounded bg-white px-1 font-semibold text-gray-800">.env</code> file.
                        </p>
                        <a href="{{ route('help-support.index') }}" class="lc-web-btn lc-web-btn--yellow mt-6">
                            <span>Get help</span>
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.25" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/>
                            </svg>
                        </a>
                    @else
                        <div class="mb-5" aria-hidden="true">
                            <span class="flex h-20 w-20 items-center justify-center rounded-2xl bg-[#0b3680]/10 text-[#0b3680]">
                                <svg class="h-10 w-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                </svg>
                            </span>
                        </div>
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-100 px-3 py-1 text-[11px] font-bold uppercase tracking-[0.08em] text-amber-900">
                            <svg class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v3m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Branch required
                        </span>
                        <h2 class="mt-4 text-xl font-bold tracking-tight text-[#0b3680] sm:text-2xl">Select a branch to send promotions</h2>
                        <p class="mt-2 text-sm leading-relaxed text-gray-600">
                            Promotion emails are sent for one branch at a time so the right students receive them.
                            You currently have <span class="font-semibold text-gray-800">All branches</span> selected.
                        </p>
                        <div class="mt-5 w-full rounded-xl border border-[#cfe0f8] bg-[#EEF5FC] px-4 py-4 text-left">
                            <p class="text-xs font-bold uppercase tracking-[0.06em] text-[#0b3680]">How to fix this</p>
                            <ul class="mt-3 list-disc space-y-2 pl-5 text-sm text-gray-700 marker:text-[#0b3680]">
                                <li>Open the <span class="font-semibold">Branch</span> dropdown in the top bar.</li>
                                <li>Choose one library branch (not “All branches”).</li>
                                <li>Return here to compose and send your promotion email.</li>
                            </ul>
                        </div>
                    @endif
                </div>
            </div>
        @endif

        @if ($offersEnabled && $mailConfigured && ! $viewingAll)
        <div class="mt-6 grid gap-4 lg:grid-cols-5">
            <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm lg:col-span-3">
                <div class="lc-panel-header border-b border-gray-100 px-5 py-4">
                    <h2 class="text-base font-semibold text-gray-900">Compose promotion email</h2>
                    <p class="mt-1 text-xs text-gray-500">Only students with an email address receive this message.</p>
                </div>

                <form class="space-y-4 p-5" @submit.prevent="sendOffers">
                    <div>
                        <label class="text-xs font-semibold uppercase tracking-wide text-gray-500">Subject</label>
                        <input
                            type="text"
                            x-model="form.subject"
                            maxlength="200"
                            class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"
                            placeholder="e.g. 20% off this month"
                        >
                        <p class="mt-1 text-xs text-red-600" x-show="errors.subject" x-text="errors.subject"></p>
                    </div>

                    <div>
                        <label class="text-xs font-semibold uppercase tracking-wide text-gray-500">Message</label>
                        <textarea
                            x-model="form.message"
                            rows="8"
                            maxlength="5000"
                            class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"
                            placeholder="Write your promotion details here..."
                        ></textarea>
                        <p class="mt-1 text-xs text-red-600" x-show="errors.message" x-text="errors.message"></p>
                    </div>

                    <div>
                        <label class="text-xs font-semibold uppercase tracking-wide text-gray-500">Optional link</label>
                        <input
                            type="url"
                            x-model="form.action_url"
                            class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"
                            placeholder="https://your-library.com/offer"
                        >
                        <p class="mt-1 text-xs text-gray-500">Added at the end of the email when provided.</p>
                        <p class="mt-1 text-xs text-red-600" x-show="errors.action_url" x-text="errors.action_url"></p>
                    </div>

                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Recipients</p>
                        <div class="mt-2 space-y-2">
                            <label class="flex items-center gap-2 text-sm text-gray-700">
                                <input type="radio" value="all" x-model="form.audience" class="text-indigo-600 focus:ring-indigo-500">
                                <span>All students with email (<span x-text="students.length"></span>)</span>
                            </label>
                            <label class="flex items-center gap-2 text-sm text-gray-700">
                                <input type="radio" value="selected" x-model="form.audience" class="text-indigo-600 focus:ring-indigo-500">
                                <span>Selected students only (<span x-text="selectedCount()"></span>)</span>
                            </label>
                        </div>
                        <p class="mt-1 text-xs text-red-600" x-show="errors.student_ids" x-text="errors.student_ids"></p>
                    </div>

                    <p class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-800" x-show="errors.mail" x-text="errors.mail"></p>

                    <div class="flex justify-end">
                        <button
                            type="submit"
                            :disabled="sending || ! offersEnabled || ! mailConfigured || viewingAll || ! canSend()"
                            class="inline-flex h-10 items-center rounded-lg bg-indigo-600 px-5 text-sm font-semibold text-white hover:bg-indigo-700 disabled:opacity-50"
                        >
                            <span x-show="! sending">Send promotion email</span>
                            <span x-show="sending">Sending...</span>
                        </button>
                    </div>
                </form>
            </section>

            <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm lg:col-span-2" x-show="form.audience === 'selected'">
                <div class="lc-panel-header flex items-center justify-between gap-3 border-b border-gray-100 px-5 py-4">
                    <h2 class="text-base font-semibold text-gray-900">Select students</h2>
                    <button type="button" @click="toggleSelectAllVisible()" class="text-xs font-semibold hover:underline">
                        <span x-text="allVisibleSelected() ? 'Clear all' : 'Select all'"></span>
                    </button>
                </div>
                <div class="lc-table-toolbar border-b border-gray-100 px-4 py-3">
                    <input
                        type="search"
                        x-model="search"
                        placeholder="Search by name, code, or email..."
                        class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"
                    >
                </div>
                <div class="max-h-[28rem] space-y-2 overflow-y-auto p-4">
                    <template x-for="student in filteredStudents()" :key="student.id">
                        <label class="flex items-start gap-3 rounded-lg border border-gray-100 px-3 py-2 hover:bg-gray-50">
                            <input type="checkbox" :value="student.id" x-model="form.student_ids" class="mt-1 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                            <span class="min-w-0">
                                <span class="block text-sm font-medium text-gray-900" x-text="student.name"></span>
                                <span class="block text-xs text-gray-500" x-text="`${student.student_code} · ${student.email}`"></span>
                            </span>
                        </label>
                    </template>
                    <p x-show="filteredStudents().length === 0" class="py-8 text-center text-sm text-gray-500">No students with email match your search.</p>
                </div>
            </section>

            <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm lg:col-span-2" x-show="form.audience === 'all'">
                <div class="lc-panel-header border-b border-gray-100 px-5 py-4">
                    <h2 class="text-base font-semibold text-gray-900">Ready to send</h2>
                </div>
                <p class="p-5 text-sm text-gray-600">
                    This promotion will be emailed to
                    <span class="font-semibold text-gray-900" x-text="students.length"></span>
                    active student(s) who have an email address on file.
                </p>
            </section>
        </div>
        @endif
    </div>
</x-admin-layout>
