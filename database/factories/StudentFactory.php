<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Student>
 */
class StudentFactory extends Factory
{
    protected $model = Student::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'branch_id' => Branch::factory(),
            'student_code' => strtoupper(fake()->lexify('???')).'-'.fake()->unique()->numerify('###'),
            'name' => fake()->name(),
            'phone' => fake()->unique()->numerify('9#########'),
            'email' => fake()->unique()->safeEmail(),
            'status' => 'active',
            'student_type' => Student::TYPE_REGULAR,
        ];
    }
}
