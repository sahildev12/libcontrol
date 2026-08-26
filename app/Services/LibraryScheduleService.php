<?php

namespace App\Services;

use App\Models\Branch;
use Illuminate\Support\Carbon;

class LibraryScheduleService
{
    public function __construct(
        private Branch $branch,
    ) {}

    public static function forBranch(Branch $branch): self
    {
        return new self($branch);
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function defaultOptions(): array
    {
        $branch = new Branch([
            'library_open_time' => '09:00',
            'library_close_time' => '18:00',
            'is_open_24_hours' => false,
        ]);

        return (new self($branch))->timeSlotOptions();
    }

    public function is24Hours(): bool
    {
        return (bool) $this->branch->is_open_24_hours;
    }

    public function openMinutes(): int
    {
        if ($this->is24Hours()) {
            return 0;
        }

        return $this->minutesFromTime($this->branch->library_open_time ?? '09:00');
    }

    public function closeMinutes(): int
    {
        if ($this->is24Hours()) {
            return 24 * 60;
        }

        return $this->minutesFromTime($this->branch->library_close_time ?? '18:00');
    }

    /**
     * @return array{0: int, 1: int}
     */
    public function slotWindow(string $timeSlot, ?string $customStart = null, ?string $customEnd = null): array
    {
        $open = $this->openMinutes();
        $close = $this->closeMinutes();
        $mid = (int) floor(($open + $close) / 2);

        return match ($timeSlot) {
            'day_first_half' => [$open, $mid],
            'day_end_half' => [$mid, $close],
            'custom_hours' => $this->customWindow($customStart, $customEnd),
            default => [$open, $close],
        };
    }

    public function slotLabel(string $timeSlot, ?string $customStart = null, ?string $customEnd = null): string
    {
        if ($timeSlot === 'custom_hours' && $customStart === null && $customEnd === null) {
            if ($this->is24Hours()) {
                return 'Custom Hours (any time · open 24 hours)';
            }

            return sprintf(
                'Custom Hours (%s – %s)',
                $this->formatMinutes($this->openMinutes()),
                $this->formatMinutes($this->closeMinutes()),
            );
        }

        $base = match ($timeSlot) {
            'day_first_half' => 'Day First Half',
            'day_end_half' => 'Day End Half',
            'custom_hours' => 'Custom Hours',
            default => 'Full Day',
        };

        if ($this->is24Hours() && $timeSlot === 'full_day') {
            return 'Full Day (open 24 hours)';
        }

        [$start, $end] = $this->slotWindow($timeSlot, $customStart, $customEnd);

        return sprintf('%s (%s – %s)', $base, $this->formatMinutes($start), $this->formatMinutes($end));
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public function timeSlotOptions(): array
    {
        return [
            ['value' => 'full_day', 'label' => $this->slotLabel('full_day')],
            ['value' => 'custom_hours', 'label' => $this->slotLabel('custom_hours')],
        ];
    }

    public function formatMinutes(int $minutes): string
    {
        if ($minutes >= 24 * 60) {
            return '11:59 PM';
        }
        $hours = intdiv($minutes, 60) % 24;
        $mins = $minutes % 60;
        $period = $hours >= 12 ? 'PM' : 'AM';
        $displayHour = $hours % 12;

        if ($displayHour === 0) {
            $displayHour = 12;
        }

        return sprintf('%d:%02d %s', $displayHour, $mins, $period);
    }

    public function minutesFromTime(string $time): int
    {
        $time = substr($time, 0, 5);
        [$hour, $minute] = array_map('intval', explode(':', $time));

        return ($hour * 60) + $minute;
    }

    public function nowInBranchTimezone(): Carbon
    {
        return Carbon::now(config('libspace.timezone', 'Asia/Kolkata'));
    }

    public function currentMinutes(): int
    {
        $now = $this->nowInBranchTimezone();

        return ($now->hour * 60) + $now->minute;
    }

    public function windowOverlapsNow(int $startMinutes, int $endMinutes): bool
    {
        $now = $this->currentMinutes();

        return $startMinutes < $now && $endMinutes > $now;
    }

    /**
     * Custom hours are clamped to library open/close times.
     *
     * @return array{0: int, 1: int}
     */
    private function customWindow(?string $customStart, ?string $customEnd): array
    {
        return $this->clampWindow(
            $this->minutesFromTime($customStart ?? '09:00'),
            $this->minutesFromTime($customEnd ?? '18:00'),
        );
    }

    /**
     * @return array{0: int, 1: int}
     */
    private function clampWindow(int $start, int $end): array
    {
        $open = $this->openMinutes();
        $close = $this->closeMinutes();

        return [max($open, $start), min($close, max($end, $start + 1))];
    }
}
