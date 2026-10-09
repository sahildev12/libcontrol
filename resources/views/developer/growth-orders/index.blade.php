<x-admin-layout>
    <div class="space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Growth Orders</h1>
                <p class="mt-1 text-sm text-gray-600">Library Growth package and service requests from clients.</p>
            </div>
        </div>

        @if (session('status'))
            <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('status') }}</div>
        @endif

        <form method="GET" class="flex flex-wrap gap-2">
            <select name="status" class="rounded-lg border-gray-300 text-sm">
                <option value="">All statuses</option>
                @foreach ($statuses as $s)
                    <option value="{{ $s }}" @selected($status === $s)>{{ ucfirst($s) }}</option>
                @endforeach
            </select>
            <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white">Filter</button>
        </form>

        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold text-gray-700">Item</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-700">Library</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-700">Contact</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-700">Status</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-700">Payment</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-700">Created</th>
                        <th class="px-4 py-3 text-right font-semibold text-gray-700"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($orders as $order)
                        <tr>
                            <td class="px-4 py-3">
                                <p class="font-medium text-gray-900">{{ $order->item_name }}</p>
                                <p class="text-xs text-gray-500">{{ $order->order_type }} · {{ $order->item_key }}</p>
                            </td>
                            <td class="px-4 py-3 text-gray-600">{{ $order->library_name ?: '—' }}</td>
                            <td class="px-4 py-3 text-gray-600">
                                <p>{{ $order->contact_name }}</p>
                                <p class="text-xs">{{ $order->contact_email }}</p>
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex rounded-full bg-gray-100 px-2 py-0.5 text-xs font-semibold text-gray-800">{{ $order->statusLabel() }}</span>
                            </td>
                            <td class="px-4 py-3 text-gray-600">{{ $order->payment_status ?: '—' }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ $order->created_at?->format('d M Y') }}</td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('developer.growth-orders.show', $order) }}" class="font-medium text-indigo-600 hover:text-indigo-800">Open</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-10 text-center text-gray-500">No growth orders yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $orders->links() }}
    </div>
</x-admin-layout>
