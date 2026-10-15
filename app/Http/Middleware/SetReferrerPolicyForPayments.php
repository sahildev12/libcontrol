<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * PhonePe PG checkout needs the merchant site Referer on mercury*.phonepe.com (QR / UPI).
 *
 * @see https://developer.phonepe.com/payment-gateway/website-integration/standard-checkout/api-integration/troubleshoot
 */
class SetReferrerPolicyForPayments
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $response->headers->has('Referrer-Policy')) {
            $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin', false);
        }

        return $response;
    }
}
