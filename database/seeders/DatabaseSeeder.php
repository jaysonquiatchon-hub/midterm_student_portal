<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            ProgramSeeder::class,
            CourseSeeder::class,
            AdminUserSeeder::class,
            StudentDemoSeeder::class,
        ]);

        // 1. Create a default Admin user account
        User::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('password'),
                'role' => 'admin',
            ]
        );

        // 2. Create a default Student user and linked Student profile
        $studentUser = User::updateOrCreate(
            ['email' => 'student@example.com'],
            [
                'name' => 'Student User',
                'password' => Hash::make('password'),
                'role' => 'student',
            ]
        );

        Student::updateOrCreate(
            ['user_id' => $studentUser->id],
            [
                'student_number' => '2026-0001',
                'first_name' => 'Student',
                'last_name' => 'User',
                'email' => $studentUser->email,
                'birth_date' => '2000-01-01',
                'year_level' => 1,
                'program_id' => 1,
            ]
        );

        // 3. Seed random factory students
        Student::factory(30)->create()->each(function (Student $student) {
            $matchingCourses = Course::where('program_id', $student->program_id)
                ->where('year_level', '<=', $student->year_level)
                ->get();

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
