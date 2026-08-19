<?php

namespace App\Services;

use App\Events\SeatMapUpdated;
use App\Models\Branch;
use App\Models\Hall;
use App\Models\Seat;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class SeatMapService
{
    public function __construct(
        private SeatStatusService $seatStatusService,
    ) {}

    /**
     * @return array{halls: list<array<string, mixed>>, seats: list<array<string, mixed>>, time_slot_options: list<array{value: string, label: string}>}
     */
    public function payloadForBranch(int $branchId): array
    {
        $branch = Branch::query()->findOrFail($branchId);
        $schedule = LibraryScheduleService::forBranch($branch);

        $halls = Hall::query()
            ->where('branch_id', $branchId)
            ->orderBy('name')
            ->get(['id', 'name', 'seat_capacity']);

        $seats = Seat::query()
            ->with([
                'hall:id,name,branch_id',
                'bookings.student:id,student_code,name,student_type',
            ])
            ->whereHas('hall', fn ($query) => $query->where('branch_id', $branchId))
            ->orderBy('hall_id')
            ->orderBy('row_number')
            ->orderBy('column_number')
            ->get();

        return [
            'halls' => $halls->map(fn (Hall $hall) => [
                'id' => $hall->id,
                'name' => $hall->name,
                'seat_capacity' => $hall->seat_capacity,
            ])->values()->all(),
            'seats' => $this->seatStatusService->mapSeatsForBranch($seats, $branch),
            'time_slot_options' => $schedule->timeSlotOptions(),
        ];
    }

    public function broadcastForAuthenticatedBranch(): void
    {
        $branchId = Auth::user()?->branch_id;

        if (! $branchId) {
            return;
        }

        $this->broadcastForBranch($branchId);
    }

    public function broadcastForBranch(int $branchId): void
    {
        try {
            broadcast(new SeatMapUpdated($branchId, $this->payloadForBranch($branchId)));
        } catch (\Throwable $exception) {
            Log::warning('Seat map broadcast failed.', [
                'branch_id' => $branchId,
                'message' => $exception->getMessage(),
            ]);
        }
    }
}
