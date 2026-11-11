<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminTipTourController extends Controller
{
    public function complete(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user?->isClientAdmin()) {
            abort(403);
        }

        $admin = $user->adminProfile;

        if (! $admin) {
            abort(403);
        }

        if ($admin->tour_completed_at === null) {
            $admin->forceFill(['tour_completed_at' => now()])->save();
        }

        return response()->json([
            'ok' => true,
            'tour_completed' => true,
            'tour_completed_at' => $admin->fresh()->tour_completed_at?->toIso8601String(),
        ]);
    }
}
