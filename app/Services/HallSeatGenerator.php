<?php

namespace App\Services;

use App\Models\Hall;
use App\Models\Seat;

class HallSeatGenerator
{
    public function generate(Hall $hall, int $columns = 8, int $startFrom = 1): void
    {
        $this->createSeatsUpTo($hall, (int) $hall->seat_capacity, $columns, $startFrom);
    }

    public function nextSeatNumberAfterHall(int $hallId): int
    {
        $max = $this->maxSeatNumberInHall($hallId);

        return $max > 0 ? $max + 1 : 1;
    }

    public function appendToCapacity(Hall $hall, int $columns = 8): void
    {
        $this->syncToCapacity($hall, $columns);
    }

    public function syncToCapacity(Hall $hall, int $columns = 8): void
    {
        $targetCapacity = (int) $hall->seat_capacity;
        $currentCount = $hall->seats()->count();

        if ($currentCount > $targetCapacity) {
            $this->removeVacantSeatsDownTo($hall, $targetCapacity);

            return;
        }

        if ($currentCount < $targetCapacity) {
            $this->addSeatsUntil($hall, $targetCapacity, $columns);
        }
    }

    private function removeVacantSeatsDownTo(Hall $hall, int $targetCapacity): void
    {
        $currentCount = $hall->seats()->count();

        if ($currentCount <= $targetCapacity) {
            return;
        }

        $toRemove = $currentCount - $targetCapacity;

        $vacantSeatIds = $hall->seats()
            ->whereDoesntHave(
                'bookings',
                fn ($query) => $query
                    ->whereNull('cancelled_at')
                    ->where('status', '!=', 'cancelled'),
            )
            ->orderByRaw('CAST(seat_number AS UNSIGNED) DESC')
            ->orderByDesc('id')
            ->limit($toRemove)
            ->pluck('id');

        if ($vacantSeatIds->count() < $toRemove) {
            return;
        }

        Seat::query()->whereIn('id', $vacantSeatIds)->delete();
    }

    private function addSeatsUntil(Hall $hall, int $targetCapacity, int $columns): void
    {
        $count = $hall->seats()->count();
        $nextNumber = ((int) $hall->seats()->selectRaw('MAX(CAST(seat_number AS UNSIGNED)) as max_num')->value('max_num')) ?: 0;

        while ($count < $targetCapacity) {
            $nextNumber++;
            $row = (int) ceil($nextNumber / $columns);
            $col = (($nextNumber - 1) % $columns) + 1;

            if ($hall->seats()->where('seat_number', (string) $nextNumber)->exists()) {
                continue;
            }

            Seat::create([
                'hall_id' => $hall->id,
                'seat_number' => (string) $nextNumber,
                'row_number' => $row,
                'column_number' => $col,
            ]);

            $count++;
        }
    }

    private function createSeatsUpTo(Hall $hall, int $targetCapacity, int $columns, int $startFrom = 1): void
    {
        $existingNumbers = $hall->seats()
            ->pluck('seat_number')
            ->mapWithKeys(fn ($number) => [(int) $number => true])
            ->all();

        for ($position = 0; $position < $targetCapacity; $position++) {
            $seatNumber = $startFrom + $position;

            if (isset($existingNumbers[$seatNumber])) {
                continue;
            }

            $row = (int) floor($position / $columns) + 1;
            $col = ($position % $columns) + 1;

            Seat::create([
                'hall_id' => $hall->id,
                'seat_number' => (string) $seatNumber,
                'row_number' => $row,
                'column_number' => $col,
            ]);
        }
    }

    private function maxSeatNumberInHall(int $hallId): int
    {
        return (int) (Seat::query()
            ->where('hall_id', $hallId)
            ->selectRaw('MAX(CAST(seat_number AS UNSIGNED)) as max_num')
            ->value('max_num') ?: 0);
    }
}
