<?php

namespace Database\Factories;

use App\Models\Hall;
use App\Models\Seat;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Seat>
 */
class SeatFactory extends Factory
{
    protected $model = Seat::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'hall_id' => Hall::factory(),
            'seat_number' => (string) fake()->unique()->numberBetween(1, 100),
            'row_number' => 1,
            'column_number' => 1,
        ];
    }
}
