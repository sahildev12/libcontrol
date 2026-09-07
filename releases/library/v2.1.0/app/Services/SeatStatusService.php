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

    public function isTrialBooking(SeatBooking $booking): bool
    {
        if ($booking->status === 'on_trial' || $booking->trial_end !== null) {
            return true;
        }

        return $booking->student?->student_type === Student::TYPE_TRIAL;
    }

    public function resolveForSeat(Seat $seat, ?Branch $branch = null, ?Carbon $moment = null): string
    {
        $moment ??= Carbon::now(config('libcontrol.timezone', 'Asia/Kolkata'));
        $today = $moment->copy()->startOfDay();
        $booking = $this->displayBookingForSeat($seat, $today);

        if (! $booking) {
            return 'available';
        }

        if ($this->isRecentlyExpired($booking, $today)) {
            return 'expired';
        }

        if ($this->isTrialBooking($booking)) {
            if ($branch) {
                $availability = $this->availability ?? SeatAvailabilityService::forBranch($branch);

                if ($availability->isTrialAt($seat, $moment) || $availability->isOccupiedAt($seat, $moment)) {
                    return 'on_trial';
                }

                return 'available';
            }

            return 'on_trial';
        }

        if ($booking->plan_expiry_date->lte($today->copy()->addDays(self::EXPIRING_SOON_DAYS))) {
            return 'expiring_soon';
        }

        if ($branch) {
            $availability = $this->availability ?? SeatAvailabilityService::forBranch($branch);

            if ($availability->isOccupiedAt($seat, $moment)) {
                return 'occupied';
            }

            return 'available';
        }

        return 'occupied';
    }

    public function statusLabel(string $status): string
    {
        return match ($status) {
            'available' => 'Vacant',
            'occupied' => 'Occupied (Full Day)',
            'occupied_custom' => 'Occupied (Custom Hours)',
            'expiring_soon' => 'Expiring Soon',
            'on_trial' => 'Trial',
            default => ucwords(str_replace('_', ' ', $status)),
        };
    }

    public function displayBookingForSeat(Seat $seat, ?Carbon $today = null): ?SeatBooking
    {
        $today ??= Carbon::now(config('libcontrol.timezone', 'Asia/Kolkata'))->copy()->startOfDay();

        return $seat->bookings
            ->filter(fn (SeatBooking $booking) => $booking->cancelled_at === null && $booking->status !== 'cancelled')
            ->filter(fn (SeatBooking $booking) => ! $this->isStaleExpired($booking, $today))
            ->sortByDesc('joining_date')
            ->first();
    }

    public function bookingExpiryDate(SeatBooking $booking): Carbon
    {
        $expiry = $this->isTrialBooking($booking)
            ? ($booking->trial_end ?? $booking->plan_expiry_date)
            : $booking->plan_expiry_date;

        return $expiry->copy()->startOfDay();
    }

    public function isRecentlyExpired(SeatBooking $booking, ?Carbon $today = null): bool
    {
        $today ??= Carbon::now(config('libcontrol.timezone', 'Asia/Kolkata'))->copy()->startOfDay();
        $expiry = $this->bookingExpiryDate($booking);

        return $expiry->lt($today) && $expiry->gte($today->copy()->subDay());
    }

    public function isStaleExpired(SeatBooking $booking, ?Carbon $today = null): bool
    {
        $today ??= Carbon::now(config('libcontrol.timezone', 'Asia/Kolkata'))->copy()->startOfDay();

        return $this->bookingExpiryDate($booking)->lt($today->copy()->subDay());
    }

    public function visibleOnTrialMap(array $seat): bool
    {
        if (! empty($seat['has_regular_assignment'])) {
            return false;
        }

        if (($seat['status'] ?? '') === 'expired') {
            return ! empty($seat['expired_from_trial']);
        }

        return true;
    }

    public function visibleOnRegularMap(array $seat): bool
    {
        if (($seat['status'] ?? '') === 'expired' && ! empty($seat['expired_from_trial'])) {
            return false;
        }

        return true;
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
        $today = ($moment ?? Carbon::now(config('libcontrol.timezone', 'Asia/Kolkata')))->copy()->startOfDay();

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

        if ($booking->status === 'on_trial' || $booking->trial_end || $booking->student?->student_type === Student::TYPE_TRIAL) {
            $end = $booking->trial_end ?? $booking->plan_expiry_date;

            return $end && $end->gte($today);
        }

        return $booking->plan_expiry_date->gte($today);
    }

    /**
     * @param  Collection<int, Seat>  $seats
     * @return list<array<string, mixed>>
     */
    public function mapSeatsForBranch(Collection $seats, ?Branch $branch = null, ?Carbon $moment = null): array
    {
        $moment ??= Carbon::now(config('libcontrol.timezone', 'Asia/Kolkata'));
        $availability = $branch ? ($this->availability ?? SeatAvailabilityService::forBranch($branch)) : null;
        $schedule = $branch ? LibraryScheduleService::forBranch($branch) : null;

        return $seats->map(function (Seat $seat) use ($branch, $moment, $availability, $schedule) {
            $seatBranch = $seat->hall?->branch ?? $branch;
            $seatAvailability = $seatBranch
                ? SeatAvailabilityService::forBranch($seatBranch)
                : $availability;
            $seatSchedule = $seatBranch
                ? LibraryScheduleService::forBranch($seatBranch)
                : $schedule;
            $status = $this->resolveForSeat($seat, $seatBranch, $moment);
            $booking = $this->displayBookingForSeat($seat, $moment->copy()->startOfDay());
            $student = $booking?->student;

            $payload = [
                'id' => $seat->id,
                'hall_id' => $seat->hall_id,
                'branch_id' => $seat->hall?->branch_id,
                'hall_name' => $seat->hall?->name,
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
                'time_slot_label' => $booking && $seatSchedule
                    ? $seatSchedule->slotLabel(
                        $booking->time_slot,
                        $booking->custom_start_time ? substr((string) $booking->custom_start_time, 0, 5) : null,
                        $booking->custom_end_time ? substr((string) $booking->custom_end_time, 0, 5) : null,
                    )
                    : null,
                'plan_expiry_date' => $booking?->plan_expiry_date?->toDateString(),
                'student_type' => $student?->student_type ?: Student::TYPE_REGULAR,
                'has_regular_assignment' => $this->hasActiveRegularAssignment($seat, $moment),
                'expired_from_trial' => $status === 'expired' && $booking && $this->isTrialBooking($booking),
                'library_open_time' => $seatBranch?->is_open_24_hours
                    ? '00:00'
                    : substr((string) ($seatBranch?->library_open_time ?? '09:00'), 0, 5),
                'library_close_time' => $seatBranch?->is_open_24_hours
                    ? '23:59'
                    : substr((string) ($seatBranch?->library_close_time ?? '18:00'), 0, 5),
                'is_open_24_hours' => (bool) ($seatBranch?->is_open_24_hours),
            ];

            if ($seatAvailability) {
                $payload['today_windows'] = $seatAvailability->availabilityTimeline($seat, $moment->copy()->startOfDay());
            }

            return $payload;
        })->values()->all();
    }
}
