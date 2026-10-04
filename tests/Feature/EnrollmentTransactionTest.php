<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\EnrollmentApplication;
use App\Models\Program;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class EnrollmentTransactionTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_student_can_open_the_registration_page(): void
    {
        $this->get('/portal/register')
            ->assertOk()
            ->assertSee('Student number')
            ->assertSee('Confirm password')
            ->assertDontSee('Date of Birth')
            ->assertDontSee('Program');
    }

    public function test_registration_creates_a_student_account_with_a_hashed_password(): void
    {
        $student = $this->createApprovedStudent('STU-2026-001', 'casey@example.test');

        $this->post('/portal/register', [
            'first_name' => 'Casey',
            'last_name' => 'Student',
            'student_number' => $student->student_number,
            'email' => 'casey@example.test',
            'password' => 'secure-password',
            'password_confirmation' => 'secure-password',
            'role' => 'admin',
        ])->assertRedirect('/student/login');

        $student->refresh();
        $this->assertSame('student', $student->user->role);
        $this->assertTrue(Hash::check('secure-password', $student->user->password));
        $this->assertSame($student->user_id, EnrollmentApplication::where('student_id', $student->id)->value('applicant_user_id'));
    }

    public function test_registration_is_rejected_until_the_student_has_an_approved_application(): void
    {
        $program = Program::create(['code' => 'BSIT', 'name' => 'Information Technology']);
        Student::create([
            'student_number' => 'STU-2026-002',
            'first_name' => 'New',
            'last_name' => 'Applicant',
            'email' => 'new.applicant@example.test',
            'birth_date' => '2005-04-10',
            'program_id' => $program->id,
        ]);

        $this->from('/portal/register')->post('/portal/register', [
            'first_name' => 'New',
            'last_name' => 'Applicant',
            'student_number' => 'STU-2026-002',
            'email' => 'new.applicant@example.test',
            'password' => 'secure-password',
            'password_confirmation' => 'secure-password',
        ])->assertSessionHasErrors('student_number');

        $this->assertDatabaseCount('users', 0);
    }

    public function test_duplicate_student_accounts_are_rejected(): void
    {
        $student = $this->createApprovedStudent('STU-2026-107', 'casey@example.test');
        $this->post('/portal/register', [
            'first_name' => 'Casey',
            'last_name' => 'Student',
            'student_number' => $student->student_number,
            'email' => $student->email,
            'password' => 'secure-password',
            'password_confirmation' => 'secure-password',
        ])->assertRedirect('/student/login');

        $this->from('/portal/register')->post('/portal/register', [
            'first_name' => 'Casey',
            'last_name' => 'Student',
            'student_number' => $student->student_number,
            'email' => $student->email,
            'password' => 'secure-password',
            'password_confirmation' => 'secure-password',
        ])->assertSessionHasErrors('email');

        $this->assertDatabaseCount('users', 1);
    }

    public function test_duplicate_emails_are_rejected_without_case_variation(): void
    {
        $student = $this->createApprovedStudent('STU-2026-110', 'casey@example.test');
        User::factory()->create(['email' => $student->email]);

        $this->from('/portal/register')->post('/portal/register', [
            'first_name' => 'Casey',
            'last_name' => 'Student',
            'student_number' => $student->student_number,
            'email' => strtoupper($student->email),
            'password' => 'secure-password',
            'password_confirmation' => 'secure-password',
        ])->assertSessionHasErrors('email');

        $this->assertDatabaseCount('users', 1);
    }

    public function test_registration_links_an_existing_student_record_when_number_and_email_match(): void
    {
        $student = $this->createApprovedStudent('STU-EXISTING-1', 'existing@example.test', [
            'first_name' => 'Existing',
        ]);

        $this->post('/portal/register', [
            'student_number' => $student->student_number,
            'first_name' => 'Existing',
            'last_name' => 'Student',
            'email' => $student->email,
            'password' => 'secure-password',
            'password_confirmation' => 'secure-password',
        ])->assertRedirect('/student/login');

        $this->assertDatabaseCount('students', 1);
        $this->assertNotNull($student->fresh()->user_id);
        $this->assertSame('student', $student->fresh()->user->role);
    }

    public function test_existing_student_activation_requires_the_profile_identity_to_match(): void
    {
        $student = $this->createApprovedStudent('STU-EXISTING-2', 'existing2@example.test', [
            'first_name' => 'Existing',
        ]);

        $this->from('/portal/register')->post('/portal/register', [
            'student_number' => $student->student_number,
            'first_name' => 'Different',
            'last_name' => 'Person',
            'email' => $student->email,
            'password' => 'secure-password',
            'password_confirmation' => 'secure-password',
        ])->assertSessionHasErrors('first_name');

        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('students', 1);
    }

    public function test_registration_matches_student_numbers_without_case_sensitivity(): void
    {
        $student = $this->createApprovedStudent('STU-2026-116', 'casey116@example.test');

        $this->from('/portal/register')->post('/portal/register', [
            'student_number' => strtolower($student->student_number),
            'first_name' => 'Casey',
            'last_name' => 'Student',
            'email' => $student->email,
            'password' => 'secure-password',
            'password_confirmation' => 'secure-password',
        ])->assertRedirect('/student/login');

        $this->assertNotNull($student->fresh()->user_id);
    }

    public function test_registration_shows_clear_email_and_password_validation_messages(): void
    {
        $this->from('/portal/register')->post('/portal/register', [
            'first_name' => 'Casey',
            'last_name' => 'Student',
            'student_number' => 'STU-2026-117',
            'email' => 'not-an-email',
            'password' => 'secure-password',
            'password_confirmation' => 'different-password',
        ])->assertSessionHasErrors([
            'email' => 'Enter a valid email address, such as name@example.com.',
            'password' => 'The password confirmation does not match.',
        ]);

        $this->assertDatabaseCount('users', 0);
    }

    public function test_seeded_admin_can_log_in_using_the_configured_email(): void
    {
        config(['student_portal.admin_password' => 'safe-test-password']);
        $this->seed(AdminUserSeeder::class);

        $this->post('/admin/login', [
            'username' => config('student_portal.admin_email'),
            'password' => 'safe-test-password',
        ])->assertRedirect('/dashboard');

        $this->assertAuthenticatedAs(User::where('email', config('student_portal.admin_email'))->firstOrFail());
        $this->get('/dashboard')->assertOk();
    }

    public function test_incorrect_login_credentials_do_not_authenticate(): void
    {
        $this->post('/student/login', [
            'username' => 'unknown@example.test',
            'password' => 'wrong-password',
        ])->assertSessionHasErrors('username');

        $this->assertGuest();
    }

    public function test_student_email_login_is_case_insensitive(): void
    {
        $student = $this->registerStudent('STU-2026-114');
        $this->post('/portal/logout')->assertRedirect('/student/login');

        $this->post('/student/login', [
            'username' => strtoupper($student->email),
            'password' => 'secure-password',
        ])->assertRedirect('/student/dashboard');

        $this->assertAuthenticatedAs($student->user);
    }

    public function test_term_enrollment_request_routes_are_removed(): void
    {
        $this->registerStudent('STU-ROUTE-001');

        $this->get('/student/enrollments/create')->assertNotFound();
        $this->post('/student/enrollments')->assertNotFound();
        $this->get('/admin/enrollments')->assertNotFound();
    }

    public function test_admin_application_history_does_not_link_to_term_enrollment_requests(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get('/admin/enrollment-applications/history')
            ->assertOk()
            ->assertDontSee('Term Enrollment Requests');
    }

    public function test_students_cannot_open_admin_pages_or_write_grades(): void
    {
        $student = $this->registerStudent('STU-2026-102');
        $course = Course::create([
            'code' => 'IT102',
            'title' => 'Foundations of Computing',
            'units' => 3,
            'year_level' => 1,
            'program_id' => $student->program_id,
        ]);
        $student->courses()->attach($course->id);
        $enrollment = Enrollment::create([
            'reference_number' => 'ENR-2026-000102',
            'student_id' => $student->id,
            'program_id' => $student->program_id,
            'academic_year' => '2026-2027',
            'term' => '1st',
            'status' => 'enrolled',
        ]);
        $enrollment->courses()->attach($course->id);

        $this->get('/students')->assertForbidden();
        $this->get('/admin/enrollments')->assertNotFound();
        $this->post("/students/{$student->id}/enrollments/{$enrollment->reference_number}/courses/{$course->id}/grade", ['grade' => '1.00'])
            ->assertForbidden();
    }

    public function test_a_student_cannot_view_another_students_enrollment(): void
    {
        $student = $this->registerStudent('STU-2026-104');
        $enrollment = Enrollment::create([
            'reference_number' => 'ENR-2026-000104',
            'student_id' => $student->id,
            'program_id' => $student->program_id,
            'academic_year' => '2026-2027',
            'term' => '1st',
            'status' => 'enrolled',
        ]);
        $this->registerStudent('STU-2026-105');

        $this->get('/student/enrollments/'.$enrollment->reference_number)->assertNotFound();
    }

    public function test_admin_can_manage_courses_while_students_cannot_access_course_management(): void
    {
        $student = $this->registerStudent('STU-2026-106');
        $this->get('/courses')->assertForbidden();

        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)
            ->get('/courses')
            ->assertOk()
            ->assertSee(route('students.index'), false)
            ->assertSee('Student Directory');
        $this->get('/courses/create')->assertOk();

        $this->post('/courses', [
            'code' => 'IT106',
            'title' => 'Secure Systems',
            'units' => 3,
            'year_level' => 2,
            'semester' => '1st',
            'program_id' => $student->program_id,
        ])->assertRedirect('/courses');

        $this->assertDatabaseHas('courses', ['code' => 'IT106', 'program_id' => $student->program_id]);
        $courseId = Course::where('code', 'IT106')->value('id');
        $this->get('/courses/'.$courseId.'/edit')->assertOk();
        $this->put('/courses/'.$courseId, [
            'code' => 'IT106',
            'title' => 'Secure Systems Revised',
            'units' => 3,
            'year_level' => 2,
            'semester' => '1st',
            'program_id' => $student->program_id,
        ])->assertRedirect('/courses');
        $this->assertDatabaseHas('courses', ['id' => $courseId, 'title' => 'Secure Systems Revised']);
    }

    public function test_admin_student_profile_updates_keep_linked_login_details_in_sync(): void
    {
        $student = $this->registerStudent('STU-2026-113');
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->put('/students/'.$student->id, [
                'student_number' => $student->student_number,
                'first_name' => 'Casey Updated',
                'last_name' => 'Student',
                'email' => 'casey.updated@example.test',
                'birth_date' => $student->birth_date->toDateString(),
                'year_level' => $student->year_level,
                'program_id' => $student->program_id,
                'status' => 'active',
            ])
            ->assertRedirect('/students');

        $this->assertDatabaseHas('users', [
            'id' => $student->user_id,
            'name' => 'Casey Updated Student',
            'email' => 'casey.updated@example.test',
        ]);
    }

    public function test_admin_cannot_update_a_grade_for_a_course_not_assigned_to_that_student(): void
    {
        $student = $this->registerStudent('STU-2026-112');
        $course = Course::create([
            'code' => 'IT112',
            'title' => 'Unassigned Course',
            'units' => 3,
            'year_level' => 1,
            'program_id' => $student->program_id,
        ]);
        $enrollment = Enrollment::create([
            'reference_number' => 'ENR-2026-000112',
            'student_id' => $student->id,
            'program_id' => $student->program_id,
            'academic_year' => '2026-2027',
            'term' => '1st',
            'status' => 'enrolled',
        ]);

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->post("/students/{$student->id}/enrollments/{$enrollment->reference_number}/courses/{$course->id}/grade", ['grade' => '1.00'])
            ->assertNotFound();
    }

    private function createApprovedStudent(
        string $studentNumber,
        string $email,
        array $attributes = [],
    ): Student {
        $program = Program::create([
            'code' => 'P'.(Program::count() + 1),
            'name' => 'Program '.(Program::count() + 1),
        ]);
        $student = Student::create(array_merge([
            'student_number' => $studentNumber,
            'student_id' => $studentNumber,
            'first_name' => 'Casey',
            'last_name' => 'Student',
            'email' => $email,
            'birth_date' => '2005-04-10',
            'year_level' => 1,
            'program_id' => $program->id,
            'status' => 'active',
        ], $attributes));

        EnrollmentApplication::create([
            'application_number' => 'APP-'.strtoupper(fake()->unique()->bothify('????-####')),
            'first_name' => $student->first_name,
            'last_name' => $student->last_name,
            'birth_date' => $student->birth_date->toDateString(),
            'gender' => 'Prefer not to say',
            'email' => $student->email,
            'contact_number' => '09123456789',
            'barangay' => 'Central',
            'city' => 'Sample City',
            'province' => 'Sample Province',
            'program_id' => $program->id,
            'student_id' => $student->id,
            'student_type' => 'New Student',
            'year_level' => $student->year_level,
            'school_year' => '2026-2027',
            'semester' => '1st',
            'status' => 'approved',
            'approved_at' => now(),
        ]);

        return $student;
    }

    private function registerStudent(string $studentNumber): Student
    {
        $program = Program::create([
            'code' => 'P'.(Program::count() + 1),
            'name' => 'Program '.(Program::count() + 1),
        ]);
        $email = strtolower($studentNumber).'@example.test';
        $user = User::factory()->create([
            'name' => 'Casey Student',
            'email' => $email,
            'password' => 'secure-password',
            'role' => 'student',
        ]);
        $student = Student::create([
            'user_id' => $user->id,
            'student_number' => $studentNumber,
            'student_id' => $studentNumber,
            'first_name' => 'Casey',
            'last_name' => 'Student',
            'email' => $email,
            'birth_date' => '2005-04-10',
            'year_level' => 1,
            'program_id' => $program->id,
            'status' => 'active',
        ]);

        $this->actingAs($user)->withSession(['portal_access' => true]);
        $this->assertAuthenticatedAs($student->user);
        $this->assertSame('student', $student->user->role);
        $this->assertNotNull($student->user->student);

        return $student;
    }
}
