<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Seat;
use App\Models\SeatBooking;
use App\Models\Student;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class SeatStatusService
{
    public const EXPIRING_SOON_DAYS = 7;

    public function __construct(
        private ?SeatAvailabilityService $availability = null,
    ) {}

    public function resolveForSeat(Seat $seat, ?Branch $branch = null, ?Carbon $moment = null): string
    {
        $moment ??= Carbon::now(config('libspace.timezone', 'Asia/Kolkata'));
        $today = $moment->copy()->startOfDay();
        $booking = $this->displayBookingForSeat($seat);

        if (! $booking) {
            return 'available';
        }

        if ($booking->plan_expiry_date->lt($today) && $booking->status !== 'on_trial') {
            return 'expired';
        }

        if ($booking->plan_expiry_date->lte($today->copy()->addDays(self::EXPIRING_SOON_DAYS))) {
            return 'expiring_soon';
        }

        if ($branch) {
            $availability = $this->availability ?? SeatAvailabilityService::forBranch($branch);

            if ($availability->isTrialAt($seat, $moment)) {
                return 'on_trial';
            }

            if ($availability->isOccupiedAt($seat, $moment)) {
                return 'occupied';
            }

            return 'available';
        }

        if ($booking->status === 'on_trial' || ($booking->trial_end && $booking->trial_end->gte($today))) {
            return 'on_trial';
        }

        return 'occupied';
    }

    public function statusLabel(string $status): string
    {
        return match ($status) {
            'available' => 'Vacant',
            default => ucwords(str_replace('_', ' ', $status)),
        };
    }

    public function displayBookingForSeat(Seat $seat): ?SeatBooking
    {
        return $seat->bookings
            ->filter(fn (SeatBooking $booking) => $booking->cancelled_at === null && $booking->status !== 'cancelled')
            ->sortByDesc('joining_date')
            ->first();
    }

    public function activeBookingForSeat(Seat $seat, ?Carbon $today = null): ?SeatBooking
    {
        $today ??= Carbon::today();
        $booking = $this->displayBookingForSeat($seat);

        if (! $booking || ! $this->bookingIsActive($booking, $today)) {
            return null;
        }

        return $booking;
    }

    public function hasActiveRegularAssignment(Seat $seat, ?Carbon $moment = null): bool
    {
        $today = ($moment ?? Carbon::now(config('libspace.timezone', 'Asia/Kolkata')))->copy()->startOfDay();

        return $seat->bookings->contains(function (SeatBooking $booking) use ($today) {
            if ($booking->cancelled_at !== null || $booking->status === 'cancelled') {
                return false;
            }

            if ($booking->status === 'on_trial' || $booking->trial_end) {
                return false;
            }

            if ($booking->plan_expiry_date->lt($today)) {
                return false;
            }

            if ($booking->student?->student_type === Student::TYPE_TRIAL) {
                return false;
            }

            return $booking->plan_expiry_date->gte($today);
        });
    }

    public function bookingIsActive(SeatBooking $booking, ?Carbon $today = null): bool
    {
        $today ??= Carbon::today();

        if ($booking->cancelled_at !== null || $booking->status === 'cancelled') {
            return false;
        }

        if ($booking->status === 'on_trial' || $booking->trial_end) {
            return $booking->trial_end && $booking->trial_end->gte($today);
        }

        return $booking->plan_expiry_date->gte($today);
    }

    /**
     * @param  Collection<int, Seat>  $seats
     * @return list<array<string, mixed>>
     */
    public function mapSeatsForBranch(Collection $seats, ?Branch $branch = null, ?Carbon $moment = null): array
    {
        $moment ??= Carbon::now(config('libspace.timezone', 'Asia/Kolkata'));
        $availability = $branch ? ($this->availability ?? SeatAvailabilityService::forBranch($branch)) : null;
        $schedule = $branch ? LibraryScheduleService::forBranch($branch) : null;

        return $seats->map(function (Seat $seat) use ($branch, $moment, $availability, $schedule) {
            $status = $this->resolveForSeat($seat, $branch, $moment);
            $booking = $this->displayBookingForSeat($seat);
            $student = $booking?->student;

            $payload = [
                'id' => $seat->id,
                'hall_id' => $seat->hall_id,
                'hall_name' => $seat->hall->name,
                'seat_number' => $seat->seat_number,
                'row_number' => $seat->row_number,
                'column_number' => $seat->column_number,
                'status' => $status,
                'status_label' => $this->statusLabel($status),
                'student_code' => $student?->student_code,
                'student_name' => $student?->name,
                'student_initial' => $student ? strtoupper(substr($student->name, 0, 1)) : null,
                'booking_id' => $booking?->id,
                'time_slot' => $booking?->time_slot,
                'time_slot_label' => $booking && $schedule
                    ? $schedule->slotLabel(
                        $booking->time_slot,
                        $booking->custom_start_time ? substr((string) $booking->custom_start_time, 0, 5) : null,
                        $booking->custom_end_time ? substr((string) $booking->custom_end_time, 0, 5) : null,
                    )
                    : null,
                'plan_expiry_date' => $booking?->plan_expiry_date?->toDateString(),
                'student_type' => $student?->student_type ?: Student::TYPE_REGULAR,
                'has_regular_assignment' => $this->hasActiveRegularAssignment($seat, $moment),
            ];

            if ($availability) {
                $payload['today_windows'] = $availability->availabilityTimeline($seat, $moment->copy()->startOfDay());
            }

            return $payload;
        })->values()->all();
    }
}
