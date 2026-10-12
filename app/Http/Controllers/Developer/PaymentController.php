<?php

namespace App\Http\Controllers\Developer;

use App\Http\Controllers\Controller;
use App\Models\LicensedDeployment;
use App\Models\Payment;
use App\Services\Payments\PaymentService;
use App\Services\Payments\PhonePeClient;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function index(Request $request, PhonePeClient $phonePe): View
    {
        $status = $request->string('status')->toString();
        $search = trim($request->string('q')->toString());
        $statuses = [Payment::STATUS_COMPLETED, Payment::STATUS_PENDING, Payment::STATUS_FAILED, Payment::STATUS_CREATED];
        $deployment = $request->filled('deployment')
            ? LicensedDeployment::query()->find($request->integer('deployment'))
            : null;

        $payments = Payment::query()
            ->with('growthOrder:id,uuid')
            ->when($deployment, fn (Builder $q) => $q->where('licensed_deployment_id', $deployment->id))
            ->when(in_array($status, $statuses, true), fn (Builder $q) => $q->where('status', $status))
            ->when($search !== '', function (Builder $q) use ($search) {
                $like = '%'.$search.'%';
                $q->where(fn (Builder $w) => $w
                    ->where('merchant_order_id', 'like', $like)
                    ->orWhere('gateway_transaction_id', 'like', $like)
                    ->orWhere('item_name', 'like', $like)
                    ->orWhere('library_name', 'like', $like)
                    ->orWhere('deployment_domain', 'like', $like)
                    ->orWhere('payer_email', 'like', $like)
                    ->orWhere('payer_phone', 'like', $like));
            })
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        $counts = Payment::query()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        return view('developer.payments.index', [
            'payments' => $payments,
            'status' => $status,
            'search' => $search,
            'deployment' => $deployment,
            'statuses' => $statuses,
            'counts' => $counts,
            'collectedPaise' => (int) Payment::query()->where('status', Payment::STATUS_COMPLETED)->sum('amount_paise'),
            'gatewayEnv' => $phonePe->environment(),
            'gatewayConfigured' => $phonePe->configured(),
            'webhookUrl' => route('payments.webhooks.phonepe'),
        ]);
    }

    public function refresh(Payment $payment, PaymentService $payments): RedirectResponse
    {
        try {
            $payment = $payments->refreshStatus($payment);
        } catch (\Throwable $e) {
            return back()->withErrors(['payment' => $e->getMessage()]);
        }

        return back()->with('status', "{$payment->merchant_order_id}: {$payment->statusLabel()}");
    }
}
