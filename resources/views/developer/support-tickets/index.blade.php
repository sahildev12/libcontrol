<x-admin-layout>
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Support Tickets</h1>
            <p class="mt-1 text-sm text-gray-600">Tickets submitted from client LibControl installations.</p>
        </div>
    </div>

    <form method="GET" class="mt-6 flex flex-wrap gap-3">
        <input type="search" name="search" value="{{ $search }}" placeholder="Search library, subject, email..." class="rounded-lg border border-gray-300 px-3 py-2 text-sm">
        <select name="status" class="rounded-lg border border-gray-300 px-3 py-2 text-sm">
            <option value="">All statuses</option>
            @foreach (['open', 'in_progress', 'resolved', 'closed'] as $option)
                <option value="{{ $option }}" @selected($status === $option)>{{ ucfirst(str_replace('_', ' ', $option)) }}</option>
            @endforeach
        </select>
        <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white">Filter</button>
    </form>

    <div class="mt-6 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <table class="min-w-full text-sm">
            <thead class="border-b border-gray-200 bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500">
                <tr>
                    <th class="px-4 py-3">Library</th>
                    <th class="px-4 py-3">Subject</th>
                    <th class="px-4 py-3">Reporter</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">Created</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($tickets as $ticket)
                    <tr class="border-b border-gray-100 hover:bg-gray-50">
                        <td class="px-4 py-3">
                            <div class="font-medium text-gray-900">{{ $ticket->library_name ?: '—' }}</div>
                            <div class="text-xs text-gray-500">{{ $ticket->library_code ?: $ticket->deployment_domain }}</div>
                        </td>
                        <td class="px-4 py-3">
                            <a href="{{ route('developer.support-tickets.show', $ticket) }}" class="font-medium text-indigo-600 hover:underline">{{ $ticket->subject }}</a>
                            <div class="text-xs text-gray-500">{{ $ticket->categoryLabel() }}</div>
                        </td>
                        <td class="px-4 py-3">
                            <div>{{ $ticket->reporter_name }}</div>
                            <div class="text-xs text-gray-500">{{ $ticket->reporter_email }}</div>
                        </td>
                        <td class="px-4 py-3">{{ $ticket->statusLabel() }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ $ticket->created_at?->format('d M Y, h:i A') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-gray-500">No support tickets yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $tickets->links() }}</div>
</x-admin-layout>
