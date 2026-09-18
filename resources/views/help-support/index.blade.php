<x-admin-layout>
    <div
        x-data="helpSupportPage({
            storeUrl: @js(route('help-support.store')),
            tickets: @js($tickets->map(fn ($ticket) => [
                'id' => $ticket->id,
                'subject' => $ticket->subject,
                'status' => $ticket->status,
                'status_label' => $ticket->statusLabel(),
                'category_label' => $ticket->categoryLabel(),
                'created_at' => $ticket->created_at?->format('d M Y, h:i A'),
                'synced' => $ticket->synced_at !== null,
            ])),
        })"
    >
        <header>
            <h1 class="text-2xl font-bold text-gray-900">Help &amp; Support</h1>
            <p class="mt-1 text-sm text-gray-600">Contact Phenomit or create a support ticket. Tickets are reviewed on the main LibControl hub.</p>
        </header>

        <div class="mt-6 grid gap-4 lg:grid-cols-3">
            <section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm lg:col-span-1">
                <h2 class="text-sm font-semibold text-gray-900">Contact us</h2>
                <dl class="mt-4 space-y-3 text-sm">
                    @if ($supportEmail)
                        <div>
                            <dt class="font-medium text-gray-500">Email</dt>
                            <dd class="mt-0.5 text-gray-900"><a href="mailto:{{ $supportEmail }}" class="text-indigo-600 hover:underline">{{ $supportEmail }}</a></dd>
                        </div>
                    @endif
                    @if ($supportPhone)
                        <div>
                            <dt class="font-medium text-gray-500">Phone</dt>
                            <dd class="mt-0.5 text-gray-900">{{ $supportPhone }}</dd>
                        </div>
                    @endif
                    <div>
                        <dt class="font-medium text-gray-500">Website</dt>
                        <dd class="mt-0.5"><a href="{{ $companyUrl }}" target="_blank" rel="noopener" class="text-indigo-600 hover:underline">{{ $companyUrl }}</a></dd>
                    </div>
                </dl>
            </section>

            <section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm lg:col-span-2">
                <h2 class="text-sm font-semibold text-gray-900">Create a ticket</h2>
                <form class="mt-4 space-y-4" @submit.prevent="submitTicket">
                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="text-xs font-semibold uppercase tracking-wide text-gray-500">Category</label>
                            <select x-model="form.category" class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                                <option value="general">General</option>
                                <option value="billing">Billing</option>
                                <option value="technical">Technical</option>
                                <option value="feature">Feature request</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-xs font-semibold uppercase tracking-wide text-gray-500">Priority</label>
                            <select x-model="form.priority" class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                                <option value="normal">Normal</option>
                                <option value="high">High</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="text-xs font-semibold uppercase tracking-wide text-gray-500">Subject</label>
                        <input type="text" x-model="form.subject" maxlength="200" class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" placeholder="Brief summary of your issue">
                        <p class="mt-1 text-xs text-red-600" x-show="errors.subject" x-text="errors.subject"></p>
                    </div>
                    <div>
                        <label class="text-xs font-semibold uppercase tracking-wide text-gray-500">Message</label>
                        <textarea x-model="form.message" rows="5" class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" placeholder="Describe the issue, steps to reproduce, or what you need help with"></textarea>
                        <p class="mt-1 text-xs text-red-600" x-show="errors.message" x-text="errors.message"></p>
                    </div>
                    <div class="flex justify-end">
                        <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700" :disabled="saving">
                            <span x-show="! saving">Submit ticket</span>
                            <span x-show="saving">Submitting...</span>
                        </button>
                    </div>
                </form>
            </section>
        </div>

        <section class="mt-6 rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <h2 class="text-sm font-semibold text-gray-900">Your recent tickets</h2>
            <div class="mt-4 overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="border-b border-gray-200 text-left text-xs uppercase tracking-wide text-gray-500">
                        <tr>
                            <th class="px-3 py-2">Subject</th>
                            <th class="px-3 py-2">Category</th>
                            <th class="px-3 py-2">Status</th>
                            <th class="px-3 py-2">Created</th>
                            <th class="px-3 py-2">Synced</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-if="tickets.length === 0">
                            <tr>
                                <td colspan="5" class="px-3 py-6 text-center text-gray-500">No tickets yet.</td>
                            </tr>
                        </template>
                        <template x-for="ticket in tickets" :key="ticket.id">
                            <tr class="border-b border-gray-100">
                                <td class="px-3 py-3 font-medium text-gray-900" x-text="ticket.subject"></td>
                                <td class="px-3 py-3 text-gray-600" x-text="ticket.category_label"></td>
                                <td class="px-3 py-3 text-gray-600" x-text="ticket.status_label"></td>
                                <td class="px-3 py-3 text-gray-600" x-text="ticket.created_at"></td>
                                <td class="px-3 py-3">
                                    <span class="rounded-full px-2 py-0.5 text-xs font-semibold" :class="ticket.synced ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700'" x-text="ticket.synced ? 'Yes' : 'Pending'"></span>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</x-admin-layout>
