<x-admin-layout>
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h1 class="lc-page-title">Payments</h1>
            <p class="mt-1 text-sm text-gray-600">PhonePe payments for LibControl plans, growth packages and services.</p>
        </div>
        <span class="inline-flex items-center gap-2 rounded-full px-3 py-1 text-xs font-semibold ring-1 ring-inset {{ $gatewayEnv === 'production' ? 'bg-green-50 text-green-700 ring-green-200' : 'bg-amber-50 text-amber-700 ring-amber-200' }}">
            PhonePe {{ $gatewayEnv === 'production' ? 'Live' : 'Test (sandbox)' }}{{ $gatewayConfigured ? '' : ' · not configured' }}
        </span>
    </div>

    @if (session('status'))
        <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('status') }}</div>
    @endif
    @error('payment')
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ $message }}</div>
    @enderror

    <div class="grid gap-4 sm:grid-cols-3">
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Collected</p>
            <p class="mt-2 text-2xl font-bold text-gray-900">₹{{ number_format($collectedPaise / 100) }}</p>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Paid / pending</p>
            <p class="mt-2 text-2xl font-bold text-gray-900">{{ number_format($counts['completed'] ?? 0) }} <span class="text-base font-medium text-gray-400">/ {{ number_format($counts['pending'] ?? 0) }}</span></p>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Webhook URL</p>
            <p class="mt-2 break-all font-mono text-xs text-gray-700">{{ $webhookUrl }}</p>
        </div>
    </div>

    <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <form method="GET" class="lc-table-toolbar flex flex-wrap items-center justify-between gap-3 border-b border-gray-200 px-4 py-3">
            <div class="flex flex-wrap items-center gap-1.5">
                <a href="{{ route('developer.payments.index', array_filter(['q' => $search])) }}" class="lc-toolbar-filter rounded-full border px-3 py-1 text-xs font-semibold {{ $status === '' ? 'is-active' : '' }}">All</a>
                @foreach ($statuses as $s)
                    <a href="{{ route('developer.payments.index', array_filter(['status' => $s, 'q' => $search])) }}" class="lc-toolbar-filter rounded-full border px-3 py-1 text-xs font-semibold {{ $status === $s ? 'is-active' : '' }}">
                        {{ ucfirst($s) }} <span class="opacity-70">{{ number_format($counts[$s] ?? 0) }}</span>
                    </a>
                @endforeach
            </div>
            <div class="flex items-center gap-2">
                @if ($status !== '')<input type="hidden" name="status" value="{{ $status }}">@endif
                <input type="search" name="q" value="{{ $search }}" placeholder="Reference, library, email..." class="w-full min-w-[220px] rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm sm:w-64">
                <button type="submit" class="rounded-lg bg-indigo-600 px-3 py-2 text-xs font-semibold text-white hover:bg-indigo-700">Search</button>
            </div>
        </form>
        @include('developer.payments.partials.table', ['payments' => $payments])
        @if ($payments->hasPages())
            <div class="border-t border-gray-200 px-4 py-3">{{ $payments->links() }}</div>
        @endif
    </section>
</x-admin-layout>
