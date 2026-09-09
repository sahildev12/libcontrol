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
    public function payloadForBranch(?int $branchId): array
    {
        $hallQuery = Hall::query()->with('branch:id,name')->orderBy('name');

        if ($branchId) {
            $hallQuery->where('branch_id', $branchId);
        }

        $halls = $hallQuery->get(['id', 'name', 'seat_capacity', 'branch_id']);

        $seatQuery = Seat::query()
            ->with([
                'hall.branch',
                'bookings.student:id,student_code,name,student_type',
            ])
            ->orderBy('hall_id')
            ->orderByRaw('CAST(seat_number AS UNSIGNED)')
            ->orderBy('seat_number')
            ->orderBy('row_number')
            ->orderBy('column_number');

        if ($branchId) {
            $seatQuery->whereHas('hall', fn ($query) => $query->where('branch_id', $branchId));
        }

        $seats = $seatQuery->get();
        $includeBranchName = $branchId === null;
        $scheduleBranch = $branchId
            ? Branch::query()->find($branchId)
            : $halls->first()?->branch;

        return [
            'halls' => $halls->map(fn (Hall $hall) => [
                'id' => $hall->id,
                'branch_id' => $hall->branch_id,
                'name' => $includeBranchName && $hall->branch
                    ? "{$hall->name} ({$hall->branch->name})"
                    : $hall->name,
                'seat_capacity' => $hall->seat_capacity,
            ])->values()->all(),
            'seats' => $this->seatStatusService->mapSeatsForBranch($seats, $scheduleBranch),
            'time_slot_options' => $scheduleBranch
                ? LibraryScheduleService::forBranch($scheduleBranch)->timeSlotOptions()
                : LibraryScheduleService::defaultOptions(),
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
