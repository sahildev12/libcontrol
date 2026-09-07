<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\Hall;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Hall>
 */
class HallFactory extends Factory
{
    protected $model = Hall::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'branch_id' => Branch::factory(),
            'name' => fake()->words(2, true).' Hall',
            'description' => fake()->sentence(),
            'seat_capacity' => fake()->numberBetween(10, 40),
        ];
    }
}
