<?php

namespace App\Services;

use App\Models\SeatBooking;
use Carbon\Carbon;

class PlanExpiryService
{
    public function calculate(string $feeType, Carbon $joiningDate, ?Carbon $customEnd = null): Carbon
    {
        return match ($feeType) {
            'yearly' => $joiningDate->copy()->addYear(),
            'custom' => $customEnd ?? $joiningDate->copy()->addMonth(),
            default => $joiningDate->copy()->addMonth(),
        };
    }
}
