<?php

namespace Tests\Feature;

use App\Mail\EnrollmentApplicationApproved;
use App\Mail\EnrollmentApplicationRejected;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\EnrollmentApplication;
use App\Models\Program;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\CourseSeeder;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\ProgramSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Mail\Mailer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class EnrollmentApplicationFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_enrollment_page_shows_the_five_step_form_without_login(): void
    {
        $this->get('/student/login')->assertOk()->assertSee('Start an application');
        $this->get('/enrollment')
            ->assertOk()
            ->assertSee('Personal')
            ->assertSee('Contact')
            ->assertSee('Academic')
            ->assertSee('Subjects')
            ->assertSee('Review');

        $this->assertGuest();
    }

    public function test_catalog_seeders_assign_all_supplied_courses_to_their_programs(): void
    {
        $this->seed([ProgramSeeder::class, CourseSeeder::class]);

        $expectedCourseCounts = ['BSIT' => 50, 'BSCS' => 49, 'BSBA' => 50, 'BSA' => 50, 'BSED' => 54];
        foreach ($expectedCourseCounts as $code => $count) {
            $program = Program::where('code', $code)->firstOrFail();
            $this->assertSame('active', $program->status);
            $this->assertSame($count, $program->courses()->where('status', 'active')->count());
        }

        $program = Program::where('code', 'BSIT')->firstOrFail();
        $subject = Course::whereBelongsTo($program)->where('code', 'IT101')->firstOrFail();
        $this->assertSame('Introduction to Computing', $subject->title);
        $this->assertSame('1st', $subject->semester);
        $this->assertSame('active', $subject->status);
        $this->assertSame(5, Course::where('code', 'GE101')->count());
        $this->assertDatabaseHas('courses', [
            'program_id' => Program::where('code', 'BSA')->value('id'),
            'code' => 'AC406',
            'title' => 'Accounting Practicum',
            'semester' => '2nd',
            'units' => 6,
        ]);
    }

    public function test_default_database_seeder_populates_program_scoped_courses_for_students(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(5, Program::count());
        $this->assertSame(253, Course::count());
        $this->assertSame(51, Student::count());
        $this->assertGreaterThan(0, DB::table('course_student')->count());
    }

    public function test_admin_can_create_update_and_archive_programs_and_subjects(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        $this->post('/programs', [
            'code' => 'BSIT',
            'name' => 'BS Information Technology',
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

        $this->assertDatabaseHas('courses', ['id' => $subject->id, 'status' => 'archived']);
        $this->assertDatabaseHas('programs', ['id' => $program->id, 'status' => 'archived']);
    }

    public function test_course_codes_can_repeat_between_programs_but_not_within_one_program(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);
        $bsit = Program::create(['code' => 'BSIT', 'name' => 'Information Technology']);
        $bscs = Program::create(['code' => 'BSCS', 'name' => 'Computer Science']);

        foreach ([$bsit, $bscs] as $program) {
            $this->post('/courses', [
                'code' => 'GE101',
                'title' => 'Understanding the Self',
                'units' => 3,
                'year_level' => 1,
                'semester' => '1st',
                'program_id' => $program->id,
            ])->assertRedirect('/courses');
        }

        $this->post('/courses', [
            'code' => 'GE101',
            'title' => 'Duplicate within program',
            'units' => 3,
            'year_level' => 1,
            'semester' => '1st',
            'program_id' => $bsit->id,
        ])->assertSessionHasErrors('code');

        $this->assertDatabaseCount('courses', 2);
    }

    public function test_admin_catalog_management_pages_render_without_department_pages(): void
    {
        $program = Program::create([
            'code' => 'BSIT',
            'name' => 'BS Information Technology',
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

        $this->get('/departments')->assertNotFound();
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

        $this->post('/student/login', [
            'username' => $studentUser->email,
            'password' => 'portal-password',
        ])->assertSessionHasErrors('username');
    }

    public function test_complete_public_submission_creates_only_a_pending_application(): void
    {
        Storage::fake('local');
        [$program, $subject] = $this->makeCatalog();
        $this->submitStepsOneToThree($program);

        $this->get('/enrollment?step=4')->assertOk()->assertSee($subject->code);
        $this->post('/enrollment/step/4', ['selected_subject_ids' => [$subject->id]])
            ->assertRedirect('/enrollment?step=5');

        $submissionToken = $this->app['session.store']->get('enrollment_submission_token');
        $this->post('/enrollment/submit', [
            'submission_token' => $submissionToken,
            ...$this->requirementUploads(),
        ])->assertRedirect();
        $application = EnrollmentApplication::firstOrFail();

        $this->assertMatchesRegularExpression('/^ENR-\d{4}-\d{5}$/', $application->application_number);
        $this->assertSame('pending', $application->status);
        $this->assertSame('APPLICANT@example.test', $application->email);
        $this->assertSame([$subject->id], $application->subjects()->pluck('courses.id')->all());
        $this->assertCount(4, $application->documents);
        foreach ($application->documents as $document) {
            Storage::disk('local')->assertExists($document->file_path);
        }
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

    public function test_application_submission_succeeds_without_departments(): void
    {
        Storage::fake('local');
        [$program, $subject] = $this->makeCatalog();
        $this->submitStepsOneToThree($program);

        $this->get('/enrollment?step=4');
        $this->post('/enrollment/step/4', ['selected_subject_ids' => [$subject->id]]);
        $submissionToken = $this->app['session.store']->get('enrollment_submission_token');

        $this->post('/enrollment/submit', [
            'submission_token' => $submissionToken,
            ...$this->requirementUploads(),
        ])->assertRedirect();

        $application = EnrollmentApplication::firstOrFail();
        $this->assertSame($program->id, $application->program_id);
        $this->assertFalse(Schema::hasTable('departments'));
    }

    public function test_a_submission_token_prevents_duplicate_applications_for_the_same_double_submit(): void
    {
        Storage::fake('local');
        [$program, $subject] = $this->makeCatalog();
        $this->submitStepsOneToThree($program);
        $this->get('/enrollment?step=4');
        $this->post('/enrollment/step/4', ['selected_subject_ids' => [$subject->id]]);
        $submissionToken = $this->app['session.store']->get('enrollment_submission_token');

        $this->assertIsString($submissionToken);
        $this->post('/enrollment/submit', [
            'submission_token' => $submissionToken,
            ...$this->requirementUploads(),
        ])->assertRedirect()->assertSessionHasNoErrors();
        $application = EnrollmentApplication::firstOrFail();
        $this->post('/enrollment/submit', ['submission_token' => $submissionToken])
            ->assertRedirect(route('enrollment.success', $application));

        $this->assertDatabaseCount('enrollment_applications', 1);
        $this->assertDatabaseHas('enrollment_applications', ['submission_token' => $submissionToken]);
    }

    public function test_application_submission_requires_every_configured_document(): void
    {
        Storage::fake('local');
        [$program, $subject] = $this->makeCatalog();
        $this->submitStepsOneToThree($program);
        $this->get('/enrollment?step=4');
        $this->post('/enrollment/step/4', ['selected_subject_ids' => [$subject->id]]);
        $submissionToken = $this->app['session.store']->get('enrollment_submission_token');

        $this->post('/enrollment/submit', ['submission_token' => $submissionToken])
            ->assertSessionHasErrors([
                'requirements.birth_certificate',
                'requirements.senior_high_record',
                'requirements.good_moral',
                'requirements.id_photo',
            ]);

        $uploads = $this->requirementUploads();
        $uploads['requirements']['birth_certificate'] = UploadedFile::fake()->create(
            'not-a-document.exe',
            100,
            'application/octet-stream',
        );
        $this->post('/enrollment/submit', [
            'submission_token' => $submissionToken,
            ...$uploads,
        ])->assertSessionHasErrors('requirements.birth_certificate');

        $this->assertDatabaseCount('enrollment_applications', 0);
    }

    public function test_student_can_apply_with_uploaded_requirements_and_keep_account_access_after_approval(): void
    {
        Storage::fake('local');
        [$program, $subject] = $this->makeCatalog();
        $this->submitStepsOneToThree($program);
        $this->get('/enrollment?step=4');
        $this->post('/enrollment/step/4', ['selected_subject_ids' => [$subject->id]]);
        $submissionToken = $this->app['session.store']->get('enrollment_submission_token');
        $this->post('/enrollment/submit', [
            'submission_token' => $submissionToken,
            ...$this->requirementUploads(),
        ])->assertRedirect();

        $application = EnrollmentApplication::firstOrFail();
        $this->assertNull($application->applicant_user_id);
        $this->assertNull($application->student_id);
        $this->assertSame($program->id, $application->program_id);
        $this->from('/portal/register')->post('/portal/register', [
            'first_name' => 'Juan',
            'last_name' => 'Cruz',
            'student_number' => '2026-00001',
            'email' => 'applicant@example.test',
            'password' => 'secure-password',
            'password_confirmation' => 'secure-password',
        ])->assertSessionHasErrors('student_number');
        $this->assertDatabaseCount('users', 0);

        $document = $application->documents()->firstOrFail();
        $downloadUrl = route('admin.enrollment-applications.documents.show', [$application, $document]);
        $this->get($downloadUrl)->assertRedirect(route('admin.login'));

        $admin = User::factory()->create(['role' => 'admin']);
        Mail::fake();
        $this->actingAs($admin)
            ->post('/admin/enrollment-applications/'.$application->application_number.'/process', ['action' => 'approve'])
            ->assertRedirect();

        $application->refresh();
        $student = Student::findOrFail($application->student_id);
        $this->assertSame('approved', $application->status);
        $this->assertSame('active', $student->status);
        $this->assertNull($application->sample_username);

        $this->post('/portal/register', [
            'first_name' => 'Juan',
            'last_name' => 'Cruz',
            'student_number' => $student->student_number,
            'email' => 'applicant@example.test',
            'password' => 'secure-password',
            'password_confirmation' => 'secure-password',
        ])->assertRedirect('/student/login');

        $studentUser = User::where('email', 'applicant@example.test')->firstOrFail();
        $this->assertSame($studentUser->id, $student->fresh()->user_id);
        $this->assertSame($studentUser->id, $application->fresh()->applicant_user_id);
        $this->assertTrue(Hash::check('secure-password', $studentUser->password));
        $this->post('/student/login', [
            'username' => 'applicant@example.test',
            'password' => 'secure-password',
        ])->assertRedirect('/student/dashboard');
        $this->get('/student/dashboard')->assertOk()->assertSee('Apply / Submit Requirements');

        $this->actingAs($admin)->get($downloadUrl)->assertDownload($document->original_name);
        $this->get('/admin/enrollment-applications/'.$application->application_number)
            ->assertOk()
            ->assertSee('Submitted Requirements')
            ->assertSee('already has portal access');
    }

    public function test_subjects_outside_the_selected_program_year_or_semester_are_rejected(): void
    {
        [$program] = $this->makeCatalog();
        $otherProgram = Program::create([
            'code' => 'BSCS',
            'name' => 'Computer Science',
        ]);
        $otherSubject = Course::create([
            'code' => 'CS101',
            'title' => 'Computer Science Basics',
            'units' => 3,
            'year_level' => 1,
            'semester' => '1st',
            'program_id' => $otherProgram->id,
        ]);
        $this->submitStepsOneToThree($program);

        $this->post('/enrollment/step/4', ['selected_subject_ids' => [$otherSubject->id]])
            ->assertSessionHasErrors('selected_subject_ids.0');

        $this->assertDatabaseCount('enrollment_applications', 0);
    }

    public function test_only_admin_can_access_application_review_and_processing(): void
    {
        $application = $this->submitPublicApplication();

        $this->get('/admin/enrollment-applications')->assertRedirect('/admin/login');

        $student = User::factory()->create(['role' => 'student']);
        $this->actingAs($student)
            ->get('/admin/enrollment-applications/'.$application->application_number)
            ->assertForbidden();
    }

    public function test_admin_approval_creates_student_and_enrollment_and_sends_dynamic_email(): void
    {
        $application = $this->submitPublicApplication();
        $admin = User::factory()->create(['role' => 'admin']);
        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.transport' => 'smtp',
            'mail.mailers.smtp.host' => 'smtp.example.test',
        ]);
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
        $this->assertNull($application->sample_username);
        $this->assertNull($application->sample_password);
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
        Mail::assertSent(EnrollmentApplicationApproved::class, fn (EnrollmentApplicationApproved $mail): bool => $mail->hasTo($application->email)
        );

        $message = (new EnrollmentApplicationApproved($application->fresh(['student', 'program'])))->render();
        $this->assertStringContainsString($student->student_id, $message);
        $this->assertStringContainsString('Hello '.$application->first_name, $message);
        $this->assertStringNotContainsString(route('student.login'), $message);
        $this->assertStringContainsString('Create your student account', $message);
        $this->assertStringContainsString(route('portal.register'), $message);
    }

    public function test_rejection_requires_a_reason_and_does_not_create_student_or_enrollment(): void
    {
        $application = $this->submitPublicApplication();
        $admin = User::factory()->create(['role' => 'admin']);
        Mail::fake();

        $this->actingAs($admin)
            ->post('/admin/enrollment-applications/'.$application->application_number.'/process', [
                'action' => 'reject',
            ])
            ->assertSessionHasErrors('rejection_reason');

        $this->post('/admin/enrollment-applications/'.$application->application_number.'/process', [
            'action' => 'reject',
            'rejection_reason' => "Please submit a clear photo. <script>alert('x')</script>",
        ])->assertRedirect();

        $application->refresh();
        $this->assertSame('rejected', $application->status);
        $this->assertSame($admin->id, $application->rejected_by);
        $this->assertSame("Please submit a clear photo. <script>alert('x')</script>", $application->rejection_reason);
        $this->assertSame(0, Student::count());
        $this->assertSame(0, Enrollment::count());
        $this->assertDatabaseHas('email_histories', [
            'enrollment_application_id' => $application->id,
            'type' => 'Enrollment Application Update',
            'status' => 'captured',
        ]);
        Mail::assertSent(EnrollmentApplicationRejected::class, fn (EnrollmentApplicationRejected $mail): bool => $mail->hasTo($application->email));

        $message = (new EnrollmentApplicationRejected($application))->render();
        $this->assertStringContainsString('Please submit a clear photo.', $message);
        $this->assertStringNotContainsString("<script>alert('x')</script>", $message);
        $this->assertStringContainsString('You may submit a new application', $message);
    }

    public function test_email_failure_does_not_undo_an_application_rejection(): void
    {
        $application = $this->submitPublicApplication();
        $admin = User::factory()->create(['role' => 'admin']);
        config(['mail.default' => 'missing-mailer']);

        $this->actingAs($admin)
            ->post('/admin/enrollment-applications/'.$application->application_number.'/process', [
                'action' => 'reject',
                'rejection_reason' => 'Please upload a clearer photo.',
            ])
            ->assertRedirect()
            ->assertSessionHas('warning');

        $this->assertDatabaseHas('enrollment_applications', [
            'id' => $application->id,
            'status' => 'rejected',
            'rejection_reason' => 'Please upload a clearer photo.',
        ]);
        $this->assertDatabaseHas('email_histories', [
            'enrollment_application_id' => $application->id,
            'status' => 'failed',
        ]);
        $this->assertDatabaseCount('students', 0);

        config(['mail.default' => 'array']);
        $this->post('/admin/enrollment-applications/'.$application->application_number.'/resend-email')
            ->assertRedirect()
            ->assertSessionHas('warning', 'The email was captured by the configured test mailer, not delivered to the applicant.');

        $this->assertDatabaseCount('email_histories', 2);
        $this->get('/admin/enrollment-applications/'.$application->application_number)
            ->assertOk()
            ->assertSee('Resend Rejection Email');
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
        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.transport' => 'smtp',
            'mail.mailers.smtp.host' => 'smtp.example.test',
        ]);
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
            'error_message' => 'Mail transport failed. Check the configured SMTP host, port, encryption, and provider credentials.',
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

    public function test_mailtrap_sandbox_captures_email_without_claiming_it_was_delivered(): void
    {
        $application = $this->submitPublicApplication();
        $admin = User::factory()->create(['role' => 'admin']);
        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.transport' => 'smtp',
            'mail.mailers.smtp.host' => 'sandbox.smtp.mailtrap.io',
        ]);
        Mail::fake();

        $this->actingAs($admin)
            ->post('/admin/enrollment-applications/'.$application->application_number.'/process', ['action' => 'approve'])
            ->assertSessionHas('warning', 'The email was captured by the configured test mailer, not delivered to the applicant. Configure a live sending mail service to deliver it.');

        $this->get('/admin/enrollment-applications/'.$application->application_number)
            ->assertOk()
            ->assertSee('Captured (not delivered)')
            ->assertSee('Test mailers only capture messages and do not deliver them.');

        $this->assertDatabaseHas('email_histories', [
            'enrollment_application_id' => $application->id,
            'status' => 'captured',
            'error_message' => 'Configured test mailer captures messages but does not deliver them to the recipient.',
            'sent_at' => null,
        ]);
        Mail::assertSent(EnrollmentApplicationApproved::class);
    }

    private function makeCatalog(): array
    {
        $program = Program::create([
            'code' => 'BSIT',
            'name' => 'BS Information Technology',
        ]);
        $subject = Course::create([
            'code' => 'IT101',
            'title' => 'Introduction to Computing',
            'units' => 3,
            'year_level' => 1,
            'semester' => '1st',
            'program_id' => $program->id,
        ]);

        return [$program, $subject];
    }

    private function submitStepsOneToThree(Program $program): void
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
            'program_id' => $program->id,
            'student_type' => 'New Student',
            'year_level' => 1,
            'school_year' => '2026-2027',
            'semester' => '1st',
        ])->assertRedirect('/enrollment?step=4');
    }

    private function submitPublicApplication(): EnrollmentApplication
    {
        Storage::fake('local');
        [$program, $subject] = $this->makeCatalog();
        $this->submitStepsOneToThree($program);
        $this->get('/enrollment?step=4');
        $this->post('/enrollment/step/4', ['selected_subject_ids' => [$subject->id]]);
        $submissionToken = $this->app['session.store']->get('enrollment_submission_token');
        $this->post('/enrollment/submit', [
            'submission_token' => $submissionToken,
            ...$this->requirementUploads(),
        ])->assertRedirect();

        return EnrollmentApplication::firstOrFail();
    }

    /**
     * @return array{requirements: array<string, UploadedFile>}
     */
    private function requirementUploads(string $studentType = 'New Student'): array
    {
        $uploads = [];
        foreach (config('enrollment.requirements.'.$studentType) as $key => $label) {
            $uploads[$key] = UploadedFile::fake()->create($key.'.pdf', 100, 'application/pdf');
        }

        return ['requirements' => $uploads];
    }
}
