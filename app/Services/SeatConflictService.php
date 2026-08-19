<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\SeatBooking;
use Illuminate\Support\Carbon;

class SeatConflictService
{
    public function __construct(
        private LibraryScheduleService $schedule,
    ) {}

    public static function forBranch(Branch $branch): self
    {
        return new self(LibraryScheduleService::forBranch($branch));
    }

    /**
     * @return array{0: int, 1: int}
     */
    public function windowForBooking(SeatBooking|array $booking): array
    {
        $timeSlot = is_array($booking) ? $booking['time_slot'] : $booking->time_slot;
        $customStart = is_array($booking) ? ($booking['custom_start_time'] ?? null) : $booking->custom_start_time;
        $customEnd = is_array($booking) ? ($booking['custom_end_time'] ?? null) : $booking->custom_end_time;

        $customStart = $customStart ? substr((string) $customStart, 0, 5) : null;
        $customEnd = $customEnd ? substr((string) $customEnd, 0, 5) : null;

        return $this->schedule->slotWindow($timeSlot, $customStart, $customEnd);
    }

    public function hasConflict(int $seatId, string $timeSlot, Carbon $joiningDate, Carbon $planExpiryDate, ?string $customStart, ?string $customEnd, ?int $ignoreBookingId = null): bool
    {
        $candidate = [
            'time_slot' => $timeSlot,
            'custom_start_time' => $customStart,
            'custom_end_time' => $customEnd,
            'joining_date' => $joiningDate->toDateString(),
            'plan_expiry_date' => $planExpiryDate->toDateString(),
        ];

        [$cStart, $cEnd] = $this->windowForBooking($candidate);

        $bookings = SeatBooking::query()
            ->where('seat_id', $seatId)
            ->whereNull('cancelled_at')
            ->where('status', '!=', 'cancelled')
            ->when($ignoreBookingId, fn ($q) => $q->where('id', '!=', $ignoreBookingId))
            ->get();

        $today = Carbon::now(config('libspace.timezone', 'Asia/Kolkata'))->startOfDay();

        foreach ($bookings as $booking) {
            if ($planExpiryDate->lt($booking->joining_date) || $joiningDate->gt($booking->plan_expiry_date)) {
                continue;
            }

            if ($booking->trial_end && $booking->trial_end->lt($today)) {
                continue;
            }

            if ($booking->plan_expiry_date->lt($today) && $booking->status !== 'on_trial') {
                continue;
            }

            [$bStart, $bEnd] = $this->windowForBooking($booking);

            if ($cStart < $bEnd && $cEnd > $bStart) {
                return true;
            }
        }

        return false;
    }

    public function hasConflictForDateRange(
        int $seatId,
        string $timeSlot,
        Carbon $startDate,
        Carbon $endDate,
        ?string $customStart,
        ?string $customEnd,
        ?int $ignoreBookingId = null,
    ): bool {
        $cursor = $startDate->copy();

        while ($cursor->lte($endDate)) {
            if ($this->hasConflict($seatId, $timeSlot, $cursor, $cursor, $customStart, $customEnd, $ignoreBookingId)) {
                return true;
            }

            $cursor->addDay();
        }

        return false;
    }
}
