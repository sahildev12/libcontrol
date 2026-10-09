<x-admin-layout>
    <div class="mx-auto max-w-3xl space-y-6">
        <div>
            <a href="{{ route('developer.growth-orders.index') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-800">← Growth Orders</a>
            <h1 class="mt-2 text-2xl font-bold text-gray-900">{{ $order->item_name }}</h1>
            <p class="mt-1 text-sm text-gray-600">{{ $order->uuid }}</p>
        </div>

        @if (session('status'))
            <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('status') }}</div>
        @endif

        <section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm text-sm">
            <dl class="grid gap-3 sm:grid-cols-2">
                <div><dt class="text-gray-500">Type</dt><dd class="font-medium">{{ $order->order_type }} / {{ $order->item_key }}</dd></div>
                <div><dt class="text-gray-500">Status</dt><dd class="font-medium">{{ $order->statusLabel() }}</dd></div>
                <div><dt class="text-gray-500">Library</dt><dd class="font-medium">{{ $order->library_name ?: '—' }}</dd></div>
                <div><dt class="text-gray-500">Branch</dt><dd class="font-medium">{{ $order->branch?->name ?: ('#'.$order->branch_id) }}</dd></div>
                <div><dt class="text-gray-500">Contact</dt><dd class="font-medium">{{ $order->contact_name }} &lt;{{ $order->contact_email }}&gt;</dd></div>
                <div><dt class="text-gray-500">Phone</dt><dd class="font-medium">{{ $order->contact_phone ?: '—' }}</dd></div>
                <div><dt class="text-gray-500">Payment</dt><dd class="font-medium">{{ $order->payment_status ?: '—' }}</dd></div>
                <div><dt class="text-gray-500">Amount</dt><dd class="font-medium">{{ $order->amount_paise ? '₹'.number_format($order->amount_paise / 100, 0) : '—' }}</dd></div>
            </dl>
            <div class="mt-4">
                <p class="text-gray-500">Client message</p>
                <p class="mt-1 whitespace-pre-wrap text-gray-900">{{ $order->message ?: '—' }}</p>
            </div>
        </section>

        <form method="POST" action="{{ route('developer.growth-orders.update', $order) }}" class="space-y-4 rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            @csrf
            @method('PATCH')
            <div>
                <label class="block text-sm font-medium text-gray-700">Status</label>
                <select name="status" class="mt-1 w-full rounded-lg border-gray-300 text-sm">
                    @foreach (['new','quoted','active','paused','completed','cancelled'] as $s)
                        <option value="{{ $s }}" @selected($order->status === $s)>{{ ucfirst($s) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Payment status</label>
                <select name="payment_status" class="mt-1 w-full rounded-lg border-gray-300 text-sm">
                    <option value="">—</option>
                    @foreach (['pending','paid','failed'] as $s)
                        <option value="{{ $s }}" @selected($order->payment_status === $s)>{{ ucfirst($s) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Monthly report URL</label>
                <input type="url" name="monthly_report_url" value="{{ old('monthly_report_url', $order->monthly_report_url) }}" class="mt-1 w-full rounded-lg border-gray-300 text-sm" placeholder="https://...">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Admin notes</label>
                <textarea name="admin_notes" rows="4" class="mt-1 w-full rounded-lg border-gray-300 text-sm">{{ old('admin_notes', $order->admin_notes) }}</textarea>
            </div>
            <button type="submit" class="inline-flex h-10 items-center rounded-lg bg-indigo-600 px-5 text-sm font-semibold text-white hover:bg-indigo-700">Save</button>
        </form>
    </div>
</x-admin-layout>
