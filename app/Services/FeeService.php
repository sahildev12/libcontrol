<?php

namespace App\Services;

use App\Models\SeatBooking;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class FeeService
{
    public function baseQuery(int $branchId): Builder
    {
        return SeatBooking::query()
            ->with(['student:id,student_code,name,phone,email', 'seat.hall:id,name'])
            ->whereHas('seat.hall', fn ($query) => $query->where('branch_id', $branchId))
            ->whereNull('cancelled_at')
            ->where('status', '!=', 'cancelled');
    }

    /**
     * @return array{expiring_soon: Collection, expired: Collection, active: Collection}
     */
    public function overviewForBranch(int $branchId): array
    {
        $today = Carbon::today();
        $soon = $today->copy()->addDays(7);
        $base = $this->baseQuery($branchId);

        return [
            'expiring_soon' => (clone $base)
                ->whereDate('plan_expiry_date', '>=', $today)
                ->whereDate('plan_expiry_date', '<=', $soon)
                ->orderBy('plan_expiry_date')
                ->get(),
            'expired' => (clone $base)
                ->whereDate('plan_expiry_date', '<', $today)
                ->orderByDesc('plan_expiry_date')
                ->get(),
            'active' => (clone $base)
                ->whereDate('plan_expiry_date', '>', $soon)
                ->orderBy('plan_expiry_date')
                ->get(),
        ];
    }

    public function paymentStatus(SeatBooking $booking): string
    {
        $today = Carbon::today();
        $soon = $today->copy()->addDays(7);

        if ($booking->plan_expiry_date->lt($today)) {
            return 'expired';
        }

        if ($booking->plan_expiry_date->lte($soon)) {
            return 'expiring_soon';
        }

        return 'active';
    }

    /**
     * @return array<string, mixed>
     */
    public function serializeRow(SeatBooking $booking): array
    {
        return [
            'id' => $booking->id,
            'student_code' => $booking->student?->student_code,
            'student_name' => $booking->student?->name,
            'student_phone' => $booking->student?->phone,
            'hall_name' => $booking->seat?->hall?->name,
            'seat_number' => $booking->seat?->seat_number,
            'time_slot' => $booking->time_slot,
            'fee_type' => $booking->fee_type,
            'fee_amount' => $booking->fee_amount,
            'joining_date' => $booking->joining_date?->format('M d, Y'),
            'plan_expiry_date' => $booking->plan_expiry_date?->format('M d, Y'),
            'payment_status' => $this->paymentStatus($booking),
            'membership_mode' => $booking->membership_mode,
        ];
    }
}
