<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Enquiry;
use App\Models\Hall;
use App\Models\Seat;
use App\Models\Student;
use Illuminate\Support\Carbon;

class DashboardService
{
    public function __construct(
        private SeatStatusService $seatStatusService,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function statsForBranch(?int $branchId): array
    {
        $seats = Seat::query()
            ->with(['bookings.student', 'hall.branch'])
            ->when($branchId, fn ($query) => $query->whereHas('hall', fn ($hallQuery) => $hallQuery->where('branch_id', $branchId)))
            ->get();

        $counts = [
            'total_seats' => $seats->count(),
            'occupied' => 0,
            'available' => 0,
            'expiring_soon' => 0,
            'expired' => 0,
            'on_trial' => 0,
        ];

        $branch = $branchId ? Branch::query()->find($branchId) : null;

        foreach ($seats as $seat) {
            $status = $this->seatStatusService->resolveForSeat($seat, $seat->hall?->branch ?? $branch);

            if ($status === 'occupied') {
                $counts['occupied']++;
            } elseif ($status === 'available') {
                $counts['available']++;
            } elseif ($status === 'expiring_soon') {
                $counts['expiring_soon']++;
            } elseif ($status === 'expired') {
                $counts['expired']++;
            } elseif ($status === 'on_trial') {
                $counts['on_trial']++;
            }
        }

        return [
            ...$counts,
            'total_students' => Student::query()->when($branchId, fn ($query) => $query->where('branch_id', $branchId))->count(),
            'total_halls' => Hall::query()->when($branchId, fn ($query) => $query->where('branch_id', $branchId))->count(),
            'new_enquiries' => Enquiry::query()->when($branchId, fn ($query) => $query->where('branch_id', $branchId))->where('status', 'new')->count(),
        ];
    }
}
