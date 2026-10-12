@php
    $paymentStatusStyles = [
        'completed' => 'bg-green-50 text-green-700 ring-green-200',
        'failed' => 'bg-red-50 text-red-700 ring-red-200',
        'pending' => 'bg-amber-50 text-amber-700 ring-amber-200',
        'created' => 'bg-slate-100 text-slate-600 ring-slate-200',
    ];
@endphp
<div class="overflow-x-auto">
    <table class="w-full min-w-[900px] text-sm">
        <thead class="border-b border-gray-200 bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
            <tr>
                <th class="px-4 py-3">Item</th>
                <th class="px-4 py-3">Library</th>
                <th class="px-4 py-3">Amount</th>
                <th class="px-4 py-3">Reference</th>
                <th class="px-4 py-3">Date</th>
                <th class="px-4 py-3">Status</th>
                <th class="px-4 py-3 text-right">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 text-gray-700">
            @forelse ($payments as $payment)
                <tr>
                    <td class="px-4 py-3">
                        <p class="font-semibold text-gray-900">{{ $payment->item_name }}</p>
                        <p class="text-xs text-gray-500">
                            {{ $payment->purposeLabel() }}
                            @if ($payment->growthOrder)
                                · <a href="{{ route('developer.growth-orders.show', $payment->growthOrder) }}" class="font-semibold text-indigo-600 hover:underline">Open order</a>
                            @endif
                        </p>
                    </td>
                    <td class="px-4 py-3">
                        @if ($payment->licensed_deployment_id)
                            <a href="{{ route('developer.deployments.manage', $payment->licensed_deployment_id) }}" class="font-medium text-indigo-600 hover:underline">{{ $payment->library_name ?: 'Library #'.$payment->licensed_deployment_id }}</a>
                        @else
                            <p class="font-medium text-gray-900">{{ $payment->library_name ?: '—' }}</p>
                        @endif
                        <p class="text-xs text-gray-500">{{ $payment->deployment_domain ?: '—' }} · {{ $payment->payer_email ?: '' }}</p>
                    </td>
                    <td class="whitespace-nowrap px-4 py-3 font-semibold text-gray-900">{{ $payment->amountLabel() }}</td>
                    <td class="px-4 py-3 font-mono text-xs text-gray-600">
                        {{ $payment->merchant_order_id }}
                        @if ($payment->gateway_transaction_id)<br><span class="text-gray-400">{{ $payment->gateway_transaction_id }}</span>@endif
                        @if ($payment->payment_mode)<br><span class="text-gray-400">{{ $payment->payment_mode }}</span>@endif
                    </td>
                    <td class="whitespace-nowrap px-4 py-3 text-gray-500">{{ ($payment->paid_at ?? $payment->created_at)?->format('d M Y, h:i A') }}</td>
                    <td class="px-4 py-3">
                        <span class="rounded-full px-2.5 py-1 text-[11px] font-semibold ring-1 ring-inset {{ $paymentStatusStyles[$payment->status] ?? $paymentStatusStyles['created'] }}">{{ $payment->statusLabel() }}</span>
                        @if ($payment->error_code)<p class="mt-1 text-xs text-red-600">{{ $payment->error_code }}</p>@endif
                        @if ($payment->webhookReceivedAt())
                            <p class="mt-1 text-[11px] text-gray-400" title="{{ $payment->meta['webhook_event'] ?? '' }}">Webhook received</p>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-right">
                        @unless ($payment->isFinal())
                            <form method="POST" action="{{ route('developer.payments.refresh', $payment) }}">
                                @csrf
                                <button type="submit" class="rounded-lg border border-indigo-200 bg-indigo-50 px-2.5 py-1.5 text-xs font-semibold text-indigo-700 hover:bg-indigo-100">Check status</button>
                            </form>
                        @endunless
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="px-4 py-10 text-center text-gray-500">No payments yet.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
