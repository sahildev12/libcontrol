<x-admin-layout>
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <a href="{{ route('developer.growth-orders.index') }}" class="text-sm font-semibold text-indigo-600 hover:underline">&larr; Service Requests</a>
            <h1 class="lc-page-title mt-2">{{ $order->item_name }}</h1>
            <p class="mt-1 text-sm text-gray-600">{{ $order->library_name ?: 'Unknown library' }} · {{ $order->deployment_domain ?: ($order->library_code ?: '—') }}</p>
        </div>
        <span class="inline-flex rounded-full bg-indigo-50 px-3 py-1 text-xs font-semibold text-indigo-700 ring-1 ring-inset ring-indigo-200">{{ $order->statusLabel() }}</span>
    </div>

    @if (session('status'))
        <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('status') }}</div>
    @endif

    <div class="grid gap-4 lg:grid-cols-3">
        <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm lg:col-span-2">
            <div class="border-b border-gray-200 px-5 py-4">
                <h2 class="text-sm font-semibold text-gray-900">Request details</h2>
            </div>
            <div class="p-5">
                <dl class="grid gap-4 text-sm sm:grid-cols-2">
                    <div><dt class="text-xs font-medium uppercase tracking-wide text-gray-500">Type</dt><dd class="mt-1 font-medium text-gray-900">{{ $order->order_type === 'package' ? 'Package' : 'Service' }} <span class="text-gray-400">({{ $order->item_key }})</span></dd></div>
                    <div><dt class="text-xs font-medium uppercase tracking-wide text-gray-500">Amount</dt><dd class="mt-1 font-medium text-gray-900">{{ $order->amount_paise ? '₹'.number_format($order->amount_paise / 100) : '—' }}</dd></div>
                    <div><dt class="text-xs font-medium uppercase tracking-wide text-gray-500">Received</dt><dd class="mt-1 font-medium text-gray-900">{{ $order->created_at?->format('d M Y, h:i A') }}</dd></div>
                    <div><dt class="text-xs font-medium uppercase tracking-wide text-gray-500">Request ID</dt><dd class="mt-1 break-all font-mono text-xs text-gray-600">{{ $order->uuid }}</dd></div>
                </dl>
                <div class="mt-5">
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Client message</p>
                    <p class="mt-1 whitespace-pre-wrap rounded-lg bg-gray-50 p-3 text-sm text-gray-800">{{ $order->message ?: '—' }}</p>
                </div>
            </div>
        </section>

        <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <div class="border-b border-gray-200 px-5 py-4">
                <h2 class="text-sm font-semibold text-gray-900">Contact</h2>
            </div>
            <dl class="space-y-4 p-5 text-sm">
                <div><dt class="text-xs font-medium uppercase tracking-wide text-gray-500">Name</dt><dd class="mt-1 font-medium text-gray-900">{{ $order->contact_name ?: '—' }}</dd></div>
                <div>
                    <dt class="text-xs font-medium uppercase tracking-wide text-gray-500">Email</dt>
                    <dd class="mt-1">@if ($order->contact_email)<a href="mailto:{{ $order->contact_email }}" class="break-all font-medium text-indigo-600 hover:underline">{{ $order->contact_email }}</a>@else — @endif</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase tracking-wide text-gray-500">Phone</dt>
                    <dd class="mt-1">@if ($order->contact_phone)<a href="tel:{{ $order->contact_phone }}" class="font-medium text-indigo-600 hover:underline">{{ $order->contact_phone }}</a>@else — @endif</dd>
                </div>
                @if ($order->supportTicket)
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wide text-gray-500">Linked ticket</dt>
                        <dd class="mt-1"><a href="{{ route('developer.support-tickets.show', $order->supportTicket) }}" class="font-medium text-indigo-600 hover:underline">Ticket #{{ $order->supportTicket->id }}</a></dd>
                    </div>
                @endif
            </dl>
        </section>
    </div>

    <form method="POST" action="{{ route('developer.growth-orders.update', $order) }}" class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        @csrf
        @method('PATCH')
        <div class="border-b border-gray-200 px-5 py-4">
            <h2 class="text-sm font-semibold text-gray-900">Update request</h2>
            <p class="mt-1 text-xs text-gray-500">The library sees the status and your message on their Growth page.</p>
        </div>
        <div class="grid gap-4 p-5 md:grid-cols-2">
            <div>
                <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
                <select id="status" name="status" class="admin-select mt-1 block w-full px-3 py-2">
                    @foreach ($statuses as $s)
                        <option value="{{ $s }}" @selected(old('status', $order->status) === $s)>{{ ucfirst($s) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="payment_status" class="block text-sm font-medium text-gray-700">Payment</label>
                <select id="payment_status" name="payment_status" class="admin-select mt-1 block w-full px-3 py-2">
                    <option value="">Not set</option>
                    @foreach (['pending', 'paid', 'failed'] as $s)
                        <option value="{{ $s }}" @selected(old('payment_status', $order->payment_status) === $s)>{{ ucfirst($s) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="md:col-span-2">
                <label for="monthly_report_url" class="block text-sm font-medium text-gray-700">Report link</label>
                <input id="monthly_report_url" type="url" name="monthly_report_url" value="{{ old('monthly_report_url', $order->monthly_report_url) }}" class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" placeholder="https://...">
                @error('monthly_report_url')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
            <div class="md:col-span-2">
                <label for="admin_notes" class="block text-sm font-medium text-gray-700">Message to the library</label>
                <textarea id="admin_notes" name="admin_notes" rows="4" class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" placeholder="e.g. Quote sent on WhatsApp, work starts Monday.">{{ old('admin_notes', $order->admin_notes) }}</textarea>
            </div>
            <div class="flex justify-end md:col-span-2">
                <button type="submit" class="rounded-lg bg-indigo-600 px-5 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Save update</button>
            </div>
        </div>
    </form>
</x-admin-layout>
