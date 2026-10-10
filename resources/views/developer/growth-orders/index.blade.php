@php
    $statusStyles = [
        'new' => 'bg-sky-50 text-sky-700 ring-sky-200',
        'quoted' => 'bg-amber-50 text-amber-700 ring-amber-200',
        'active' => 'bg-green-50 text-green-700 ring-green-200',
        'paused' => 'bg-slate-100 text-slate-600 ring-slate-200',
        'completed' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
        'cancelled' => 'bg-red-50 text-red-700 ring-red-200',
    ];
    $paymentStyles = [
        'paid' => 'text-emerald-700',
        'pending' => 'text-amber-700',
        'failed' => 'text-red-700',
    ];
@endphp

<x-admin-layout>
    <header>
        <h1 class="lc-page-title">Service Requests</h1>
        <p class="mt-1 text-sm text-gray-600">Growth packages and services requested by client libraries. Update the status here and the library sees it in their Growth page.</p>
    </header>

    @if (session('status'))
        <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('status') }}</div>
    @endif

    <div class="lc-dash-stats">
        @foreach ([
            ['Total requests', $stats['total'], 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2'],
            ['New', $stats['new'], 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z'],
            ['In progress', $stats['in_progress'], 'M13 10V3L4 14h7v7l9-11h-7z'],
            ['Completed', $stats['completed'], 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'],
        ] as [$label, $value, $icon])
            <article class="lc-dash-stat">
                <div class="lc-dash-stat__head">
                    <p class="lc-dash-stat__label">{{ $label }}</p>
                    <span class="lc-dash-stat__icon" aria-hidden="true">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="{{ $icon }}"/></svg>
                    </span>
                </div>
                <p class="lc-dash-stat__value">{{ number_format($value) }}</p>
            </article>
        @endforeach
    </div>

    <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <form method="GET" class="lc-table-toolbar flex flex-wrap items-center justify-between gap-3 border-b border-gray-200 px-4 py-3">
            <div class="flex flex-wrap items-center gap-1.5">
                <a href="{{ route('developer.growth-orders.index', array_filter(['q' => $search])) }}" class="lc-toolbar-filter rounded-full border px-3 py-1 text-xs font-semibold {{ $status === '' ? 'is-active' : '' }}">
                    All <span class="opacity-70">{{ number_format($stats['total']) }}</span>
                </a>
                @foreach ($statuses as $s)
                    <a href="{{ route('developer.growth-orders.index', array_filter(['status' => $s, 'q' => $search])) }}" class="lc-toolbar-filter rounded-full border px-3 py-1 text-xs font-semibold {{ $status === $s ? 'is-active' : '' }}">
                        {{ ucfirst($s) }} <span class="opacity-70">{{ number_format($counts[$s] ?? 0) }}</span>
                    </a>
                @endforeach
            </div>
            <div class="flex items-center gap-2">
                @if ($status !== '')
                    <input type="hidden" name="status" value="{{ $status }}">
                @endif
                <input type="search" name="q" value="{{ $search }}" placeholder="Search library, service, contact..." class="w-full min-w-[220px] rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/30 sm:w-72">
                <button type="submit" class="rounded-lg border px-3 py-2 text-sm font-semibold">Search</button>
            </div>
        </form>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[960px]">
                <thead class="border-b border-gray-200 bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                    <tr>
                        <th class="px-4 py-3">#</th>
                        <th class="px-4 py-3">Library</th>
                        <th class="px-4 py-3">Requested</th>
                        <th class="px-4 py-3">Contact</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Payment</th>
                        <th class="px-4 py-3">Received</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-sm text-gray-700">
                    @forelse ($orders as $order)
                        <tr class="transition-colors hover:bg-indigo-50/30 {{ $order->status === 'new' ? 'bg-sky-50/40' : '' }}">
                            <td class="px-4 py-3 font-medium text-gray-500">#{{ $order->id }}</td>
                            <td class="px-4 py-3">
                                <p class="font-medium text-gray-900">{{ $order->library_name ?: '—' }}</p>
                                <p class="mt-0.5 text-xs text-gray-500">{{ $order->deployment_domain ?: ($order->library_code ?: '—') }}</p>
                            </td>
                            <td class="px-4 py-3">
                                <a href="{{ route('developer.growth-orders.show', $order) }}" class="font-medium text-indigo-600 hover:underline">{{ $order->item_name }}</a>
                                <p class="mt-0.5 text-xs text-gray-500">{{ $order->order_type === 'package' ? 'Package' : 'Service' }}</p>
                            </td>
                            <td class="px-4 py-3">
                                <p class="font-medium text-gray-900">{{ $order->contact_name ?: '—' }}</p>
                                <p class="mt-0.5 text-xs text-gray-500">{{ $order->contact_phone ?: $order->contact_email }}</p>
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 ring-inset {{ $statusStyles[$order->status] ?? 'bg-gray-100 text-gray-700 ring-gray-200' }}">{{ $order->statusLabel() }}</span>
                            </td>
                            <td class="px-4 py-3 font-medium {{ $paymentStyles[$order->payment_status] ?? 'text-gray-400' }}">{{ $order->payment_status ? ucfirst($order->payment_status) : '—' }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-gray-600">{{ $order->created_at?->format('d M Y, h:i A') }}</td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('developer.growth-orders.show', $order) }}" class="inline-flex items-center rounded-lg border border-indigo-200 bg-indigo-50 px-2.5 py-1.5 text-xs font-semibold text-indigo-700 hover:bg-indigo-100">Open</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-14 text-center">
                                <p class="text-sm font-medium text-gray-900">No service requests found.</p>
                                <p class="mt-1 text-sm text-gray-500">{{ $search !== '' || $status !== '' ? 'Try clearing the search or status filter.' : 'Requests from client libraries will appear here.' }}</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($orders->hasPages())
            <div class="border-t border-gray-100 px-4 py-3">{{ $orders->links() }}</div>
        @endif
    </section>
</x-admin-layout>
