<x-admin-layout>
    <div
        x-data="studentOffersPage({
            students: @js($students),
            sendUrl: @js(route('offers.send')),
            offersEnabled: @js($offersEnabled),
            mailConfigured: @js($mailConfigured),
            mailIssue: @js($mailIssue),
            viewingAll: @js($viewingAll),
            emailSettingsUrl: @js($emailSettingsUrl),
        })"
    >
        <header>
            <h1 class="text-2xl font-bold text-gray-900">Offers</h1>
            <p class="mt-1 text-sm text-gray-600">Send promotional offer emails to students{{ $scopeLabel !== '' ? ' for '.$scopeLabel : '' }}.</p>
        </header>

        <div x-show="! offersEnabled" class="mt-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-950">
            Offer emails are turned off.
            <a :href="emailSettingsUrl" class="font-semibold text-amber-900 underline">Enable them in Settings → Emails</a>
            before sending.
        </div>

        <div x-show="! mailConfigured" class="mt-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900">
            <p class="font-semibold">Email delivery is not configured</p>
            <p class="mt-1" x-text="mailIssue"></p>
            <p class="mt-2 text-xs text-red-800">Add SMTP settings in your server <code class="rounded bg-white px-1">.env</code> file: <code class="rounded bg-white px-1">MAIL_MAILER</code>, <code class="rounded bg-white px-1">MAIL_HOST</code>, <code class="rounded bg-white px-1">MAIL_USERNAME</code>, <code class="rounded bg-white px-1">MAIL_PASSWORD</code>, and <code class="rounded bg-white px-1">MAIL_FROM_ADDRESS</code>.</p>
        </div>

        <div x-show="viewingAll" class="mt-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-950">
            Select a specific branch from the top bar to send offer emails.
        </div>

        <div class="mt-6 grid gap-4 lg:grid-cols-5">
            <section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm lg:col-span-3">
                <h2 class="text-sm font-semibold text-gray-900">Compose offer email</h2>
                <p class="mt-1 text-xs text-gray-500">Only students with an email address receive this message.</p>

                <form class="mt-4 space-y-4" @submit.prevent="sendOffers">
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
                            placeholder="Write your offer details here..."
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
                            <span x-show="! sending">Send offer email</span>
                            <span x-show="sending">Sending...</span>
                        </button>
                    </div>
                </form>
            </section>

            <section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm lg:col-span-2" x-show="form.audience === 'selected'">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="text-sm font-semibold text-gray-900">Select students</h2>
                    <button type="button" @click="toggleSelectAllVisible()" class="text-xs font-semibold text-indigo-600 hover:underline">
                        <span x-text="allVisibleSelected() ? 'Clear all' : 'Select all'"></span>
                    </button>
                </div>
                <input
                    type="search"
                    x-model="search"
                    placeholder="Search by name, code, or email..."
                    class="mt-3 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"
                >
                <div class="mt-3 max-h-[28rem] space-y-2 overflow-y-auto">
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

            <section class="rounded-xl border border-dashed border-gray-200 bg-gray-50 p-5 shadow-sm lg:col-span-2" x-show="form.audience === 'all'">
                <h2 class="text-sm font-semibold text-gray-900">Ready to send</h2>
                <p class="mt-2 text-sm text-gray-600">
                    This offer will be emailed to
                    <span class="font-semibold text-gray-900" x-text="students.length"></span>
                    active student(s) who have an email address on file.
                </p>
            </section>
        </div>
    </div>
</x-admin-layout>
