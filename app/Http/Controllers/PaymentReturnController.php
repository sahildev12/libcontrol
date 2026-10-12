<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Services\Payments\PaymentService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PaymentReturnController extends Controller
{
    public function __invoke(Request $request, string $uuid, PaymentService $payments): View
    {
        abort_unless((bool) config('libcontrol.license_server.enabled'), 404);

        $payment = Payment::query()->where('uuid', $uuid)->firstOrFail();

        try {
            $payment = $payments->refreshStatus($payment);
        } catch (\Throwable) {
            $payment->refresh();
        }

        $backUrl = null;
        if ($payment->return_url) {
            [$base, $fragment] = array_pad(explode('#', $payment->return_url, 2), 2, null);
            $backUrl = $base.(str_contains($base, '?') ? '&' : '?').'payment='.$payment->uuid.($fragment ? '#'.$fragment : '');
        }

        return view('payments.return', [
            'payment' => $payment,
            'backUrl' => $backUrl,
        ]);
    }
}
