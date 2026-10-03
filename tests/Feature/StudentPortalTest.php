<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\EnrollmentApplication;
use App\Models\Program;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StudentPortalTest extends TestCase
{
    use RefreshDatabase;

    public function test_portal_login_and_logout_set_and_clear_session_access(): void
    {
        User::factory()->create([
            'email' => config('student_portal.admin_email'),
            'password' => 'password123',
            'role' => 'admin',
        ]);

        $this->get(route('student.login'))
            ->assertOk()
            ->assertSee('Student Login')
            ->assertSee('action="'.route('student.login.submit').'"', false)
            ->assertDontSee('Administrator login')
            ->assertSee('Create student account')
            ->assertDontSee('>Courses</a>', false)
            ->assertDontSee('Demo Credentials')
            ->assertDontSee('password123');

        $this->get(route('admin.login'))
            ->assertOk()
            ->assertSee('Administrator Login')
            ->assertSee('action="'.route('admin.login.submit').'"', false)
            ->assertDontSee('Student login')
            ->assertDontSee('>Courses</a>', false)
            ->assertDontSee('Create student account');

        $student = User::factory()->create(['role' => 'student', 'password' => 'student-password']);
        $this->post(route('admin.login.submit'), [
            'username' => $student->email,
            'password' => 'student-password',
        ])
            ->assertRedirect(route('admin.login'))
            ->assertSessionHasErrors('username');
        $this->assertGuest();

        $this->from(route('student.login'))->post(route('student.login.submit'), [
            'username' => 'admin',
            'password' => 'password123',
        ])
            ->assertRedirect(route('student.login'))
            ->assertSessionHasErrors('username');
        $this->assertGuest();

        $this->post(route('admin.login.submit'), [
            'username' => 'admin',
            'password' => 'password123',
        ])
            ->assertRedirect(route('students.index'))
            ->assertSessionHas('portal_access', true);

        $this->post(route('portal.logout'))
            ->assertRedirect(route('admin.login'))
            ->assertSessionMissing('portal_access');
    }

    public function test_navigation_places_course_and_student_links_next_to_the_portal_title(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->withSession(['portal_access' => true])
            ->actingAs($admin)
            ->get(route('courses.index'))
            ->assertSeeInOrder([
                'Student Portal</a>',
                'class="portal-nav-link"',
                '>Dashboard</a>',
                'class="portal-nav-link active"',
                '>Courses</a>',
                'class="portal-nav-link"',
                '>Enrollment Applications</a>',
                'class="portal-nav-link"',
                '>Students</a>',
                '>Logout</button>',
            ], false);

        $this->withSession(['portal_access' => true])
            ->actingAs($admin)
            ->get(route('students.index'))
            ->assertSeeInOrder([
                'class="portal-nav-link"',
                '>Courses</a>',
                '>Enrollment Applications</a>',
                'class="portal-nav-link active"',
                '>Students</a>',
            ], false);

        $student = User::factory()->create(['role' => 'student']);
        $this->withSession(['portal_access' => true])
            ->actingAs($student)
            ->get(route('catalog.courses.index'))
            ->assertSee('href="'.route('catalog.courses.index').'"', false)
            ->assertDontSee('Enrollment Applications')
            ->assertDontSee('Student Directory');
    }

    public function test_guest_is_redirected_from_dashboard_to_portal_login(): void
    {
        $this->get(route('dashboard'))
            ->assertRedirect(route('student.login'));
    }

    public function test_dashboard_displays_student_course_program_and_pending_application_totals(): void
    {
        $program = $this->createProgram('BSIT');
        $otherProgram = $this->createProgram('BSCS');
        $student = $this->createStudent($program);
        $this->createCourse($program, 'IT101');
        $this->createCourse($otherProgram, 'CS101', ['status' => 'archived']);
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
                'program_id' => $program->id,
                'student_type' => 'new',
                'year_level' => 1,
                'school_year' => '2026-2027',
                'semester' => '1st',
                'status' => $status,
            ]);
        }

        $admin = User::factory()->create(['role' => 'admin']);
        $this->withSession(['portal_access' => true])->actingAs($admin);

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

    public function test_student_can_update_personal_information_and_linked_login_details(): void
    {
        $program = $this->createProgram('BSIT');
        $user = User::factory()->create([
            'name' => 'Taylor Student',
            'email' => 'taylor@example.test',
            'role' => 'student',
        ]);
        $student = $this->createStudent($program);
        $student->update(['user_id' => $user->id, 'email' => $user->email]);

        $this->withSession(['portal_access' => true])
            ->actingAs($user)
            ->get(route('student.profile.edit'))
            ->assertOk()
            ->assertSee('Edit Profile')
            ->assertSee('Profile photo');

        $this->put(route('student.profile.update'), [
            'first_name' => 'Taylor',
            'middle_name' => 'Rae',
            'last_name' => 'Student',
            'suffix' => 'Jr.',
            'birth_date' => '2005-04-10',
            'gender' => 'Female',
            'civil_status' => 'Single',
            'nationality' => 'Filipino',
            'email' => 'Taylor.Updated@example.test',
            'contact_number' => '09171234567',
            'address' => '12 School Street',
        ])->assertRedirect(route('student.profile.edit'));

        $this->assertDatabaseHas('students', [
            'id' => $student->id,
            'middle_name' => 'Rae',
            'gender' => 'Female',
            'civil_status' => 'Single',
            'nationality' => 'Filipino',
            'email' => 'taylor.updated@example.test',
            'contact_number' => '09171234567',
            'address' => '12 School Street',
        ]);
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Taylor Rae Student Jr.',
            'email' => 'taylor.updated@example.test',
        ]);
    }

    public function test_student_profile_photo_is_private_and_rejects_non_images(): void
    {
        Storage::fake('local');
        $program = $this->createProgram('BSIT');
        $user = User::factory()->create(['role' => 'student']);
        $student = $this->createStudent($program);
        $student->update(['user_id' => $user->id]);

        $this->withSession(['portal_access' => true])
            ->actingAs($user)
            ->post(route('student.profile.photo.update'), [
                'profile_photo' => UploadedFile::fake()->createWithContent(
                    'profile.png',
                    base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAIAAACQd1PeAAAADUlEQVR4nGP4z8AAAAMBAQDJ/pLvAAAAAElFTkSuQmCC'),
                ),
            ])
            ->assertRedirect(route('student.profile.edit'));

        $photoPath = $student->fresh()->profile_photo_path;
        $this->assertNotNull($photoPath);
        Storage::disk('local')->assertExists($photoPath);
        $this->get(route('student.profile.photo'))->assertOk();

        $otherUser = User::factory()->create(['role' => 'student']);
        $otherStudent = $this->createStudent($program);
        $otherStudent->update(['user_id' => $otherUser->id]);

        $this->actingAs($otherUser)
            ->withSession(['portal_access' => true])
            ->get(route('student.profile.photo'))
            ->assertNotFound();

        $this->actingAs($user)
            ->withSession(['portal_access' => true])
            ->post(route('student.profile.photo.update'), [
                'profile_photo' => UploadedFile::fake()->create('profile.txt', 10, 'text/plain'),
            ])
            ->assertSessionHasErrors('profile_photo');

        $this->assertSame($photoPath, $student->fresh()->profile_photo_path);
        Storage::disk('local')->assertExists($photoPath);
    }

    public function test_student_dashboard_displays_enrolled_courses_and_posted_grades(): void
    {
        $program = $this->createProgram('BSIT');
        $user = User::factory()->create(['role' => 'student']);
        $student = $this->createStudent($program);
        $student->update(['user_id' => $user->id]);
        $course = $this->createCourse($program, 'IT101');
        $enrollment = Enrollment::create([
            'reference_number' => 'ENR-2026-000001',
            'student_id' => $student->id,
            'program_id' => $program->id,
            'academic_year' => '2026-2027',
            'term' => '1st',
            'status' => 'enrolled',
        ]);
        $enrollment->courses()->attach($course->id);
        $student->courses()->attach($course->id, ['enrollment_id' => $enrollment->id, 'grade' => '1.25']);

        $this->withSession(['portal_access' => true])
            ->actingAs($user)
            ->get(route('student.dashboard'))
            ->assertOk()
            ->assertSee('IT101')
            ->assertSee('1.25');
    }

    public function test_guest_cannot_update_a_student_grade(): void
    {
        $program = $this->createProgram('BSIT');
        $student = $this->createStudent($program);
        $course = $this->createCourse($program, 'IT101');
        $student->courses()->attach($course->id);

        $this->post(route('students.courses.update-grade', [$student, $course]), [
            'grade' => '1.25',
        ])->assertRedirect(route('student.login'));

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

        $this->get(route('catalog.courses.index'))
            ->assertOk()
            ->assertSee($activeCourse->title)
            ->assertDontSee($archivedCourse->title);
    }

    public function test_portal_user_can_create_update_and_delete_a_student(): void
    {
        $program = $this->createProgram('BSIT');
        $admin = User::factory()->create(['role' => 'admin']);
        $this->withSession(['portal_access' => true])->actingAs($admin);

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
        $admin = User::factory()->create(['role' => 'admin']);
        $this->withSession(['portal_access' => true])->actingAs($admin);

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
        $admin = User::factory()->create(['role' => 'admin']);
        $this->withSession(['portal_access' => true, 'success' => 'Student updated successfully.'])
            ->actingAs($admin)
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
        $admin = User::factory()->create(['role' => 'admin']);
        $this->withSession(['portal_access' => true])->actingAs($admin);

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
        $admin = User::factory()->create(['role' => 'admin']);
        $this->withSession(['portal_access' => true])->actingAs($admin);

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
        $admin = User::factory()->create(['role' => 'admin']);
        $this->withSession(['portal_access' => true])->actingAs($admin);

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
