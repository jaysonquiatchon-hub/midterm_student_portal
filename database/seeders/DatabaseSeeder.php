<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Student;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(ProgramSeeder::class);
        $this->call(CourseSeeder::class);

        // Seed Students and automatically enroll them in courses matching their program and year level
        Student::factory(30)->create()->each(function (Student $student) {
            // Find courses that belong to the student's program and year level
            $matchingCourses = Course::where('program', $student->program)
                ->where('year_level', '<=', $student->year_level)
                ->get();

            // If matching courses exist, attach up to 3 random courses
            if ($matchingCourses->isNotEmpty()) {
                $countToPick = min(3, $matchingCourses->count());
                $picked = $matchingCourses->random($countToPick);

                foreach ($picked as $course) {
                    $student->courses()->attach($course->id, [
                        'grade' => fake()->randomElement(['1.00', '1.25', '1.50', '1.75', '2.00', '2.25', '2.50', '3.00', null]),
                    ]);
                }
            }
        });
    }
}