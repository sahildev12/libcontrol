<?php

namespace App\Http\Controllers;

use App\Models\StudentRegistrationInvite;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StudentRegistrationInviteController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        abort_unless(
            app(\App\Services\StudentCodeService::class)->prefixIsConfigured(),
            422,
            'Set the global student code prefix in Settings before sharing registration links.',
        );

        $branchId = $request->integer('branch_id') ?: $this->optionalActiveBranchId($request);
        abort_unless($branchId, 422, 'Select a branch before creating a registration link.');
        $this->assertCanAccessBranch($request, $branchId);

        $invite = StudentRegistrationInvite::createForBranch(
            $branchId,
            $request->user()?->id,
        );

        return response()->json([
            'message' => 'Registration link created. Valid for 2 hours and single use.',
            'invite' => [
                'url' => $invite->publicUrl(),
                'expires_at' => $invite->expires_at->toIso8601String(),
                'expires_label' => $invite->expires_at->format('M d, Y h:i A'),
            ],
        ], 201);
    }
}
