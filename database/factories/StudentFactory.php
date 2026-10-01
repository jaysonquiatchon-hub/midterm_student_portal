<?php

namespace Database\Factories;

use App\Models\Program; // <-- Add this import statement!
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Student>
 */
class StudentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'student_number' => $this->faker->unique()->numerify('2026-#####'),
            'first_name'     => $this->faker->firstName(),
            'last_name'      => $this->faker->lastName(),
            'email'          => $this->faker->unique()->safeEmail(),
            'birth_date'     => $this->faker->date('Y-m-d', '-18 years'),
            'year_level'     => $this->faker->numberBetween(1, 4),
            'program_id'     => Program::inRandomOrder()->value('id') ?? Program::factory(),
        ];
    }
}