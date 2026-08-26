<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Seat;
use App\Models\SeatBooking;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class SeatAvailabilityService
{
    public function __construct(
        private LibraryScheduleService $schedule,
        private SeatConflictService $conflictService,
    ) {}

    public static function forBranch(Branch $branch): self
    {
        return new self(
            LibraryScheduleService::forBranch($branch),
            SeatConflictService::forBranch($branch),
        );
    }

    public function isOccupiedAt(Seat $seat, ?Carbon $moment = null): bool
    {
        $moment ??= $this->schedule->nowInBranchTimezone();
        $date = $moment->copy()->startOfDay();
        $currentMinutes = ($moment->hour * 60) + $moment->minute;

        foreach ($this->activeBookingsForSeat($seat, $date) as $booking) {
            if (! $this->bookingCoversDate($booking, $date)) {
                continue;
            }

            [$start, $end] = $this->conflictService->windowForBooking($booking);

            if ($start <= $currentMinutes && $end > $currentMinutes) {
                return true;
            }
        }

        return false;
    }

    public function isTrialAt(Seat $seat, ?Carbon $moment = null): bool
    {
        $moment ??= $this->schedule->nowInBranchTimezone();
        $date = $moment->copy()->startOfDay();

        foreach ($this->activeBookingsForSeat($seat, $date) as $booking) {
            $isTrial = $booking->status === 'on_trial'
                || $booking->trial_end
                || $booking->student?->student_type === \App\Models\Student::TYPE_TRIAL;

            if (! $isTrial) {
                continue;
            }

            if (! $this->bookingCoversDate($booking, $date)) {
                continue;
            }

            if ($booking->trial_end && $booking->trial_end->lt($date)) {
                continue;
            }

            [$start, $end] = $this->conflictService->windowForBooking($booking);
            $currentMinutes = ($moment->hour * 60) + $moment->minute;

            if ($start <= $currentMinutes && $end > $currentMinutes) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<array{from: string, to: string, start_minutes: int, end_minutes: int, type: string, label: string}>
     */
    public function availabilityTimeline(Seat $seat, ?Carbon $date = null): array
    {
        $date ??= $this->schedule->nowInBranchTimezone()->copy()->startOfDay();
        $open = $this->schedule->openMinutes();
        $close = $this->schedule->closeMinutes();
        $segments = [];

        $bookedWindows = [];
        foreach ($this->activeBookingsForSeat($seat, $date) as $booking) {
            if (! $this->bookingCoversDate($booking, $date)) {
                continue;
            }

            [$start, $end] = $this->conflictService->windowForBooking($booking);
            $isTrial = $booking->status === 'on_trial'
                || $booking->trial_end
                || $booking->student?->student_type === \App\Models\Student::TYPE_TRIAL;
            $bookedWindows[] = [
                'start' => max($open, $start),
                'end' => min($close, $end),
                'type' => $isTrial ? 'trial' : 'booked',
                'label' => $booking->student?->name ?? 'Booked',
                'time_slot' => $booking->time_slot,
                'booking_id' => $booking->id,
                'student_id' => $booking->student_id,
                'student_name' => $booking->student?->name,
                'student_code' => $booking->student?->student_code,
                'student_type' => $isTrial ? 'trial' : 'regular',
                'student_initial' => $booking->student
                    ? strtoupper(substr((string) $booking->student->name, 0, 1))
                    : null,
                'joining_date' => $booking->joining_date?->toDateString(),
                'plan_expiry_date' => $booking->plan_expiry_date?->toDateString(),
                'trial_end' => $booking->trial_end?->toDateString(),
            ];
        }

        usort($bookedWindows, fn ($a, $b) => $a['start'] <=> $b['start']);

        $cursor = $open;
        foreach ($bookedWindows as $window) {
            if ($window['start'] > $cursor) {
                $segments[] = $this->segment($cursor, $window['start'], 'free', 'Vacant');
            }

            $segments[] = $this->segment(
                $window['start'],
                $window['end'],
                $window['type'] === 'trial' ? 'trial' : 'booked',
                $window['label'],
                $window['time_slot'] ?? null,
                [
                    'booking_id' => $window['booking_id'] ?? null,
                    'student_id' => $window['student_id'] ?? null,
                    'student_name' => $window['student_name'] ?? null,
                    'student_code' => $window['student_code'] ?? null,
                    'student_type' => $window['student_type'] ?? null,
                    'student_initial' => $window['student_initial'] ?? null,
                    'joining_date' => $window['joining_date'] ?? null,
                    'plan_expiry_date' => $window['plan_expiry_date'] ?? null,
                    'trial_end' => $window['trial_end'] ?? null,
                ],
            );
            $cursor = max($cursor, $window['end']);
        }

        if ($cursor < $close) {
            $segments[] = $this->segment($cursor, $close, 'free', 'Vacant');
        }

        return $segments;
    }

    public function freeWindowsLabel(Seat $seat, ?Carbon $date = null): string
    {
        $free = array_values(array_filter(
            $this->availabilityTimeline($seat, $date),
            fn (array $segment) => $segment['type'] === 'free',
        ));

        if ($free === []) {
            return 'No free hours today';
        }

        return implode(', ', array_map(
            fn (array $segment) => $segment['from'].' – '.$segment['to'],
            $free,
        ));
    }

    /**
     * @return Collection<int, SeatBooking>
     */
    private function activeBookingsForSeat(Seat $seat, Carbon $date): Collection
    {
        return $seat->bookings
            ->filter(function (SeatBooking $booking) use ($date) {
                if ($booking->cancelled_at !== null || $booking->status === 'cancelled') {
                    return false;
                }

                if ($booking->plan_expiry_date->lt($date) && $booking->status !== 'on_trial') {
                    return false;
                }

                if ($booking->trial_end && $booking->trial_end->lt($date)) {
                    return false;
                }

                return true;
            })
            ->values();
    }

    private function bookingCoversDate(SeatBooking $booking, Carbon $date): bool
    {
        return $date->gte($booking->joining_date) && $date->lte($booking->plan_expiry_date);
    }

    /**
     * @param  array<string, mixed>  $meta
     * @return array<string, mixed>
     */
    private function segment(int $start, int $end, string $type, string $label, ?string $timeSlot = null, array $meta = []): array
    {
        return [
            'from' => $this->schedule->formatMinutes($start),
            'to' => $this->schedule->formatMinutes($end),
            'start_minutes' => $start,
            'end_minutes' => $end,
            'type' => $type,
            'label' => $label,
            'time_slot' => $timeSlot,
            'booking_id' => $meta['booking_id'] ?? null,
            'student_id' => $meta['student_id'] ?? null,
            'student_name' => $meta['student_name'] ?? null,
            'student_code' => $meta['student_code'] ?? null,
            'student_type' => $meta['student_type'] ?? null,
            'student_initial' => $meta['student_initial'] ?? null,
            'joining_date' => $meta['joining_date'] ?? null,
            'plan_expiry_date' => $meta['plan_expiry_date'] ?? null,
            'trial_end' => $meta['trial_end'] ?? null,
        ];
    }
}
