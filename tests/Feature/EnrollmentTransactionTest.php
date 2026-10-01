<?php

namespace Tests\Feature;

use App\Mail\EnrollmentConfirmed;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Program;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use RuntimeException;
use Tests\TestCase;

class EnrollmentTransactionTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_student_can_open_the_registration_page(): void
    {
        $this->get('/portal/register')->assertOk();
    }

    public function test_registration_creates_a_student_account_with_a_hashed_password(): void
    {
        $program = Program::create(['code' => 'BSIT', 'name' => 'Information Technology']);

        $this->post('/portal/register', [
            'first_name' => 'Casey',
            'last_name' => 'Student',
            'student_number' => 'STU-2026-001',
            'email' => 'casey@example.test',
            'birth_date' => '2005-04-10',
            'program_id' => $program->id,
            'year_level' => 1,
            'password' => 'secure-password',
            'password_confirmation' => 'secure-password',
            'role' => 'admin',
        ])->assertRedirect('/portal/login');

        $student = Student::where('student_number', 'STU-2026-001')->firstOrFail();

        $this->assertSame('student', $student->user->role);
        $this->assertTrue(Hash::check('secure-password', $student->user->password));
    }

    public function test_duplicate_student_accounts_are_rejected(): void
    {
        $student = $this->registerStudent('STU-2026-107');

        $this->from('/portal/register')->post('/portal/register', [
            'first_name' => 'Casey',
            'last_name' => 'Student',
            'student_number' => $student->student_number,
            'email' => 'another@example.test',
            'birth_date' => '2005-04-10',
            'program_id' => $student->program_id,
            'year_level' => 1,
            'password' => 'secure-password',
            'password_confirmation' => 'secure-password',
        ])->assertSessionHasErrors('student_number');

        $this->assertDatabaseCount('students', 1);
    }

    public function test_duplicate_emails_are_rejected_without_case_variation(): void
    {
        $student = $this->registerStudent('STU-2026-110');

        $this->from('/portal/register')->post('/portal/register', [
            'first_name' => 'Another',
            'last_name' => 'Student',
            'student_number' => 'STU-2026-111',
            'email' => strtoupper($student->email),
            'birth_date' => '2005-04-10',
            'program_id' => $student->program_id,
            'year_level' => 1,
            'password' => 'secure-password',
            'password_confirmation' => 'secure-password',
        ])->assertSessionHasErrors('email');

        $this->assertDatabaseCount('students', 1);
    }

    public function test_registration_links_an_existing_student_record_when_number_and_email_match(): void
    {
        $program = Program::create(['code' => 'BSIT', 'name' => 'Information Technology']);
        $student = Student::create([
            'student_number' => 'STU-EXISTING-1',
            'first_name' => 'Existing',
            'last_name' => 'Student',
            'email' => 'existing@example.test',
            'birth_date' => '2004-02-20',
            'year_level' => 2,
            'program_id' => $program->id,
        ]);

        $this->post('/portal/register', [
            'student_number' => $student->student_number,
            'first_name' => 'Existing',
            'last_name' => 'Student',
            'email' => $student->email,
            'birth_date' => '2004-02-20',
            'program_id' => $program->id,
            'year_level' => 2,
            'password' => 'secure-password',
            'password_confirmation' => 'secure-password',
        ])->assertRedirect('/portal/login');

        $this->assertDatabaseCount('students', 1);
        $this->assertNotNull($student->fresh()->user_id);
        $this->assertSame('student', $student->fresh()->user->role);
    }

    public function test_existing_student_activation_requires_the_profile_identity_to_match(): void
    {
        $program = Program::create(['code' => 'BSIT', 'name' => 'Information Technology']);
        Student::create([
            'student_number' => 'STU-EXISTING-2',
            'first_name' => 'Existing',
            'last_name' => 'Student',
            'email' => 'existing2@example.test',
            'birth_date' => '2004-02-20',
            'year_level' => 2,
            'program_id' => $program->id,
        ]);

        $this->from('/portal/register')->post('/portal/register', [
            'student_number' => 'STU-EXISTING-2',
            'first_name' => 'Different',
            'last_name' => 'Person',
            'email' => 'existing2@example.test',
            'birth_date' => '2004-02-20',
            'program_id' => $program->id,
            'year_level' => 2,
            'password' => 'secure-password',
            'password_confirmation' => 'secure-password',
        ])->assertSessionHasErrors('student_number');

        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('students', 1);
    }

    public function test_case_variant_student_numbers_cannot_create_duplicate_profiles(): void
    {
        $student = $this->registerStudent('STU-2026-116');

        $this->from('/portal/register')->post('/portal/register', [
            'student_number' => strtolower($student->student_number),
            'first_name' => 'Another',
            'last_name' => 'Student',
            'email' => 'other116@example.test',
            'birth_date' => '2005-04-10',
            'program_id' => $student->program_id,
            'year_level' => 1,
            'password' => 'secure-password',
            'password_confirmation' => 'secure-password',
        ])->assertSessionHasErrors('student_number');

        $this->assertDatabaseCount('students', 1);
    }

    public function test_seeded_admin_can_log_in_using_the_existing_admin_username(): void
    {
        $this->seed(AdminUserSeeder::class);

        $this->post('/portal/login', [
            'username' => 'admin',
            'password' => config('student_portal.admin_password'),
        ])->assertRedirect('/students');

        $this->assertAuthenticatedAs(User::where('email', config('student_portal.admin_email'))->firstOrFail());
        $this->get('/admin/enrollments')->assertOk();
    }

    public function test_incorrect_login_credentials_do_not_authenticate(): void
    {
        $this->post('/portal/login', [
            'username' => 'unknown@example.test',
            'password' => 'wrong-password',
        ])->assertSessionHasErrors('username');

        $this->assertGuest();
    }

    public function test_student_email_login_is_case_insensitive(): void
    {
        $student = $this->registerStudent('STU-2026-114');
        $this->post('/portal/logout')->assertRedirect('/portal/login');

        $this->post('/portal/login', [
            'username' => strtoupper($student->email),
            'password' => 'secure-password',
        ])->assertRedirect('/student/dashboard');

        $this->assertAuthenticatedAs($student->user);
    }

    public function test_a_student_can_submit_one_enrollment_per_term_and_track_its_reference(): void
    {
        $student = $this->registerStudent('STU-2026-101');
        $payload = ['academic_year' => '2026-2027', 'term' => '1st'];

        $this->get('/student/dashboard')->assertOk();
        $this->get('/student/enrollments/create')->assertOk();
        $this->post('/student/enrollments', $payload)->assertRedirect();

        $enrollment = Enrollment::firstOrFail();
        $this->assertMatchesRegularExpression('/^ENR-\d{4}-\d{6}$/', $enrollment->reference_number);
        $this->assertSame('pending', $enrollment->status);

        $this->post('/student/enrollments', $payload)->assertSessionHasErrors('term');
        $this->assertDatabaseCount('enrollments', 1);

        $this->get('/student/enrollments/'.$enrollment->reference_number)
            ->assertOk()
            ->assertSee($enrollment->reference_number)
            ->assertSee('Pending');
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

        $this->get('/students')->assertForbidden();
        $this->get('/admin/enrollments')->assertForbidden();
        $this->post("/students/{$student->id}/courses/{$course->id}/grade", ['grade' => '1.00'])
            ->assertForbidden();
    }

    public function test_admin_processing_assigns_courses_sends_confirmation_and_keeps_grade_writes_admin_only(): void
    {
        $student = $this->registerStudent('STU-2026-103');
        $course = Course::create([
            'code' => 'IT103',
            'title' => 'Systems Fundamentals',
            'units' => 3,
            'year_level' => 1,
            'program_id' => $student->program_id,
        ]);
        $this->post('/student/enrollments', ['academic_year' => '2026-2027', 'term' => '1st']);
        $enrollment = Enrollment::firstOrFail();
        $admin = User::factory()->create(['role' => 'admin']);
        Mail::fake();

        $this->actingAs($admin)
            ->get('/admin/enrollments')
            ->assertOk()
            ->assertSee($enrollment->reference_number);
        $this->get('/admin/enrollments/'.$enrollment->reference_number)->assertOk();

        $this->patch('/admin/enrollments/'.$enrollment->reference_number, ['status' => 'processing'])
            ->assertRedirect();
        Mail::assertNothingSent();
        $this->patch('/admin/enrollments/'.$enrollment->reference_number, [
            'status' => 'enrolled',
            'program_id' => $student->program_id,
            'course_ids' => [$course->id],
        ])->assertRedirect();

        $this->assertDatabaseHas('enrollments', [
            'id' => $enrollment->id,
            'status' => 'enrolled',
            'program_id' => $student->program_id,
        ]);
        $this->assertDatabaseHas('enrollment_course', [
            'enrollment_id' => $enrollment->id,
            'course_id' => $course->id,
        ]);
        $this->get('/admin/enrollments/'.$enrollment->reference_number)->assertOk();
        Mail::assertSent(EnrollmentConfirmed::class, fn (EnrollmentConfirmed $mail): bool => $mail->enrollment->reference_number === $enrollment->reference_number
        );
        $confirmation = (new EnrollmentConfirmed($enrollment->fresh(['student', 'program'])))->render();
        $this->assertStringContainsString($enrollment->reference_number, $confirmation);
        $this->assertStringContainsString($student->full_name, $confirmation);
        $this->assertStringContainsString('Enrolled', $confirmation);
        $this->assertStringContainsString('Program '.substr($student->program->code, 1), $confirmation);

        $this->post("/students/{$student->id}/courses/{$course->id}/grade", ['grade' => '1.25'])
            ->assertRedirect();
        $this->assertDatabaseHas('course_student', [
            'student_id' => $student->id,
            'course_id' => $course->id,
            'enrollment_id' => $enrollment->id,
            'grade' => '1.25',
        ]);

        $this->actingAs($student->user)
            ->get('/student/enrollments/'.$enrollment->reference_number)
            ->assertOk()
            ->assertSee('Enrolled')
            ->assertSee($course->code);
        $this->post("/students/{$student->id}/courses/{$course->id}/grade", ['grade' => '1.00'])
            ->assertForbidden();
    }

    public function test_admin_cannot_skip_the_processing_transition(): void
    {
        $student = $this->registerStudent('STU-2026-108');
        $this->post('/student/enrollments', ['academic_year' => '2026-2027', 'term' => '1st']);
        $enrollment = Enrollment::firstOrFail();
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->patch('/admin/enrollments/'.$enrollment->reference_number, [
                'status' => 'enrolled',
                'program_id' => $student->program_id,
                'course_ids' => [],
            ])
            ->assertSessionHasErrors('status');

        $this->assertDatabaseHas('enrollments', ['id' => $enrollment->id, 'status' => 'pending']);
    }

    public function test_admin_can_open_an_enrollment_by_its_reference_number(): void
    {
        $student = $this->registerStudent('STU-2026-115');
        $this->post('/student/enrollments', ['academic_year' => '2026-2027', 'term' => '1st']);
        $enrollment = Enrollment::firstOrFail();

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get('/admin/enrollments/'.$enrollment->reference_number)
            ->assertOk()
            ->assertSee($enrollment->reference_number)
            ->assertSee($student->full_name);
    }

    public function test_email_delivery_failure_does_not_roll_back_enrollment(): void
    {
        $student = $this->registerStudent('STU-2026-109');
        $course = Course::create([
            'code' => 'IT109',
            'title' => 'Portal Systems',
            'units' => 3,
            'year_level' => 1,
            'program_id' => $student->program_id,
        ]);
        $this->post('/student/enrollments', ['academic_year' => '2026-2027', 'term' => '1st']);
        $enrollment = Enrollment::firstOrFail();
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->patch('/admin/enrollments/'.$enrollment->reference_number, ['status' => 'processing']);

        Mail::shouldReceive('to')->once()->with($student->email)->andThrow(new RuntimeException('SMTP unavailable'));

        $this->patch('/admin/enrollments/'.$enrollment->reference_number, [
            'status' => 'enrolled',
            'program_id' => $student->program_id,
            'course_ids' => [$course->id],
        ])->assertSessionHas('warning');

        $this->assertDatabaseHas('enrollments', ['id' => $enrollment->id, 'status' => 'enrolled']);
    }

    public function test_a_student_cannot_view_another_students_enrollment(): void
    {
        $student = $this->registerStudent('STU-2026-104');
        $this->post('/student/enrollments', ['academic_year' => '2026-2027', 'term' => '1st']);
        $enrollment = Enrollment::firstOrFail();
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
            ->assertSee('Back to Students');
        $this->get('/courses/create')->assertOk();

        $this->post('/courses', [
            'code' => 'IT106',
            'title' => 'Secure Systems',
            'units' => 3,
            'year_level' => 2,
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
        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->post("/students/{$student->id}/courses/{$course->id}/grade", ['grade' => '1.00'])
            ->assertNotFound();
    }

    private function registerStudent(string $studentNumber): Student
    {
        $program = Program::create([
            'code' => 'P'.(Program::count() + 1),
            'name' => 'Program '.(Program::count() + 1),
        ]);
        $email = strtolower($studentNumber).'@example.test';

        $this->post('/portal/register', [
            'first_name' => 'Casey',
            'last_name' => 'Student',
            'student_number' => $studentNumber,
            'email' => $email,
            'birth_date' => '2005-04-10',
            'program_id' => $program->id,
            'year_level' => 1,
            'password' => 'secure-password',
            'password_confirmation' => 'secure-password',
        ])->assertRedirect('/portal/login');

        $this->post('/portal/login', [
            'username' => $email,
            'password' => 'secure-password',
        ])->assertRedirect('/student/dashboard');

        $student = Student::where('student_number', $studentNumber)->firstOrFail();
        $this->assertAuthenticatedAs($student->user);
        $this->assertSame('student', $student->user->role);
        $this->assertNotNull($student->user->student);

        return $student;
    }
}
