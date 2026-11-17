<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use Illuminate\Http\Response;
use Illuminate\View\View;

/**
 * Blank hub hop so PhonePe sees the onboarded origin (libcontrol.in) as document.referrer.
 * A 302 is not enough — browsers keep the tenant referrer. JS replace from this page fixes QR.
 */
class PaymentLaunchController extends Controller
{
    public function __invoke(string $uuid): View|Response
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

        return response()
            ->view('payments.launch', ['phonePeUrl' => $redirect])
            ->header('Referrer-Policy', 'origin');
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
