<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Services\Payments\PhonePeClient;
use Illuminate\View\View;

/**
 * Checkout runs on the approved merchant host (libcontrol.in) before / inside PhonePe UI.
 */
class PaymentLaunchController extends Controller
{
    public function __invoke(string $uuid, PhonePeClient $phonePe): View
    {
        $payment = Payment::query()->where('uuid', $uuid)->firstOrFail();

        if ($payment->isFinal()) {
            abort(410, 'This payment is already finished.');
        }

        $redirect = is_string($payment->meta['phonepe_redirect_url'] ?? null)
            ? (string) $payment->meta['phonepe_redirect_url']
            : '';

        if ($redirect === '' || ! $this->isPhonePeUrl($redirect)) {
            abort(404, 'Payment session not found.');
        }

        $env = $phonePe->environment();

        return view('payments.launch', [
            'payment' => $payment,
            'phonePeUrl' => $redirect,
            'checkoutScript' => (string) config("payments.phonepe.checkout_js.{$env}"),
        ]);
    }

    private function isPhonePeUrl(string $url): bool
    {
        if (! filter_var($url, FILTER_VALIDATE_URL) || strtolower((string) parse_url($url, PHP_URL_SCHEME)) !== 'https') {
            return false;
        }

        $host = strtolower((string) parse_url($url, PHP_URL_HOST));

        return $host === 'phonepe.com' || str_ends_with($host, '.phonepe.com');
    }
}
