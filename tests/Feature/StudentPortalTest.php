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
            'username' => config('student_portal.admin_email'),
            'password' => 'password123',
        ])
            ->assertRedirect(route('student.login'))
            ->assertSessionHasErrors('username');
        $this->assertGuest();

        $this->post(route('admin.login.submit'), [
            'username' => config('student_portal.admin_email'),
            'password' => 'password123',
        ])
            ->assertRedirect(route('dashboard'))
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
                'portal-nav-link',
                '>Dashboard</a>',
                'portal-nav-link active',
                '>Courses</a>',
                'portal-nav-link',
                '>Enrollment Applications</a>',
                'portal-nav-link',
                '>Students</a>',
                '>Logout</button>',
            ], false);

        $this->withSession(['portal_access' => true])
            ->actingAs($admin)
            ->get(route('students.index'))
            ->assertSeeInOrder([
                'class="nav-link portal-nav-link"',
                '>Courses</a>',
                '>Enrollment Applications</a>',
                'portal-nav-link active',
                '>Students</a>',
            ], false);

        $student = User::factory()->create(['role' => 'student']);
        $this->withSession(['portal_access' => true])
            ->actingAs($student)
            ->get(route('catalog.courses.index'))
            ->assertDontSee('>Courses</a>', false)
            ->assertDontSee('Enrollment Applications')
            ->assertDontSee('Student Directory');
    }

    public function test_guest_is_redirected_from_dashboard_to_portal_login(): void
    {
        $this->get(route('dashboard'))
            ->assertRedirect(route('admin.login'));
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
            ->assertSee('Pending Applications')
            ->assertSee('Recent Enrollment Applications')
            ->assertSee('Active Students')
            ->assertSee('Inactive Students')
            ->assertSee('Dropped Students')
            ->assertViewHas('studentCount', 1)
            ->assertViewHas('pendingApplicationCount', 1)
            ->assertViewHas('activeStudentCount', 1)
            ->assertViewHas('recentApplications', fn ($applications): bool => $applications->count() === 2);
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

    public function test_inactive_and_dropped_students_cannot_log_in_or_continue_using_an_existing_session(): void
    {
        $program = $this->createProgram('BSIT');

        foreach (['inactive', 'dropped'] as $status) {
            $student = $this->createStudent($program);
            $user = User::factory()->create([
                'email' => $student->email,
                'password' => 'portal-password',
                'role' => 'student',
            ]);
            $student->update(['user_id' => $user->id, 'status' => $status]);

            $this->post(route('student.login.submit'), [
                'username' => $student->email,
                'password' => 'portal-password',
            ])->assertSessionHasErrors('username');

            $this->actingAs($user)
                ->withSession(['portal_access' => true])
                ->get(route('student.dashboard'))
                ->assertRedirect(route('student.login'));
            $this->assertGuest();
        }
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
        $enrollment->courses()->attach($course->id, ['grade' => '1.25']);
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
        $enrollment = Enrollment::create([
            'reference_number' => 'ENR-2026-000003',
            'student_id' => $student->id,
            'program_id' => $program->id,
            'academic_year' => '2026-2027',
            'term' => '1st',
            'status' => 'enrolled',
        ]);
        $enrollment->courses()->attach($course->id);

        $this->post(route('students.enrollments.courses.update-grade', [$student, $enrollment, $course]), [
            'grade' => '1.25',
        ])->assertRedirect(route('admin.login'));

        $this->assertDatabaseHas('enrollment_course', [
            'enrollment_id' => $enrollment->id,
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

    public function test_subjects_with_student_history_cannot_be_changed_or_deleted(): void
    {
        $program = $this->createProgram('BSIT');
        $student = $this->createStudent($program);
        $course = $this->createCourse($program, 'IT101');
        $student->courses()->attach($course->id);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->put('/courses/'.$course->id, [
                'code' => 'IT101',
                'title' => 'Revised Subject',
                'units' => 3,
                'year_level' => 1,
                'semester' => '1st',
                'program_id' => $program->id,
            ])
            ->assertSessionHasErrors('course');

        $this->delete('/courses/'.$course->id)->assertMethodNotAllowed();
        $this->delete('/programs/'.$program->id)->assertNotFound();

        $this->assertDatabaseHas('programs', ['id' => $program->id]);
        $this->assertDatabaseHas('courses', ['id' => $course->id, 'title' => 'IT101 Course']);
    }

    public function test_admin_can_edit_students_without_manual_creation_or_deletion(): void
    {
        $program = $this->createProgram('BSIT');
        $student = $this->createStudent($program);
        $admin = User::factory()->create(['role' => 'admin']);
        $this->withSession(['portal_access' => true])->actingAs($admin);

        $this->put(route('students.update', $student), [
            'student_number' => $student->student_number,
            'first_name' => $student->first_name,
            'middle_name' => null,
            'last_name' => 'Updated',
            'suffix' => null,
            'email' => $student->email,
            'contact_number' => null,
            'address' => null,
            'birth_date' => '2005-04-10',
            'year_level' => 1,
            'program_id' => $program->id,
            'status' => 'dropped',
        ])->assertRedirect(route('students.index'));

        $this->assertDatabaseHas('students', [
            'id' => $student->id,
            'last_name' => 'Updated',
            'status' => 'dropped',
        ]);

        $this->delete('/students/'.$student->id)
            ->assertMethodNotAllowed();

        $this->get('/students/create')->assertNotFound();
        $this->post('/students')->assertMethodNotAllowed();
        $this->assertDatabaseHas('students', ['id' => $student->id]);
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

    public function test_student_directory_search_and_status_filter_keep_pagination_query(): void
    {
        $program = $this->createProgram('BSIT');
        $matchingStudent = $this->createStudent($program);
        $matchingStudent->update(['status' => 'inactive']);
        $this->createStudent($program);
        $admin = User::factory()->create(['role' => 'admin']);

        $otherStudent = $this->createStudent($program);
        $this->actingAs($admin)
            ->get(route('students.index', ['search' => $matchingStudent->student_number, 'status' => 'inactive']))
            ->assertOk()
            ->assertSeeText($matchingStudent->student_number)
            ->assertDontSeeText($otherStudent->student_number)
            ->assertViewHas('students', fn ($students): bool => $students->total() === 1
                && str_contains($students->url(1), 'search='.$matchingStudent->student_number)
                && str_contains($students->url(1), 'status=inactive'));

        $this->get(route('students.index', ['search' => $matchingStudent->full_name]))
            ->assertOk()
            ->assertSeeText($matchingStudent->student_number);
        $this->get(route('students.index', ['search' => $matchingStudent->email]))
            ->assertOk()
            ->assertSeeText($matchingStudent->student_number);
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
        $enrollment = Enrollment::create([
            'reference_number' => 'ENR-2026-000004',
            'student_id' => $student->id,
            'program_id' => $program->id,
            'academic_year' => '2026-2027',
            'term' => '1st',
            'status' => 'enrolled',
        ]);
        $enrollment->courses()->attach($enrolledCourse->id);
        $admin = User::factory()->create(['role' => 'admin']);
        $this->withSession(['portal_access' => true])->actingAs($admin);

        $this->from(route('students.show', $student))
            ->post(route('students.enrollments.courses.update-grade', [$student, $enrollment, $enrolledCourse]), [
                'grade' => 'Incomplete',
            ])
            ->assertSessionHasErrors('grade');

        $this->post(route('students.enrollments.courses.update-grade', [$student, $enrollment, $enrolledCourse]), [
            'grade' => '1.25',
        ])->assertRedirect();

        $this->assertDatabaseHas('enrollment_course', [
            'enrollment_id' => $enrollment->id,
            'course_id' => $enrolledCourse->id,
            'grade' => '1.25',
        ]);

        $this->post(route('students.enrollments.courses.update-grade', [$student, $enrollment, $otherCourse]), [
            'grade' => '1.50',
        ])->assertNotFound();
    }

    public function test_admin_can_update_a_grade_on_the_specific_enrollment_record(): void
    {
        $program = $this->createProgram('BSIT');
        $student = $this->createStudent($program);
        $course = $this->createCourse($program, 'IT101');
        $enrollment = Enrollment::create([
            'reference_number' => 'ENR-2026-000002',
            'student_id' => $student->id,
            'program_id' => $program->id,
            'academic_year' => '2026-2027',
            'term' => '1st',
            'status' => 'enrolled',
        ]);
        $enrollment->courses()->attach($course->id);

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->post(route('students.enrollments.courses.update-grade', [$student, $enrollment, $course]), ['grade' => '1.25'])
            ->assertRedirect();

        $this->assertDatabaseHas('enrollment_course', [
            'enrollment_id' => $enrollment->id,
            'course_id' => $course->id,
            'grade' => '1.25',
        ]);
    }

    public function test_manual_student_add_routes_are_not_available(): void
    {
        $program = $this->createProgram('BSIT');
        $admin = User::factory()->create(['role' => 'admin']);
        $this->withSession(['portal_access' => true])->actingAs($admin);

        $this->get('/students/create')->assertNotFound();
        $this->post('/students')->assertMethodNotAllowed();
        $this->assertDatabaseCount('students', 0);
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
