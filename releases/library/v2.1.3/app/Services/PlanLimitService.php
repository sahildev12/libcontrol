<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Hall;
use App\Models\PlatformSetting;

class PlanLimitService
{
    public function settings(): PlatformSetting
    {
        return PlatformSetting::current();
    }

    /**
     * @return array{plan_tier: string, plan_label: string, max_seats: ?int, max_halls: ?int, max_branches: ?int}
     */
    public function limits(): array
    {
        $settings = $this->settings();
        $tier = $settings->planTier();
        $defaults = config("libcontrol.plans.{$tier}", config('libcontrol.plans.starter'));

        return [
            'plan_tier' => $tier,
            'plan_label' => $defaults['label'] ?? ucfirst($tier),
            'max_seats' => $settings->max_seats_override ?? $defaults['max_seats'] ?? null,
            'max_halls' => $settings->max_halls_override ?? $defaults['max_halls'] ?? null,
            'max_branches' => $settings->max_branches_override ?? $defaults['max_branches'] ?? null,
        ];
    }

    /**
     * @return array{seats: int, halls: int, branches: int}
     */
    public function usage(): array
    {
        return [
            'seats' => (int) Hall::query()->sum('seat_capacity'),
            'halls' => (int) Hall::query()->count(),
            'branches' => (int) Branch::query()->count(),
        ];
    }

    /**
     * @return array{limits: array<string, mixed>, usage: array<string, int>, remaining: array<string, ?int>}
     */
    public function snapshot(): array
    {
        $limits = $this->limits();
        $usage = $this->usage();

        return [
            'limits' => $limits,
            'usage' => $usage,
            'remaining' => [
                'seats' => $this->remaining($limits['max_seats'], $usage['seats']),
                'halls' => $this->remaining($limits['max_halls'], $usage['halls']),
                'branches' => $this->remaining($limits['max_branches'], $usage['branches']),
            ],
        ];
    }

    public function canAddBranch(): bool
    {
        $limits = $this->limits();
        $max = $limits['max_branches'];

        if ($max === null) {
            return true;
        }

        return $this->usage()['branches'] < $max;
    }

    public function canAddHall(): bool
    {
        $limits = $this->limits();
        $max = $limits['max_halls'];

        if ($max === null) {
            return true;
        }

        return $this->usage()['halls'] < $max;
    }

    public function maxSeatCapacityForHall(?int $hallId, int $requestedCapacity = 1): int
    {
        $limits = $this->limits();
        $maxTotal = $limits['max_seats'];

        if ($maxTotal === null) {
            return min(500, max(1, $requestedCapacity));
        }

        $currentHallCapacity = 0;

        if ($hallId) {
            $currentHallCapacity = (int) Hall::query()->whereKey($hallId)->value('seat_capacity');
        }

        $otherSeats = $this->usage()['seats'] - $currentHallCapacity;
        $remaining = max(0, $maxTotal - $otherSeats);

        return max(1, min(500, $remaining));
    }

    public function assertCanAddBranch(): void
    {
        if ($this->canAddBranch()) {
            return;
        }

        $limits = $this->limits();

        throw \Illuminate\Validation\ValidationException::withMessages([
            'name' => "Your {$limits['plan_label']} plan allows up to {$limits['max_branches']} branch(es). Upgrade to Pro or Custom for more.",
        ]);
    }

    public function assertCanAddHall(): void
    {
        if ($this->canAddHall()) {
            return;
        }

        $limits = $this->limits();

        throw \Illuminate\Validation\ValidationException::withMessages([
            'name' => "Your {$limits['plan_label']} plan allows up to {$limits['max_halls']} hall(s). Upgrade your plan for more.",
        ]);
    }

    public function assertSeatCapacity(int $seatCapacity, ?int $hallId = null): void
    {
        $limits = $this->limits();
        $maxAllowed = $this->maxSeatCapacityForHall($hallId, $seatCapacity);

        if ($seatCapacity <= $maxAllowed) {
            return;
        }

        $message = $limits['max_seats'] === null
            ? 'Seat capacity cannot exceed 500 per hall.'
            : "Your {$limits['plan_label']} plan allows {$limits['max_seats']} total seats. You can set at most {$maxAllowed} here.";

        throw \Illuminate\Validation\ValidationException::withMessages([
            'seat_capacity' => $message,
        ]);
    }

    private function remaining(?int $limit, int $used): ?int
    {
        if ($limit === null) {
            return null;
        }

        return max(0, $limit - $used);
    }
}
