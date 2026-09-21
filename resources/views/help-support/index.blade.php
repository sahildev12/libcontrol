<x-admin-layout>
    <div
        class="-mx-1"
        x-data="helpSupportPage({
            storeUrl: @js(route('help-support.store')),
            tickets: @js($tickets),
            supportEmail: @js($supportEmail),
            companyUrl: @js($companyUrl),
            whatsappUrl: @js($whatsappUrl),
            faqUrl: @js($faqUrl),
            articlesUrl: @js($articlesUrl),
            documentationUrl: @js($documentationUrl),
        })"
    >
        {{-- Page header --}}
        <header class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div class="max-w-2xl">
                <h1 class="text-2xl font-bold tracking-tight text-gray-900">Help &amp; Support</h1>
                <p class="mt-1 text-sm leading-relaxed text-gray-500">
                    We're here to help. Contact our support team or create a ticket for any issue. Our team typically responds within 24 hours.
                </p>
            </div>

            <div class="w-full shrink-0 rounded-xl border border-indigo-100 bg-indigo-50/60 p-4 lg:max-w-md">
                <p class="text-sm font-semibold text-gray-900">Self-service help</p>
                <p class="mt-1 text-xs leading-relaxed text-gray-600">Browse articles and documentation on the LibControl website before opening a ticket.</p>
                <div class="mt-3 flex flex-col gap-2 sm:flex-row sm:flex-wrap">
                    <a
                        href="{{ $articlesUrl }}"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="inline-flex items-center justify-center gap-1.5 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700"
                    >
                        Support Articles
                        <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </a>
                    <a
                        href="{{ $documentationUrl }}"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="inline-flex items-center justify-center gap-1.5 rounded-lg border border-indigo-200 bg-white px-4 py-2 text-sm font-semibold text-indigo-700 shadow-sm transition hover:bg-indigo-50"
                    >
                        Support Documentation
                        <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </a>
                </div>
            </div>
        </header>

        {{-- Main two-column section --}}
        <div class="mt-6 grid gap-5 xl:grid-cols-2">
            {{-- Get in touch --}}
            <section class="relative overflow-hidden rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                <h2 class="text-base font-semibold text-gray-900">Get in touch</h2>
                <p class="mt-1 text-sm text-gray-500">Have a question or need assistance? Reach out to us through any of the channels below.</p>

                <div class="mt-5 space-y-4">
                    @if ($supportEmail)
                        <div class="flex gap-3">
                            <div class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600">
                                <svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            </div>
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-gray-900">Email Support</p>
                                <a href="mailto:{{ $supportEmail }}" class="mt-0.5 block text-sm text-indigo-600 hover:underline">{{ $supportEmail }}</a>
                                <p class="mt-0.5 text-xs text-gray-500">We usually respond within 24 hours</p>
                            </div>
                        </div>
                    @endif

                    <div class="flex gap-3">
                        <div class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-violet-50 text-violet-600">
                            <svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/></svg>
                        </div>
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-gray-900">Website</p>
                            <a href="{{ $companyUrl }}" target="_blank" rel="noopener noreferrer" class="mt-0.5 block truncate text-sm text-indigo-600 hover:underline">{{ $companyUrl }}</a>
                            <p class="mt-0.5 text-xs text-gray-500">Visit our website for more information</p>
                        </div>
                    </div>

                    @if ($whatsappUrl)
                        <div class="flex gap-3">
                            <div class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600">
                                <svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                            </div>
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-gray-900">WhatsApp Support</p>
                                <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener noreferrer" class="mt-0.5 block text-sm text-indigo-600 hover:underline">Chat with us on WhatsApp</a>
                                <p class="mt-0.5 text-xs text-gray-500">Get instant help for quick queries</p>
                            </div>
                        </div>
                    @endif

                    <div class="flex gap-3">
                        <div class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-sky-50 text-sky-600">
                            <svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-gray-900">Support Hours</p>
                            <p class="mt-0.5 text-sm text-gray-700">Monday – Saturday</p>
                            <p class="text-sm text-gray-700">9:00 AM – 6:00 PM (IST)</p>
                            <p class="mt-0.5 text-xs text-gray-500">We're closed on Sundays</p>
                        </div>
                    </div>
                </div>

                <img
                    src="{{ $supportIllustration }}"
                    alt=""
                    aria-hidden="true"
                    class="pointer-events-none absolute bottom-0 right-0 hidden h-44 w-auto max-w-[55%] object-contain object-bottom sm:block lg:h-48"
                    loading="lazy"
                >
            </section>

            {{-- Create ticket form --}}
            <section class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                <h2 class="text-base font-semibold text-gray-900">Create a support ticket</h2>
                <p class="mt-1 text-sm text-gray-500">Tell us about your issue and our team will get back to you soon.</p>

                <form class="mt-5 space-y-4" @submit.prevent="submitTicket">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="text-xs font-semibold uppercase tracking-wide text-gray-500">Category</label>
                            <select x-model="form.category" class="mt-1.5 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">Select a category</option>
                                <option value="general">General</option>
                                <option value="billing">Billing</option>
                                <option value="technical">Technical</option>
                                <option value="feature">Feature request</option>
                            </select>
                            <p class="mt-1 text-xs text-red-600" x-show="errors.category" x-text="errors.category"></p>
                        </div>
                        <div>
                            <label class="text-xs font-semibold uppercase tracking-wide text-gray-500">Priority</label>
                            <select x-model="form.priority" class="mt-1.5 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="normal">Normal</option>
                                <option value="low">Low</option>
                                <option value="high">High</option>
                                <option value="urgent">Urgent</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="text-xs font-semibold uppercase tracking-wide text-gray-500">Subject</label>
                        <input
                            type="text"
                            x-model="form.subject"
                            maxlength="200"
                            class="mt-1.5 block w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            placeholder="Brief summary of your issue"
                        >
                        <p class="mt-1 text-xs text-red-600" x-show="errors.subject" x-text="errors.subject"></p>
                    </div>

                    <div>
                        <label class="text-xs font-semibold uppercase tracking-wide text-gray-500">Message</label>
                        <textarea
                            x-model="form.message"
                            rows="5"
                            maxlength="1000"
                            class="mt-1.5 block w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            placeholder="Describe the issue, steps to reproduce, or what you need help with..."
                        ></textarea>
                        <div class="mt-1 flex items-center justify-between gap-2">
                            <p class="text-xs text-red-600" x-show="errors.message" x-text="errors.message"></p>
                            <p class="ml-auto text-xs text-gray-400" x-text="`${messageLength()}/1000`"></p>
                        </div>
                    </div>

                    <div>
                        <label class="text-xs font-semibold uppercase tracking-wide text-gray-500">Attach files <span class="font-normal normal-case text-gray-400">(optional)</span></label>
                        <div
                            class="mt-1.5 rounded-lg border border-dashed border-gray-300 bg-gray-50/50 px-4 py-5 text-center transition hover:border-indigo-300 hover:bg-indigo-50/30"
                            @dragover.prevent
                            @drop.prevent="onFilesDropped($event)"
                        >
                            <svg class="mx-auto size-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                            <p class="mt-2 text-sm text-gray-600">You can upload images, documents or screenshots</p>
                            <p class="mt-0.5 text-xs text-gray-400">Max 5 files, 5MB each</p>
                            <label class="mt-3 inline-flex cursor-pointer items-center gap-2 rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50">
                                <svg class="size-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                                Choose Files
                                <input type="file" class="hidden" multiple @change="onFilesSelected($event)" accept="image/*,.pdf,.doc,.docx,.txt,.xls,.xlsx">
                            </label>
                        </div>
                        <p class="mt-1 text-xs text-red-600" x-show="errors.attachments" x-text="errors.attachments"></p>
                        <ul class="mt-2 space-y-1.5" x-show="selectedFiles.length > 0">
                            <template x-for="(file, index) in selectedFiles" :key="`${file.name}-${index}`">
                                <li class="flex items-center justify-between gap-2 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm">
                                    <span class="truncate text-gray-700" x-text="file.name"></span>
                                    <button type="button" @click="removeFile(index)" class="shrink-0 text-xs font-semibold text-red-600 hover:underline">Remove</button>
                                </li>
                            </template>
                        </ul>
                    </div>

                    <div class="flex justify-end pt-1">
                        <button
                            type="submit"
                            class="inline-flex h-10 items-center gap-2 rounded-lg bg-indigo-600 px-5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 disabled:opacity-50"
                            :disabled="saving"
                        >
                            <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                            <span x-show="! saving">Submit Ticket</span>
                            <span x-show="saving">Submitting...</span>
                        </button>
                    </div>
                </form>
            </section>
        </div>

        {{-- Recent tickets --}}
        <section class="mt-5 rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h2 class="text-base font-semibold text-gray-900">Your recent tickets</h2>
                    <p class="mt-1 text-sm text-gray-500">Track the status of your support requests.</p>
                </div>
                <div class="flex w-full flex-col gap-2 sm:w-auto sm:flex-row sm:items-center">
                    <div class="relative">
                        <svg class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        <input
                            type="search"
                            x-model="search"
                            placeholder="Search tickets..."
                            class="w-full rounded-lg border border-gray-300 py-2 pl-9 pr-3 text-sm shadow-sm sm:w-56 focus:border-indigo-500 focus:ring-indigo-500"
                        >
                    </div>
                    <select x-model="statusFilter" class="rounded-lg border border-gray-300 py-2 pl-3 pr-8 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">All Status</option>
                        <option value="open">Open</option>
                        <option value="in_progress">In Progress</option>
                        <option value="resolved">Resolved</option>
                        <option value="closed">Closed</option>
                    </select>
                </div>
            </div>

            <div class="mt-5 overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 text-left text-[11px] font-semibold uppercase tracking-wide text-gray-500">
                            <th class="px-3 py-3">#</th>
                            <th class="px-3 py-3">Subject</th>
                            <th class="px-3 py-3">Category</th>
                            <th class="px-3 py-3">Status</th>
                            <th class="px-3 py-3">Priority</th>
                            <th class="px-3 py-3">Created</th>
                            <th class="px-3 py-3">Last Update</th>
                            <th class="px-3 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-if="filteredTickets().length === 0">
                            <tr>
                                <td colspan="8" class="px-3 py-14 text-center">
                                    <template x-if="tickets.length === 0">
                                        <p class="text-sm font-medium text-gray-900">No support tickets yet.</p>
                                        <p class="mt-1 text-sm text-gray-500">Create a ticket above and we'll help you resolve the issue.</p>
                                    </template>
                                    <template x-if="tickets.length > 0">
                                        <p class="text-sm font-medium text-gray-900">No tickets match your search.</p>
                                        <p class="mt-1 text-sm text-gray-500">Try a different keyword or status filter.</p>
                                    </template>
                                </td>
                            </tr>
                        </template>
                        <template x-for="ticket in filteredTickets()" :key="ticket.id">
                            <tr class="border-b border-gray-100 hover:bg-gray-50/50">
                                <td class="px-3 py-3 font-medium text-gray-500" x-text="`#${ticket.id}`"></td>
                                <td class="max-w-[12rem] truncate px-3 py-3 font-medium text-gray-900" x-text="ticket.subject"></td>
                                <td class="px-3 py-3 text-gray-600" x-text="ticket.category_label"></td>
                                <td class="px-3 py-3">
                                    <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold" :class="statusBadgeClass(ticket.status)" x-text="ticket.status_label"></span>
                                </td>
                                <td class="px-3 py-3">
                                    <span class="inline-flex items-center gap-1.5 text-gray-700">
                                        <span class="size-2 rounded-full" :class="priorityDotClass(ticket.priority)"></span>
                                        <span x-text="ticket.priority_label"></span>
                                    </span>
                                </td>
                                <td class="whitespace-nowrap px-3 py-3 text-gray-600" x-text="ticket.created_at"></td>
                                <td class="whitespace-nowrap px-3 py-3 text-gray-600" x-text="ticket.updated_at"></td>
                                <td class="px-3 py-3 text-right">
                                    <div class="inline-flex items-center gap-1">
                                        <button type="button" @click="openTicket(ticket)" class="inline-flex items-center gap-1 rounded-lg px-2.5 py-1.5 text-xs font-semibold text-indigo-600 hover:bg-indigo-50">
                                            <svg class="size-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                            View
                                        </button>
                                        <div class="relative" x-data="{ open: false }">
                                            <button type="button" @click="open = ! open" class="rounded-lg p-1.5 text-gray-400 hover:bg-gray-100 hover:text-gray-600">
                                                <svg class="size-4" fill="currentColor" viewBox="0 0 20 20"><path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z"/></svg>
                                            </button>
                                            <div x-show="open" @click.outside="open = false" x-cloak class="absolute right-0 z-10 mt-1 w-36 rounded-lg border border-gray-200 bg-white py-1 shadow-lg">
                                                <button type="button" @click="openTicket(ticket); open = false" class="block w-full px-3 py-2 text-left text-xs text-gray-700 hover:bg-gray-50">View details</button>
                                                @if ($supportEmail)
                                                    <a :href="`mailto:{{ $supportEmail }}?subject=Re: ${encodeURIComponent(ticket.subject)}`" class="block px-3 py-2 text-xs text-gray-700 hover:bg-gray-50">Email support</a>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </section>

        {{-- Ticket detail modal --}}
        <div
            x-show="viewingTicket"
            x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center p-4"
            @keydown.escape.window="closeTicket()"
        >
            <div class="absolute inset-0 bg-gray-900/40" @click="closeTicket()"></div>
            <div class="relative z-10 w-full max-w-lg rounded-xl border border-gray-200 bg-white p-6 shadow-xl" @click.stop>
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-400" x-text="viewingTicket ? `#${viewingTicket.id}` : ''"></p>
                        <h3 class="mt-1 text-lg font-semibold text-gray-900" x-text="viewingTicket?.subject"></h3>
                    </div>
                    <button type="button" @click="closeTicket()" class="rounded-lg p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-600">
                        <svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="mt-4 flex flex-wrap gap-2">
                    <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold" :class="viewingTicket ? statusBadgeClass(viewingTicket.status) : ''" x-text="viewingTicket?.status_label"></span>
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-700">
                        <span class="size-2 rounded-full" :class="viewingTicket ? priorityDotClass(viewingTicket.priority) : ''"></span>
                        <span x-text="viewingTicket?.priority_label"></span>
                    </span>
                    <span class="inline-flex rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-700" x-text="viewingTicket?.category_label"></span>
                </div>

                <p class="mt-4 whitespace-pre-wrap text-sm leading-relaxed text-gray-700" x-text="viewingTicket?.message"></p>

                <template x-if="viewingTicket?.attachments?.length > 0">
                    <div class="mt-4 border-t border-gray-100 pt-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Attachments</p>
                        <ul class="mt-2 space-y-1.5">
                            <template x-for="file in viewingTicket.attachments" :key="file.id">
                                <li>
                                    <a :href="file.url" target="_blank" rel="noopener" class="text-sm text-indigo-600 hover:underline" x-text="file.name"></a>
                                </li>
                            </template>
                        </ul>
                    </div>
                </template>

                <div class="mt-4 flex flex-wrap gap-4 border-t border-gray-100 pt-4 text-xs text-gray-500">
                    <span>Created: <span class="text-gray-700" x-text="viewingTicket?.created_at_full"></span></span>
                    <span>Updated: <span class="text-gray-700" x-text="viewingTicket?.updated_at_full"></span></span>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>
