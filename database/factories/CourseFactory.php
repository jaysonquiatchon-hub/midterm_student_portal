<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\Program;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Course>
 */
class CourseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code'       => strtoupper(fake()->unique()->bothify('??###')),
            'title'      => fake()->sentence(3),
            'units'      => fake()->randomElement([2, 3]),
            'year_level' => fake()->numberBetween(1, 4),
            'program_id' => Program::inRandomOrder()->value('id') ?? Program::factory(),
        ];
    }
}