<?php

namespace Tests\Feature;

use App\Mail\EnrollmentApplicationApproved;
use App\Models\Course;
use App\Models\Department;
use App\Models\EmailHistory;
use App\Models\Enrollment;
use App\Models\EnrollmentApplication;
use App\Models\Program;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\CourseSeeder;
use Database\Seeders\DepartmentSeeder;
use Database\Seeders\ProgramSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Mail\Mailer;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class EnrollmentApplicationFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_enrollment_page_shows_the_five_step_form_without_login(): void
    {
        $this->get('/portal/login')->assertOk()->assertSee('Start an application');
        $this->get('/enrollment')
            ->assertOk()
            ->assertSee('Personal')
            ->assertSee('Contact')
            ->assertSee('Academic')
            ->assertSee('Subjects')
            ->assertSee('Review');

        $this->assertGuest();
    }

    public function test_catalog_seeders_map_programs_to_departments_and_supply_active_subjects(): void
    {
        $this->seed([DepartmentSeeder::class, ProgramSeeder::class, CourseSeeder::class]);

        $program = Program::where('code', 'BSIT')->firstOrFail();
        $this->assertSame('CIT', $program->department->code);
        $this->assertSame('active', $program->status);

        $subject = Course::where('code', 'IT101')->firstOrFail();
        $this->assertSame('1st', $subject->semester);
        $this->assertSame('active', $subject->status);
    }

    public function test_admin_can_create_update_and_archive_departments_programs_and_subjects(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        $this->post('/departments', ['code' => 'CIT', 'name' => 'College of Information Technology'])
            ->assertRedirect('/departments');
        $department = Department::where('code', 'CIT')->firstOrFail();

        $this->post('/programs', [
            'code' => 'BSIT',
            'name' => 'BS Information Technology',
            'department_id' => $department->id,
        ])->assertRedirect('/programs');
        $program = Program::where('code', 'BSIT')->firstOrFail();

        $this->post('/courses', [
            'code' => 'IT101',
            'title' => 'Introduction to Computing',
            'units' => 3,
            'year_level' => 1,
            'semester' => '1st',
            'program_id' => $program->id,
        ])->assertRedirect('/courses');
        $subject = Course::where('code', 'IT101')->firstOrFail();

        $this->put('/courses/'.$subject->id, [
            'code' => 'IT101',
            'title' => 'Introduction to Computing and Information Technology',
            'units' => 3,
            'year_level' => 1,
            'semester' => '1st',
            'program_id' => $program->id,
        ])->assertRedirect('/courses');
        $this->post('/courses/'.$subject->id.'/archive')->assertRedirect('/courses');
        $this->post('/programs/'.$program->id.'/archive')->assertRedirect('/programs');
        $this->post('/departments/'.$department->id.'/archive')->assertRedirect('/departments');

        $this->assertDatabaseHas('courses', ['id' => $subject->id, 'status' => 'archived']);
        $this->assertDatabaseHas('programs', ['id' => $program->id, 'status' => 'archived']);
        $this->assertDatabaseHas('departments', ['id' => $department->id, 'status' => 'archived']);
    }

    public function test_admin_catalog_management_pages_render_for_departments_programs_and_subjects(): void
    {
        $department = Department::create(['code' => 'CIT', 'name' => 'College of Information Technology']);
        $program = Program::create([
            'code' => 'BSIT',
            'name' => 'BS Information Technology',
            'department_id' => $department->id,
        ]);
        $subject = Course::create([
            'code' => 'IT101',
            'title' => 'Introduction to Computing',
            'units' => 3,
            'year_level' => 1,
            'semester' => '1st',
            'program_id' => $program->id,
        ]);
        $this->actingAs(User::factory()->create(['role' => 'admin']));

        $this->get('/departments')->assertOk()->assertSee('CIT');
        $this->get('/departments/create')->assertOk();
        $this->get('/departments/'.$department->id.'/edit')->assertOk();
        $this->get('/programs')->assertOk()->assertSee('BS Information Technology');
        $this->get('/programs/create')->assertOk();
        $this->get('/programs/'.$program->id.'/edit')->assertOk();
        $this->get('/courses')->assertOk()->assertSee('IT101');
        $this->get('/courses/create')->assertOk()->assertSee('1st Semester');
        $this->get('/courses/'.$subject->id.'/edit')->assertOk();
    }

    public function test_admin_can_archive_a_student_and_archived_students_cannot_sign_in(): void
    {
        $program = Program::create(['code' => 'BSIT', 'name' => 'BS Information Technology']);
        $studentUser = User::factory()->create(['role' => 'student', 'password' => 'portal-password']);
        $student = Student::create([
            'user_id' => $studentUser->id,
            'student_number' => '2026-00001',
            'student_id' => '2026-00001',
            'first_name' => 'Juan',
            'last_name' => 'Cruz',
            'email' => $studentUser->email,
            'birth_date' => '2005-04-10',
            'program_id' => $program->id,
            'year_level' => 1,
        ]);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get('/students')->assertOk()->assertSee('Archive');
        $this->post('/students/'.$student->id.'/archive')->assertRedirect('/students');
        $this->assertDatabaseHas('students', ['id' => $student->id, 'status' => 'archived']);

        $this->post('/portal/login', [
            'username' => $studentUser->email,
            'password' => 'portal-password',
        ])->assertSessionHasErrors('username');
    }

    public function test_complete_public_submission_creates_only_a_pending_application(): void
    {
        [$department, $program, $subject] = $this->makeCatalog();
        $this->submitStepsOneToThree($department, $program);

        $this->get('/enrollment?step=4')->assertOk()->assertSee($subject->code);
        $this->post('/enrollment/step/4', ['selected_subject_ids' => [$subject->id]])
            ->assertRedirect('/enrollment?step=5');

        $submissionToken = $this->app['session.store']->get('enrollment_submission_token');
        $response = $this->post('/enrollment/submit', ['submission_token' => $submissionToken])->assertRedirect();
        $application = EnrollmentApplication::firstOrFail();

        $this->assertMatchesRegularExpression('/^ENR-\d{4}-\d{5}$/', $application->application_number);
        $this->assertSame('pending', $application->status);
        $this->assertSame('APPLICANT@example.test', $application->email);
        $this->assertSame([$subject->id], $application->subjects()->pluck('courses.id')->all());
        $this->assertSame(0, Student::count());
        $this->assertSame(0, Enrollment::count());
        $this->assertDatabaseHas('enrollment_applications', [
            'application_number' => $application->application_number,
            'status' => 'pending',
        ]);
        $this->get('/enrollment/success/'.$application->application_number)
            ->assertOk()
            ->assertSee($application->application_number)
            ->assertSee('Pending Review')
            ->assertSee('not officially enrolled yet');
        $this->assertGuest();
    }

    public function test_a_submission_token_prevents_duplicate_applications_for_the_same_double_submit(): void
    {
        [$department, $program, $subject] = $this->makeCatalog();
        $this->submitStepsOneToThree($department, $program);
        $this->get('/enrollment?step=4');
        $this->post('/enrollment/step/4', ['selected_subject_ids' => [$subject->id]]);
        $submissionToken = $this->app['session.store']->get('enrollment_submission_token');

        $this->assertIsString($submissionToken);
        $this->post('/enrollment/submit', ['submission_token' => $submissionToken])->assertRedirect()->assertSessionHasNoErrors();
        $application = EnrollmentApplication::firstOrFail();
        $this->post('/enrollment/submit', ['submission_token' => $submissionToken])
            ->assertRedirect(route('enrollment.success', $application));

        $this->assertDatabaseCount('enrollment_applications', 1);
        $this->assertDatabaseHas('enrollment_applications', ['submission_token' => $submissionToken]);
    }

    public function test_subjects_outside_the_selected_program_year_or_semester_are_rejected(): void
    {
        [$department, $program] = $this->makeCatalog();
        $otherProgram = Program::create([
            'code' => 'BSCS',
            'name' => 'Computer Science',
            'department_id' => $department->id,
        ]);
        $otherSubject = Course::create([
            'code' => 'CS101',
            'title' => 'Computer Science Basics',
            'units' => 3,
            'year_level' => 1,
            'semester' => '1st',
            'program_id' => $otherProgram->id,
        ]);
        $this->submitStepsOneToThree($department, $program);

        $this->post('/enrollment/step/4', ['selected_subject_ids' => [$otherSubject->id]])
            ->assertSessionHasErrors('selected_subject_ids.0');

        $this->assertDatabaseCount('enrollment_applications', 0);
    }

    public function test_only_admin_can_access_application_review_and_processing(): void
    {
        $application = $this->submitPublicApplication();

        $this->get('/admin/enrollment-applications')->assertRedirect('/portal/login');

        $student = User::factory()->create(['role' => 'student']);
        $this->actingAs($student)
            ->get('/admin/enrollment-applications/'.$application->application_number)
            ->assertForbidden();
    }

    public function test_admin_approval_creates_student_and_enrollment_and_sends_dynamic_email(): void
    {
        $application = $this->submitPublicApplication();
        $admin = User::factory()->create(['role' => 'admin']);
        Mail::fake();

        $this->actingAs($admin)
            ->get('/admin/enrollment-applications/'.$application->application_number)
            ->assertOk()
            ->assertSee($application->email)
            ->assertSee('IT101');

        $this->post('/admin/enrollment-applications/'.$application->application_number.'/process', [
            'action' => 'approve',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $application->refresh();
        $student = Student::findOrFail($application->student_id);
        $enrollment = Enrollment::where('application_id', $application->id)->firstOrFail();

        $this->assertSame('approved', $application->status);
        $this->assertSame($admin->id, $application->approved_by);
        $this->assertMatchesRegularExpression('/^\d{4}-\d{5}$/', $student->student_id);
        $this->assertSame('Dela', $student->middle_name);
        $this->assertSame($student->student_id, $application->sample_username);
        $this->assertNotSame('Student@123', $application->getRawOriginal('sample_password'));
        $this->assertSame('enrolled', $enrollment->status);
        $this->assertSame($application->school_year, $enrollment->academic_year);
        $this->assertSame($application->semester, $enrollment->term);
        $this->assertDatabaseHas('enrollment_course', [
            'enrollment_id' => $enrollment->id,
            'course_id' => $application->subjects()->value('courses.id'),
        ]);
        $this->assertDatabaseMissing('users', ['email' => $application->email]);
        $this->assertDatabaseHas('email_histories', [
            'enrollment_application_id' => $application->id,
            'recipient' => $application->email,
            'status' => 'sent',
        ]);
        Mail::assertSent(EnrollmentApplicationApproved::class, fn (EnrollmentApplicationApproved $mail): bool =>
            $mail->hasTo($application->email)
        );

        $message = (new EnrollmentApplicationApproved($application->fresh(['student', 'department', 'program'])))->render();
        $this->assertStringContainsString($student->student_id, $message);
        $this->assertStringContainsString($application->full_name, $message);
        $this->assertStringContainsString('Important:', $message);
    }

    public function test_rejection_requires_a_reason_and_does_not_create_student_or_enrollment(): void
    {
        $application = $this->submitPublicApplication();
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->post('/admin/enrollment-applications/'.$application->application_number.'/process', [
                'action' => 'reject',
            ])
            ->assertSessionHasErrors('rejection_reason');

        $this->post('/admin/enrollment-applications/'.$application->application_number.'/process', [
            'action' => 'reject',
            'rejection_reason' => 'Please submit the missing prior transcript.',
        ])->assertRedirect();

        $application->refresh();
        $this->assertSame('rejected', $application->status);
        $this->assertSame($admin->id, $application->rejected_by);
        $this->assertSame('Please submit the missing prior transcript.', $application->rejection_reason);
        $this->assertSame(0, Student::count());
        $this->assertSame(0, Enrollment::count());
        $this->assertSame(0, EmailHistory::count());
    }

    public function test_admin_can_approve_an_application_after_marking_it_under_review(): void
    {
        $application = $this->submitPublicApplication();
        $admin = User::factory()->create(['role' => 'admin']);
        Mail::fake();

        $this->actingAs($admin)
            ->post('/admin/enrollment-applications/'.$application->application_number.'/process', [
                'action' => 'under_review',
            ])->assertRedirect();

        $this->post('/admin/enrollment-applications/'.$application->application_number.'/process', [
            'action' => 'approve',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertDatabaseHas('enrollment_applications', [
            'id' => $application->id,
            'status' => 'approved',
            'approved_by' => $admin->id,
        ]);
        $this->assertDatabaseCount('enrollments', 1);
    }

    public function test_an_approved_application_cannot_be_approved_twice(): void
    {
        $application = $this->submitPublicApplication();
        $admin = User::factory()->create(['role' => 'admin']);
        Mail::fake();

        $this->actingAs($admin)->post('/admin/enrollment-applications/'.$application->application_number.'/process', ['action' => 'approve'])->assertRedirect();
        $this->post('/admin/enrollment-applications/'.$application->application_number.'/process', ['action' => 'approve'])
            ->assertSessionHasErrors('action');

        $this->assertDatabaseCount('enrollments', 1);
        $this->assertDatabaseCount('students', 1);
        Mail::assertSent(EnrollmentApplicationApproved::class, 1);
    }

    public function test_admin_can_edit_pending_application_and_cannot_edit_after_decision(): void
    {
        $application = $this->submitPublicApplication();
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)
            ->get('/admin/enrollment-applications/'.$application->application_number.'/edit')
            ->assertOk();

        $this->patch('/admin/enrollment-applications/'.$application->application_number, [
            'first_name' => 'Juan Miguel',
            'middle_name' => 'Dela',
            'last_name' => 'Cruz',
            'birth_date' => $application->birth_date->toDateString(),
            'gender' => $application->gender,
            'civil_status' => $application->civil_status,
            'nationality' => $application->nationality,
            'email' => $application->email,
            'contact_number' => $application->contact_number,
            'house_block_lot' => $application->house_block_lot,
            'street' => $application->street,
            'barangay' => $application->barangay,
            'city' => $application->city,
            'province' => $application->province,
            'department_id' => $application->department_id,
            'program_id' => $application->program_id,
            'student_type' => $application->student_type,
            'year_level' => $application->year_level,
            'school_year' => $application->school_year,
            'semester' => $application->semester,
            'selected_subject_ids' => $application->subjects()->pluck('courses.id')->all(),
        ])->assertRedirect();

        $this->assertDatabaseHas('enrollment_applications', [
            'id' => $application->id,
            'first_name' => 'Juan Miguel',
            'status' => 'pending',
        ]);

        $application->update(['status' => 'approved']);
        $this->get('/admin/enrollment-applications/'.$application->application_number.'/edit')->assertForbidden();
    }

    public function test_existing_student_cannot_receive_a_duplicate_active_enrollment_for_the_same_term(): void
    {
        $firstApplication = $this->submitPublicApplication();
        $admin = User::factory()->create(['role' => 'admin']);
        Mail::fake();
        $this->actingAs($admin)->post('/admin/enrollment-applications/'.$firstApplication->application_number.'/process', ['action' => 'approve']);

        $secondApplication = EnrollmentApplication::create([
            'first_name' => $firstApplication->first_name,
            'middle_name' => $firstApplication->middle_name,
            'last_name' => $firstApplication->last_name,
            'birth_date' => $firstApplication->birth_date,
            'gender' => $firstApplication->gender,
            'email' => $firstApplication->email,
            'contact_number' => $firstApplication->contact_number,
            'barangay' => $firstApplication->barangay,
            'city' => $firstApplication->city,
            'province' => $firstApplication->province,
            'department_id' => $firstApplication->department_id,
            'program_id' => $firstApplication->program_id,
            'student_type' => 'Returning Student',
            'year_level' => 2,
            'school_year' => $firstApplication->school_year,
            'semester' => $firstApplication->semester,
            'status' => 'pending',
            'submitted_at' => now(),
        ]);
        $secondApplication->update(['application_number' => 'ENR-'.now()->format('Y').'-99999']);
        $secondApplication->subjects()->sync($firstApplication->subjects()->pluck('courses.id')->all());

        $this->post('/admin/enrollment-applications/'.$secondApplication->application_number.'/process', ['action' => 'approve'])
            ->assertSessionHasErrors('action');

        $this->assertDatabaseHas('enrollment_applications', ['id' => $secondApplication->id, 'status' => 'pending']);
        $this->assertDatabaseCount('students', 1);
        $this->assertDatabaseCount('enrollments', 1);
    }

    public function test_email_failure_is_logged_and_resend_creates_a_successful_history_attempt(): void
    {
        $application = $this->submitPublicApplication();
        $admin = User::factory()->create(['role' => 'admin']);
        $mailer = Mockery::mock(Mailer::class);
        $mailer->shouldReceive('send')->once()->andThrow(new RuntimeException('SMTP unavailable'));
        Mail::shouldReceive('to')->once()->with($application->email)->andReturn($mailer);
        Log::shouldReceive('error')->once();

        $this->actingAs($admin)
            ->post('/admin/enrollment-applications/'.$application->application_number.'/process', ['action' => 'approve'])
            ->assertSessionHas('warning');

        $this->assertDatabaseHas('email_histories', [
            'enrollment_application_id' => $application->id,
            'status' => 'failed',
            'error_message' => 'SMTP unavailable',
        ]);
        $this->assertDatabaseHas('enrollment_applications', ['id' => $application->id, 'status' => 'approved']);

        $resendMailer = Mockery::mock(Mailer::class);
        $resendMailer->shouldReceive('send')->once();
        Mail::shouldReceive('to')->once()->with($application->email)->andReturn($resendMailer);

        $this->post('/admin/enrollment-applications/'.$application->application_number.'/resend-email')
            ->assertSessionHas('success');

        $this->assertDatabaseCount('email_histories', 2);
        $this->assertDatabaseHas('email_histories', [
            'enrollment_application_id' => $application->id,
            'status' => 'sent',
            'error_message' => null,
        ]);
    }

    private function makeCatalog(): array
    {
        $department = Department::create(['code' => 'CIT', 'name' => 'College of Information Technology']);
        $program = Program::create([
            'code' => 'BSIT',
            'name' => 'BS Information Technology',
            'department_id' => $department->id,
        ]);
        $subject = Course::create([
            'code' => 'IT101',
            'title' => 'Introduction to Computing',
            'units' => 3,
            'year_level' => 1,
            'semester' => '1st',
            'program_id' => $program->id,
        ]);

        return [$department, $program, $subject];
    }

    private function submitStepsOneToThree(Department $department, Program $program): void
    {
        $this->post('/enrollment/step/1', [
            'first_name' => 'Juan',
            'middle_name' => 'Dela',
            'last_name' => 'Cruz',
            'birth_date' => '2005-04-10',
            'gender' => 'Male',
            'civil_status' => 'Single',
            'nationality' => 'Filipino',
        ])->assertRedirect('/enrollment?step=2');
        $this->post('/enrollment/step/2', [
            'email' => 'APPLICANT@example.test',
            'contact_number' => '+639171234567',
            'house_block_lot' => '12A',
            'street' => 'School Street',
            'barangay' => 'Central',
            'city' => 'Quezon City',
            'province' => 'Metro Manila',
        ])->assertRedirect('/enrollment?step=3');
        $this->post('/enrollment/step/3', [
            'department_id' => $department->id,
            'program_id' => $program->id,
            'student_type' => 'New Student',
            'year_level' => 1,
            'school_year' => '2026-2027',
            'semester' => '1st',
        ])->assertRedirect('/enrollment?step=4');
    }

    private function submitPublicApplication(): EnrollmentApplication
    {
        [$department, $program, $subject] = $this->makeCatalog();
        $this->submitStepsOneToThree($department, $program);
        $this->get('/enrollment?step=4');
        $this->post('/enrollment/step/4', ['selected_subject_ids' => [$subject->id]]);
        $submissionToken = $this->app['session.store']->get('enrollment_submission_token');
        $this->post('/enrollment/submit', ['submission_token' => $submissionToken])->assertRedirect();

        return EnrollmentApplication::firstOrFail();
    }
}
