<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Services\SeatMapService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SeatController extends Controller
{
    public function index(Request $request, SeatMapService $seatMapService): View
    {
        $branchId = $this->activeBranchId($request);
        $branch = $this->activeBranch($request);
        $payload = $seatMapService->payloadForBranch($branchId);

        $students = Student::query()
            ->where('branch_id', $branchId)
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'student_code', 'name', 'phone', 'student_type']);

        return view('seats.index', [
            'halls' => $payload['halls'],
            'seats' => $payload['seats'],
            'students' => $students,
            'timeSlotOptions' => $payload['time_slot_options'],
            'branchName' => $branch->name,
        ]);
    }

    public function data(Request $request, SeatMapService $seatMapService): JsonResponse
    {
        return response()->json(
            $seatMapService->payloadForBranch($this->activeBranchId($request))
        );
    }
}
