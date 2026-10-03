<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Department;
use App\Models\EnrollmentApplication;
use App\Models\Program;
use App\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentPortalTest extends TestCase
{
    use RefreshDatabase;

    public function test_portal_login_and_logout_set_and_clear_session_access(): void
    {
        $this->post(route('portal.login.submit'), [
            'username' => 'admin',
            'password' => 'password123',
        ])
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('portal_access', true);

        $this->post(route('portal.logout'))
            ->assertRedirect(route('portal.login'))
            ->assertSessionMissing('portal_access');
    }

    public function test_navigation_places_course_and_student_links_next_to_the_portal_title(): void
    {
        $this->withSession(['portal_access' => true])
            ->get(route('courses.index'))
            ->assertSeeInOrder([
                'Student Portal</a>',
                'class="portal-nav-link"',
                '>Dashboard</a>',
                'class="portal-nav-link active"',
                '>Courses</a>',
                'class="portal-nav-link"',
                '>Students</a>',
                '>Logout</button>',
            ], false);

        $this->withSession(['portal_access' => true])
            ->get(route('students.index'))
            ->assertSeeInOrder([
                'class="portal-nav-link"',
                '>Courses</a>',
                'class="portal-nav-link active"',
                '>Students</a>',
            ], false);
    }

    public function test_guest_is_redirected_from_dashboard_to_portal_login(): void
    {
        $this->get(route('dashboard'))
            ->assertRedirect(route('portal.login'));
    }

    public function test_dashboard_displays_student_course_program_and_pending_application_totals(): void
    {
        $program = $this->createProgram('BSIT');
        $otherProgram = $this->createProgram('BSCS');
        $student = $this->createStudent($program);
        $this->createCourse($program, 'IT101');
        $this->createCourse($otherProgram, 'CS101', ['status' => 'archived']);
        $department = Department::create(['code' => 'CIT', 'name' => 'College of Information Technology']);

        foreach (['pending', 'under_review'] as $status) {
            EnrollmentApplication::create([
                'application_number' => 'APP-'.strtoupper($status),
                'first_name' => 'Alex',
                'last_name' => 'Applicant',
                'birth_date' => '2005-05-10',
                'gender' => 'Prefer not to say',
                'email' => "{$status}@example.test",
                'contact_number' => '09123456789',
                'barangay' => 'Sample Barangay',
                'city' => 'Sample City',
                'province' => 'Sample Province',
                'department_id' => $department->id,
                'program_id' => $program->id,
                'student_type' => 'new',
                'year_level' => 1,
                'school_year' => '2026-2027',
                'semester' => '1st',
                'status' => $status,
            ]);
        }

        $this->withSession(['portal_access' => true]);

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Total Students')
            ->assertSee('Active Courses')
            ->assertSee('Programs')
            ->assertSee('Pending Applications')
            ->assertSee('Students by Program')
            ->assertSee('Recently Added Students')
            ->assertSee($student->full_name)
            ->assertSee($student->student_number)
            ->assertViewHas('studentCount', 1)
            ->assertViewHas('activeCourseCount', 1)
            ->assertViewHas('programCount', 2)
            ->assertViewHas('pendingApplicationCount', 1)
            ->assertViewHas('programStats', fn ($stats): bool => $stats->first()['student_count'] === 1)
            ->assertViewHas('recentStudents', fn ($students): bool => $students->contains('id', $student->id));
    }

    public function test_guest_cannot_update_a_student_grade(): void
    {
        $program = $this->createProgram('BSIT');
        $student = $this->createStudent($program);
        $course = $this->createCourse($program, 'IT101');
        $student->courses()->attach($course->id);

        $this->post(route('students.courses.update-grade', [$student, $course]), [
            'grade' => '1.25',
        ])->assertRedirect(route('portal.login'));

        $this->assertDatabaseHas('course_student', [
            'student_id' => $student->id,
            'course_id' => $course->id,
            'grade' => null,
        ]);
    }

    public function test_course_offerings_are_public_and_show_active_courses(): void
    {
        $program = $this->createProgram('BSIT');
        $activeCourse = $this->createCourse($program, 'IT101');
        $archivedCourse = $this->createCourse($program, 'IT102', ['status' => 'archived']);

        $this->get(route('courses.index'))
            ->assertOk()
            ->assertSee($activeCourse->title)
            ->assertDontSee($archivedCourse->title);
    }

    public function test_portal_user_can_create_update_and_delete_a_student(): void
    {
        $program = $this->createProgram('BSIT');
        $this->withSession(['portal_access' => true]);

        $this->post(route('students.store'), [
            'student_number' => 'STU-2026-001',
            'first_name' => 'Casey',
            'last_name' => 'Student',
            'email' => 'casey@example.test',
            'birth_date' => '2005-04-10',
            'year_level' => 1,
            'program_id' => $program->id,
        ])->assertRedirect(route('students.index'));

        $student = Student::where('student_number', 'STU-2026-001')->firstOrFail();

        $this->put(route('students.update', $student), [
            'student_number' => 'STU-2026-001',
            'first_name' => 'Casey',
            'last_name' => 'Updated',
            'email' => 'casey@example.test',
            'birth_date' => '2005-04-10',
            'year_level' => 2,
            'program_id' => $program->id,
        ])->assertRedirect(route('students.index'));

        $this->assertDatabaseHas('students', [
            'id' => $student->id,
            'last_name' => 'Updated',
            'year_level' => 2,
        ]);

        $this->delete(route('students.destroy', $student))
            ->assertRedirect(route('students.index'));

        $this->assertDatabaseMissing('students', ['id' => $student->id]);
    }

    public function test_student_directory_shows_profile_link_only_on_the_view_button(): void
    {
        $program = $this->createProgram('BSIT');
        $student = $this->createStudent($program);
        $this->withSession(['portal_access' => true]);

        $this->get(route('students.index'))
            ->assertOk()
            ->assertSeeText($student->full_name)
            ->assertSee('>View</a>', false)
            ->assertSee('>Edit</a>', false)
            ->assertSee(route('students.show', $student), false)
            ->assertSee(route('students.edit', $student), false)
            ->assertDontSee('<a href="'.route('students.show', $student).'">'.$student->full_name.'</a>', false);
    }

    public function test_success_flash_message_renders_as_a_bottom_right_toast_that_hides_after_two_seconds(): void
    {
        $this->withSession(['success' => 'Student updated successfully.'])
            ->get(route('courses.index'))
            ->assertSee('id="success-toast"', false)
            ->assertSee('class="toast-container position-fixed bottom-0 end-0 p-3"', false)
            ->assertSee('data-bs-autohide="true"', false)
            ->assertSee('data-bs-delay="2000"', false)
            ->assertSeeText('Student updated successfully.');
    }

    public function test_student_can_only_enroll_once_in_an_active_course_from_their_program(): void
    {
        $program = $this->createProgram('BSIT');
        $otherProgram = $this->createProgram('BSCS');
        $student = $this->createStudent($program);
        $course = $this->createCourse($program, 'IT101');
        $availableCourse = $this->createCourse($program, 'IT102');
        $archivedCourse = $this->createCourse($program, 'IT103', ['status' => 'archived']);
        $otherProgramCourse = $this->createCourse($otherProgram, 'CS101');
        $this->withSession(['portal_access' => true]);

        $this->from(route('students.show', $student))
            ->post(route('students.enroll', $student), ['course_id' => $otherProgramCourse->id])
            ->assertSessionHasErrors('course_id');

        $this->from(route('students.show', $student))
            ->post(route('students.enroll', $student), ['course_id' => $archivedCourse->id])
            ->assertSessionHasErrors('course_id');

        $this->from(route('students.show', $student))
            ->post(route('students.enroll', $student), ['course_id' => $course->id])
            ->assertRedirect(route('students.show', $student));

        $this->assertDatabaseHas('course_student', [
            'student_id' => $student->id,
            'course_id' => $course->id,
        ]);

        $response = $this->get(route('students.show', $student))->assertOk();
        $matchedSelect = preg_match(
            '/<select[^>]*id="course_id"[^>]*>(.*?)<\/select>/s',
            $response->getContent(),
            $select,
        );
        $this->assertSame(1, $matchedSelect);
        $this->assertStringContainsString($availableCourse->code, $select[1]);
        $this->assertStringNotContainsString($course->code, $select[1]);
        $this->assertStringNotContainsString($archivedCourse->code, $select[1]);
        $this->assertStringNotContainsString($otherProgramCourse->code, $select[1]);

        $this->from(route('students.show', $student))
            ->post(route('students.enroll', $student), ['course_id' => $course->id])
            ->assertSessionHasErrors('course_id');

        $this->assertDatabaseCount('course_student', 1);
    }

    public function test_grade_can_only_be_set_for_an_enrolled_course_and_must_match_the_decimal_column(): void
    {
        $program = $this->createProgram('BSIT');
        $student = $this->createStudent($program);
        $enrolledCourse = $this->createCourse($program, 'IT101');
        $otherCourse = $this->createCourse($program, 'IT102');
        $student->courses()->attach($enrolledCourse->id);
        $this->withSession(['portal_access' => true]);

        $this->from(route('students.show', $student))
            ->post(route('students.courses.update-grade', [$student, $enrolledCourse]), [
                'grade' => 'Incomplete',
            ])
            ->assertSessionHasErrors('grade');

        $this->post(route('students.courses.update-grade', [$student, $enrolledCourse]), [
            'grade' => '1.25',
        ])->assertRedirect();

        $this->assertDatabaseHas('course_student', [
            'student_id' => $student->id,
            'course_id' => $enrolledCourse->id,
            'grade' => '1.25',
        ]);

        $this->post(route('students.courses.update-grade', [$student, $otherCourse]), [
            'grade' => '1.50',
        ])->assertNotFound();
    }

    public function test_student_number_and_email_must_be_unique(): void
    {
        $program = $this->createProgram('BSIT');
        $student = $this->createStudent($program);
        $this->withSession(['portal_access' => true]);

        $this->from(route('students.create'))
            ->post(route('students.store'), [
                'student_number' => $student->student_number,
                'first_name' => 'Another',
                'last_name' => 'Student',
                'email' => $student->email,
                'birth_date' => '2005-04-10',
                'year_level' => 1,
                'program_id' => $program->id,
            ])
            ->assertSessionHasErrors(['student_number', 'email']);

        $this->assertDatabaseCount('students', 1);
    }

    private function createProgram(string $code): Program
    {
        return Program::create([
            'code' => $code,
            'name' => "{$code} Program",
        ]);
    }

    private function createStudent(Program $program): Student
    {
        return Student::create([
            'student_number' => 'STU-'.fake()->unique()->numerify('#####'),
            'first_name' => 'Taylor',
            'last_name' => 'Student',
            'email' => fake()->unique()->safeEmail(),
            'birth_date' => '2005-04-10',
            'year_level' => 1,
            'program_id' => $program->id,
        ]);
    }

    private function createCourse(Program $program, string $code, array $attributes = []): Course
    {
        return Course::create(array_merge([
            'code' => $code,
            'title' => "{$code} Course",
            'units' => 3,
            'year_level' => 1,
            'program_id' => $program->id,
        ], $attributes));
    }
}
