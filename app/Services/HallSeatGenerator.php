<?php

namespace App\Services;

use App\Models\Hall;
use App\Models\Seat;

class HallSeatGenerator
{
    public function generate(Hall $hall, int $columns = 8): void
    {
        $this->createSeatsUpTo($hall, (int) $hall->seat_capacity, $columns);
    }

    public function appendToCapacity(Hall $hall, int $columns = 8): void
    {
        $this->createSeatsUpTo($hall, (int) $hall->seat_capacity, $columns);
    }

    private function createSeatsUpTo(Hall $hall, int $targetCapacity, int $columns): void
    {
        $existingNumbers = $hall->seats()->pluck('seat_number')->map(fn ($n) => (int) $n)->flip()->all();

        $rows = (int) ceil($targetCapacity / $columns);
        $seatNumber = 1;

        for ($row = 1; $row <= $rows; $row++) {
            for ($col = 1; $col <= $columns; $col++) {
                if ($seatNumber > $targetCapacity) {
                    break;
                }

                if (! isset($existingNumbers[$seatNumber])) {
                    Seat::create([
                        'hall_id' => $hall->id,
                        'seat_number' => (string) $seatNumber,
                        'row_number' => $row,
                        'column_number' => $col,
                    ]);
                }

                $seatNumber++;
            }
        }
    }
}
