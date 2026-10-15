<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Services\Payments\PaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PaymentReturnController extends Controller
{
    public function __invoke(Request $request, string $uuid, PaymentService $payments): RedirectResponse
    {
        abort_unless((bool) config('libcontrol.license_server.enabled'), 404);

        $payment = Payment::query()->where('uuid', $uuid)->firstOrFail();

        try {
            $payment = $payments->refreshStatus($payment);
        } catch (\Throwable) {
            $payment->refresh();
        }

        $target = $this->clientReturnUrl($payment) ?? (string) config('app.url');

        return redirect()->away($target);
    }

    private function clientReturnUrl(Payment $payment): ?string
    {
        if (! $payment->return_url) {
            return null;
        }

        [$base, $fragment] = array_pad(explode('#', $payment->return_url, 2), 2, null);

        return $base.(str_contains($base, '?') ? '&' : '?').'payment='.$payment->uuid.($fragment ? '#'.$fragment : '');
    }
}
