<x-admin-layout>
    <div class="flex items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ $ticket->subject }}</h1>
            <p class="mt-1 text-sm text-gray-600">{{ $ticket->library_name }} · {{ $ticket->library_code ?: $ticket->deployment_domain }}</p>
        </div>
        <a href="{{ route('developer.support-tickets.index') }}" class="text-sm font-semibold text-indigo-600 hover:underline">Back to tickets</a>
    </div>

    <div class="mt-6 grid gap-4 lg:grid-cols-3">
        <section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm lg:col-span-2">
            <h2 class="text-sm font-semibold text-gray-900">Message</h2>
            <p class="mt-4 whitespace-pre-line text-sm text-gray-700">{{ $ticket->message }}</p>
        </section>

        <section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <dl class="space-y-3 text-sm">
                <div>
                    <dt class="font-medium text-gray-500">Status</dt>
                    <dd class="mt-0.5">{{ $ticket->statusLabel() }}</dd>
                </div>
                <div>
                    <dt class="font-medium text-gray-500">Category</dt>
                    <dd class="mt-0.5">{{ $ticket->categoryLabel() }}</dd>
                </div>
                <div>
                    <dt class="font-medium text-gray-500">Reporter</dt>
                    <dd class="mt-0.5">{{ $ticket->reporter_name }}<br><span class="text-gray-500">{{ $ticket->reporter_email }}</span></dd>
                </div>
                <div>
                    <dt class="font-medium text-gray-500">Created</dt>
                    <dd class="mt-0.5">{{ $ticket->created_at?->format('d M Y, h:i A') }}</dd>
                </div>
            </dl>
        </section>
    </div>

    <form method="POST" action="{{ route('developer.support-tickets.update', $ticket) }}" class="mt-6 rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
        @csrf
        @method('PATCH')
        <h2 class="text-sm font-semibold text-gray-900">Update ticket</h2>
        <div class="mt-4 grid gap-4 md:grid-cols-2">
            <div>
                <label class="text-xs font-semibold uppercase tracking-wide text-gray-500">Status</label>
                <select name="status" class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                    @foreach (['open', 'in_progress', 'resolved', 'closed'] as $status)
                        <option value="{{ $status }}" @selected($ticket->status === $status)>{{ ucfirst(str_replace('_', ' ', $status)) }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="mt-4">
            <label class="text-xs font-semibold uppercase tracking-wide text-gray-500">Internal notes</label>
            <textarea name="admin_notes" rows="4" class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">{{ old('admin_notes', $ticket->admin_notes) }}</textarea>
        </div>
        <div class="mt-4 flex justify-end">
            <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Save</button>
        </div>
    </form>
</x-admin-layout>
