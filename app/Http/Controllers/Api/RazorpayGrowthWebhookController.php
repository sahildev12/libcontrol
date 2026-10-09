<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Growth\RazorpayGrowthBillingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RazorpayGrowthWebhookController extends Controller
{
    public function __invoke(Request $request, RazorpayGrowthBillingService $billing): JsonResponse
    {
        if (! $billing->enabled()) {
            return response()->json(['message' => 'Razorpay growth billing disabled.'], 404);
        }

        $payload = $request->getContent();
        $signature = (string) $request->header('X-Razorpay-Signature', '');

        if (! $billing->verifyWebhookSignature($payload, $signature)) {
            return response()->json(['message' => 'Invalid signature.'], 400);
        }

        $data = json_decode($payload, true);
        if (! is_array($data)) {
            return response()->json(['message' => 'Invalid payload.'], 400);
        }

        $billing->handleWebhook($data);

        return response()->json(['ok' => true]);
    }
}
