<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LicensedDeployment;
use App\Models\Payment;
use App\Services\Payments\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Hub endpoints used by client installs. Requests are signed with the install's license key
 * (X-Sync-Token = HMAC-SHA256(body, key)) and the key must belong to an active deployment.
 */
class PaymentApiController extends Controller
{
    public function checkout(Request $request, PaymentService $payments): JsonResponse
    {
        $auth = $this->authenticate($request);
        if ($auth instanceof JsonResponse) {
            return $auth;
        }

        [$deployment, $payload] = $auth;

        try {
            $result = $payments->startCheckout($deployment, [
                'purpose' => (string) ($payload['purpose'] ?? ''),
                'item_key' => $payload['item_key'] ?? null,
                'billing_cycle' => $payload['billing_cycle'] ?? null,
                'growth_order_uuid' => $payload['growth_order_uuid'] ?? null,
                'payer_name' => $payload['payer_name'] ?? null,
                'payer_email' => $payload['payer_email'] ?? null,
                'payer_phone' => $payload['payer_phone'] ?? null,
                'return_url' => $payload['return_url'] ?? null,
                'domain' => $payload['domain'] ?? null,
                'library_code' => $payload['library_code'] ?? null,
                'library_name' => $payload['library_name'] ?? null,
            ]);
        } catch (ValidationException $e) {
            return response()->json(['message' => collect($e->errors())->flatten()->first() ?: 'Payment could not be started.'], 422);
        }

        return response()->json([
            'payment' => $payments->publicPayload($result['payment']),
            'redirect_url' => $result['redirect_url'],
        ], 201);
    }

    public function summary(Request $request, PaymentService $payments): JsonResponse
    {
        $auth = $this->authenticate($request);
        if ($auth instanceof JsonResponse) {
            return $auth;
        }

        [$deployment, $payload] = $auth;

        $refresh = is_string($payload['refresh_payment'] ?? null) ? $payload['refresh_payment'] : null;
        if ($refresh) {
            $payment = Payment::query()
                ->where('uuid', $refresh)
                ->where('licensed_deployment_id', $deployment->id)
                ->first();

            if ($payment && ! $payment->isFinal()) {
                try {
                    $payments->refreshStatus($payment);
                } catch (\Throwable) {
                    // Status stays pending; webhook or reconcile will settle it.
                }
                $deployment->refresh();
            }
        }

        return response()->json($payments->summaryFor($deployment));
    }

    /**
     * @return array{0: LicensedDeployment, 1: array<string, mixed>}|JsonResponse
     */
    private function authenticate(Request $request): array|JsonResponse
    {
        $rawBody = $request->getContent();
        $payload = json_decode($rawBody, true);

        if (! is_array($payload)) {
            return response()->json(['message' => 'Invalid payload.'], 422);
        }

        $licenseKey = trim((string) $request->header('X-License-Key', ''));
        $token = (string) $request->header('X-Sync-Token', '');

        if ($token === '' || LicensedDeployment::isPlaceholderLicenseKey($licenseKey)) {
            return response()->json(['message' => 'A valid LibControl license key is required for payments.'], 401);
        }

        if (! hash_equals(hash_hmac('sha256', $rawBody, $licenseKey), $token)) {
            return response()->json(['message' => 'Unauthorized.'], 401);
        }

        $deployment = LicensedDeployment::findByLicenseKey($licenseKey);

        if (! $deployment || ! $deployment->active) {
            return response()->json(['message' => 'This installation is not registered with Katsindia.'], 403);
        }

        $domain = LicensedDeployment::normalizeDomain((string) ($payload['domain'] ?? ''));
        if ($domain === '' || ! $deployment->allowsDomain($domain)) {
            return response()->json(['message' => 'This domain is not authorized for the license key.'], 403);
        }

        return [$deployment, $payload];
    }
}
