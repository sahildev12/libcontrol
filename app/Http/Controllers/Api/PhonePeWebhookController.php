<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\Payments\PaymentService;
use App\Services\Payments\PhonePeClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PhonePeWebhookController extends Controller
{
    public function __invoke(Request $request, PhonePeClient $phonePe, PaymentService $payments): JsonResponse
    {
        if (! $phonePe->webhookAuthorizationIsValid($request->header('Authorization'))) {
            Log::warning('PhonePe webhook rejected: bad authorization.', ['ip' => $request->ip()]);

            return response()->json(['message' => 'Unauthorized.'], 401);
        }

        $body = json_decode($request->getContent(), true);
        $event = is_array($body) ? (string) ($body['event'] ?? $body['type'] ?? '') : '';
        $payload = is_array($body['payload'] ?? null) ? $body['payload'] : [];
        $merchantOrderId = (string) ($payload['merchantOrderId'] ?? '');

        if (! str_starts_with($event, 'checkout.order.') || $merchantOrderId === '') {
            return response()->json(['message' => 'Ignored.']);
        }

        $payment = Payment::query()->where('merchant_order_id', $merchantOrderId)->first();

        if (! $payment) {
            Log::info('PhonePe webhook for unknown order.', ['merchant_order_id' => $merchantOrderId, 'event' => $event]);

            return response()->json(['message' => 'Ignored.']);
        }

        try {
            // The webhook body is only a hint; the Order Status API is the source of truth.
            $payment = $payments->refreshStatus($payment);
        } catch (\Throwable $e) {
            Log::warning('PhonePe webhook could not verify status.', ['payment' => $payment->uuid, 'error' => $e->getMessage()]);

            return response()->json(['message' => 'Retry later.'], 503);
        }

        return response()->json(['message' => 'OK', 'status' => $payment->status]);
    }
}
