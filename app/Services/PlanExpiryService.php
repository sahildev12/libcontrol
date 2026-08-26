<?php

namespace App\Services;

use Carbon\Carbon;

class PlanExpiryService
{
    public function calculate(string $feeType, Carbon $joiningDate, ?Carbon $customEnd = null): Carbon
    {
        $type = $this->normalize($feeType);

        return match ($type) {
            'yearly', 'membership' => $joiningDate->copy()->addYear()->subDay(),
            'one_time' => $joiningDate->copy(),
            'custom' => $customEnd ?? $joiningDate->copy()->addMonth()->subDay(),
            default => $joiningDate->copy()->addMonth()->subDay(),
        };
    }

    public function normalize(string $feeType): string
    {
        return match ($feeType) {
            'installment' => 'monthly',
            'one-time', 'onetime' => 'one_time',
            default => $feeType,
        };
    }
}
