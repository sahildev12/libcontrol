<?php

namespace App\Http\Controllers;

use App\Services\SeatMapService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WebhookController extends Controller
{
    public function refreshSeatMap(Request $request, SeatMapService $seatMapService): JsonResponse
    {
        $token = $request->header('X-LibSpace-Webhook-Token')
            ?? $request->input('token');

        abort_unless(
            is_string($token) && hash_equals((string) config('services.libspace.webhook_token'), $token),
            401,
            'Invalid webhook token.'
        );

        $branchId = (int) $request->input('branch_id', $request->user()?->branch_id);

        abort_unless($branchId > 0, 422, 'branch_id is required.');

        $seatMapService->broadcastForBranch($branchId);

        return response()->json([
            'ok' => true,
            'message' => 'Seat map broadcast triggered.',
            'branch_id' => $branchId,
        ]);
    }
}
