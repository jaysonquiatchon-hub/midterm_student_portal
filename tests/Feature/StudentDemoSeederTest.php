<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Student;
use Database\Seeders\StudentDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentDemoSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_seeder_creates_twenty_students_and_is_safe_to_run_again(): void
    {
        $this->seed(StudentDemoSeeder::class);
        $this->seed(StudentDemoSeeder::class);

        $this->assertDatabaseCount('programs', 5);
        $this->assertDatabaseCount('students', 20);
        $this->assertGreaterThan(0, Course::count());
        $this->assertDatabaseHas('students', [
            'student_number' => '2026-90001',
            'first_name' => 'Chloe',
            'last_name' => 'Bartoletti',
            'email' => 'student2026-90001@example.test',
        ]);

        $this->withSession(['portal_access' => true])
            ->get(route('students.index'))
            ->assertOk()
            ->assertViewHas(
                'students',
                fn ($students): bool => $students->count() === 10 && $students->total() === 20,
            );

        $student = Student::where('student_number', '2026-90001')->firstOrFail();
        $ownProgramCourse = Course::where('program_id', $student->program_id)->firstOrFail();
        $otherProgramCourse = Course::where('program_id', '!=', $student->program_id)->firstOrFail();

        $this->withSession(['portal_access' => true])
            ->get(route('students.show', $student))
            ->assertOk()
            ->assertSee($ownProgramCourse->code)
            ->assertDontSee($otherProgramCourse->code);
    }
}
