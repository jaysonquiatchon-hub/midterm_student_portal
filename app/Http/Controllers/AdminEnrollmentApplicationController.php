<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProcessEnrollmentApplicationRequest;
use App\Http\Requests\UpdateEnrollmentApplicationRequest;
use App\Mail\EnrollmentApplicationApproved;
use App\Mail\EnrollmentApplicationRejected;
use App\Models\Course;
use App\Models\EmailHistory;
use App\Models\Enrollment;
use App\Models\EnrollmentApplication;
use App\Models\Program;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class AdminEnrollmentApplicationController extends Controller
{
    public function index(Request $request, bool $history = false): View
    {
        $query = EnrollmentApplication::query()->with(['program', 'student'])->latest('submitted_at');
        $status = $request->query('status', $history ? null : 'pending');
        $programId = $request->integer('program_id') ?: null;
        $search = trim((string) $request->query('search', ''));

        if ($status && in_array($status, ['pending', 'under_review', 'approved', 'rejected'], true)) {
            $query->where('status', $status);
        }
        if ($programId) {
            $query->where('program_id', $programId);
        }
        if ($search !== '') {
            $query->where(function ($applications) use ($search): void {
                $applications->where('application_number', 'like', '%'.$search.'%')
                    ->orWhere('email', 'like', '%'.$search.'%')
                    ->orWhere('first_name', 'like', '%'.$search.'%')
                    ->orWhere('last_name', 'like', '%'.$search.'%');
            });
        }

        return view('admin.enrollment-applications.index', [
            'applications' => $query->paginate(15)->withQueryString(),
            'programs' => Program::query()->orderBy('name')->get(),
            'status' => $status,
            'programId' => $programId,
            'search' => $search,
            'history' => $history,
        ]);
    }

    public function history(Request $request): View
    {
        return $this->index($request, true);
    }

    public function show(EnrollmentApplication $application): View
    {
        $application->load([
            'program', 'subjects', 'student', 'applicantUser', 'documents', 'enrollment',
            'approver', 'rejector', 'emailHistories' => fn ($query) => $query->latest(),
        ]);

        return view('admin.enrollment-applications.show', compact('application'));
    }

    public function edit(EnrollmentApplication $application): View
    {
        abort_unless($application->status === 'pending', 403);
        $application->load(['program', 'subjects']);

        return view('admin.enrollment-applications.edit', [
            'application' => $application,
            'programs' => Program::query()->where('status', 'active')->orderBy('name')->get(),
            'subjects' => Course::query()
                ->where('program_id', $application->program_id)
                ->where('year_level', $application->year_level)
                ->where('semester', $application->semester)
                ->where('status', 'active')
                ->orderBy('code')->get(),
        ]);
    }

    public function update(UpdateEnrollmentApplicationRequest $request, EnrollmentApplication $application): RedirectResponse
    {
        $data = $request->validated();
        if (
            $application->applicant_user_id
            && mb_strtolower($data['email']) !== mb_strtolower($application->applicantUser()->value('email'))
        ) {
            throw ValidationException::withMessages([
                'email' => 'The email address must remain the one associated with the applicant account.',
            ]);
        }

        $subjectIds = array_values(array_unique(array_map('intval', $data['selected_subject_ids'])));
        unset($data['selected_subject_ids']);

        DB::transaction(function () use ($application, $data, $subjectIds): void {
            $lockedApplication = EnrollmentApplication::query()->lockForUpdate()->findOrFail($application->id);
            if ($lockedApplication->status !== 'pending') {
                throw ValidationException::withMessages([
                    'application' => 'Only Pending applications can be edited.',
                ]);
            }
            $lockedApplication->update($data);
            $lockedApplication->subjects()->sync($subjectIds);
        });

        return redirect()->route('admin.enrollment-applications.show', $application)
            ->with('success', 'Pending application updated.');
    }

    public function process(ProcessEnrollmentApplicationRequest $request, EnrollmentApplication $application): RedirectResponse
    {
        $data = $request->validated();

        if (! in_array($application->status, ['pending', 'under_review'], true)) {
            throw ValidationException::withMessages([
                'action' => 'This application has already been reviewed and cannot be processed again.',
            ]);
        }

        if ($data['action'] === 'under_review') {
            DB::transaction(function () use ($application): void {
                $lockedApplication = EnrollmentApplication::query()->lockForUpdate()->findOrFail($application->id);
                if (! in_array($lockedApplication->status, ['pending', 'under_review'], true)) {
                    throw ValidationException::withMessages([
                        'action' => 'This application has already been reviewed and cannot be updated.',
                    ]);
                }

                $lockedApplication->update(['status' => 'under_review']);
            });

            return redirect()->route('admin.enrollment-applications.show', $application)
                ->with('success', 'Application moved to Under Review.');
        }

        if ($data['action'] === 'reject') {
            $rejectedApplication = DB::transaction(function () use ($application, $data, $request): EnrollmentApplication {
                $lockedApplication = EnrollmentApplication::query()->lockForUpdate()->findOrFail($application->id);
                if (! in_array($lockedApplication->status, ['pending', 'under_review'], true)) {
                    throw ValidationException::withMessages([
                        'action' => 'This application has already been reviewed and cannot be rejected.',
                    ]);
                }

                $lockedApplication->update([
                    'status' => 'rejected',
                    'rejection_reason' => $data['rejection_reason'],
                    'rejected_by' => $request->user()->id,
                    'rejected_at' => now(),
                ]);

                return $lockedApplication->fresh();
            });
            $history = $this->sendEnrollmentEmail(
                $rejectedApplication,
                new EnrollmentApplicationRejected($rejectedApplication),
                'Enrollment Application Update',
                'Enrollment Application Requires Changes',
            );
            $redirect = redirect()->route('admin.enrollment-applications.show', $rejectedApplication)
                ->with('success', 'Application rejected. No student or enrollment record was created.');

            if ($history->status !== 'sent') {
                return $redirect->with('warning', $history->status === 'logged'
                    ? 'The application was rejected, but the message was written to the log mailer and not delivered to an inbox.'
                    : ($history->status === 'captured'
                        ? 'The application was rejected, but the test mailer captured the message instead of delivering it.'
                        : 'The application was rejected, but the notification email failed. Check email history and mail settings.'));
            }

            return $redirect;
        }

        $approvedApplication = DB::transaction(function () use ($application, $request): EnrollmentApplication {
            $lockedApplication = EnrollmentApplication::query()->lockForUpdate()->findOrFail($application->id);
            if (! in_array($lockedApplication->status, ['pending', 'under_review'], true)) {
                throw ValidationException::withMessages([
                    'action' => 'This application has already been approved or reviewed.',
                ]);
            }

            $selectedSubjectIds = $lockedApplication->subjects()->pluck('courses.id')->map(fn ($id): int => (int) $id)->all();
            $eligibleSubjectIds = Course::query()
                ->whereIn('id', $selectedSubjectIds)
                ->where('program_id', $lockedApplication->program_id)
                ->where('year_level', $lockedApplication->year_level)
                ->where('semester', $lockedApplication->semester)
                ->where('status', 'active')
                ->lockForUpdate()
                ->pluck('id')
                ->map(fn ($id): int => (int) $id)
                ->all();
            sort($selectedSubjectIds);
            sort($eligibleSubjectIds);

            if ($selectedSubjectIds === [] || $selectedSubjectIds !== $eligibleSubjectIds) {
                throw ValidationException::withMessages([
                    'action' => 'The application subjects are no longer eligible for the selected program and term. Edit the application before approving it.',
                ]);
            }

            $student = Student::query()
                ->whereRaw('LOWER(email) = ?', [mb_strtolower($lockedApplication->email)])
                ->lockForUpdate()
                ->first();

            $user = User::query()
                ->whereRaw('LOWER(email) = ?', [mb_strtolower($lockedApplication->email)])
                ->lockForUpdate()
                ->first();

            if ($user && (! $student || (int) $student->user_id !== (int) $user->id || ! $user->isStudent())) {
                throw ValidationException::withMessages([
                    'action' => 'The applicant email is already linked to a different account. Resolve the existing account before approving this application.',
                ]);
            }

            if ($student && Enrollment::query()
                ->where('student_id', $student->id)
                ->where('academic_year', $lockedApplication->school_year)
                ->where('term', $lockedApplication->semester)
                ->where('status', 'enrolled')
                ->exists()) {
                throw ValidationException::withMessages([
                    'action' => 'This student already has an approved enrollment for this school year and semester.',
                ]);
            }

            if (! $student) {
                $student = Student::create([
                    'student_number' => 'TMP-'.Str::random(16),
                    'first_name' => $lockedApplication->first_name,
                    'middle_name' => $lockedApplication->middle_name,
                    'last_name' => $lockedApplication->last_name,
                    'suffix' => $lockedApplication->suffix,
                    'email' => $lockedApplication->email,
                    'birth_date' => $lockedApplication->birth_date,
                    'gender' => $lockedApplication->gender,
                    'civil_status' => $lockedApplication->civil_status,
                    'nationality' => $lockedApplication->nationality,
                    'year_level' => $lockedApplication->year_level,
                    'program_id' => $lockedApplication->program_id,
                    'contact_number' => $lockedApplication->contact_number,
                    'status' => 'active',
                ]);
                $computedStudentId = sprintf('%s-%05d', now()->format('Y'), $student->id);
                $student->update([
                    'student_id' => $computedStudentId,
                    'student_number' => $computedStudentId,
                ]);
            } else {
                $studentId = $student->student_id ?? sprintf('%s-%05d', now()->format('Y'), $student->id);

                $student->update([
                    'student_id' => $studentId,
                    'student_number' => $studentId,
                    'first_name' => $lockedApplication->first_name,
                    'middle_name' => $lockedApplication->middle_name,
                    'last_name' => $lockedApplication->last_name,
                    'suffix' => $lockedApplication->suffix,
                    'program_id' => $lockedApplication->program_id,
                    'year_level' => $lockedApplication->year_level,
                    'gender' => $lockedApplication->gender,
                    'civil_status' => $lockedApplication->civil_status,
                    'nationality' => $lockedApplication->nationality,
                    'contact_number' => $lockedApplication->contact_number,
                    'status' => 'active',
                ]);
            }

            $studentId = $student->student_id;

            $student->user?->update([
                'name' => $lockedApplication->full_name,
                'email' => $student->email,
            ]);

            $enrollment = Enrollment::create([
                'reference_number' => $lockedApplication->application_number,
                'student_id' => $student->id,
                'application_id' => $lockedApplication->id,
                'program_id' => $lockedApplication->program_id,
                'academic_year' => $lockedApplication->school_year,
                'term' => $lockedApplication->semester,
                'status' => 'enrolled',
                'processed_at' => now(),
            ]);

            $enrollment->courses()->sync($selectedSubjectIds);
            $student->courses()->syncWithoutDetaching(
                collect($selectedSubjectIds)->mapWithKeys(fn (int $subjectId): array => [
                    $subjectId => ['enrollment_id' => $enrollment->id],
                ])->all(),
            );

            $lockedApplication->update([
                'student_id' => $student->id,
                'status' => 'approved',
                'sample_username' => null,
                'sample_password' => null,
                'approved_by' => $request->user()->id,
                'approved_at' => now(),
            ]);

            return $lockedApplication->fresh(['student', 'program', 'enrollment']);
        });

        $history = $this->sendEnrollmentEmail(
            $approvedApplication,
            new EnrollmentApplicationApproved($approvedApplication),
            'Enrollment Confirmation',
            'Enrollment Confirmation',
        );
        $redirect = redirect()->route('admin.enrollment-applications.show', $approvedApplication)
            ->with('success', 'Application approved and student enrollment created.');

        if ($history->status !== 'sent') {
            $message = $history->status === 'logged'
                ? 'The message was written to the configured log mailer, not delivered to an inbox.'
                : ($history->status === 'captured'
                    ? 'The email was captured by the configured test mailer, not delivered to the applicant. Configure a live sending mail service to deliver it.'
                    : 'The application is approved, but email delivery failed. Check the mail host, port, encryption, and provider credentials before resending.');

            return $redirect->with('warning', $message);
        }

        return $redirect;
    }

    public function resend(EnrollmentApplication $application): RedirectResponse
    {
        if ($application->status === 'approved' && $application->student) {
            $freshApplication = $application->fresh(['student', 'program', 'enrollment']);
            $history = $this->sendEnrollmentEmail(
                $freshApplication,
                new EnrollmentApplicationApproved($freshApplication),
                'Enrollment Confirmation',
                'Enrollment Confirmation',
            );
            $sentMessage = 'Confirmation email sent.';
        } elseif ($application->status === 'rejected') {
            $freshApplication = $application->fresh();
            $history = $this->sendEnrollmentEmail(
                $freshApplication,
                new EnrollmentApplicationRejected($freshApplication),
                'Enrollment Application Update',
                'Enrollment Application Requires Changes',
            );
            $sentMessage = 'Rejection notification email sent.';
        } else {
            abort(422);
        }

        return redirect()->route('admin.enrollment-applications.show', $application)
            ->with(
                $history->status === 'sent' ? 'success' : 'warning',
                match ($history->status) {
                    'sent' => $sentMessage,
                    'logged' => 'Message written to log; it was not delivered to an inbox.',
                    'captured' => 'The email was captured by the configured test mailer, not delivered to the applicant.',
                    default => 'Email delivery failed. Check the mail settings and email history before trying again.',
                },
            );
    }

    private function sendEnrollmentEmail(
        EnrollmentApplication $application,
        Mailable $mailable,
        string $type,
        string $subject,
    ): EmailHistory {
        $status = 'failed';
        $errorMessage = null;
        $sentAt = null;

        try {
            Mail::to($application->email)->send($mailable);
            $mailer = config('mail.default');
            $transport = config("mail.mailers.{$mailer}.transport");
            $host = strtolower((string) config("mail.mailers.{$mailer}.host"));

            if ($transport === 'log') {
                $status = 'logged';
                $errorMessage = 'Configured log mailer does not deliver email to an inbox.';
            } elseif ($transport === 'array' || $host === 'sandbox.smtp.mailtrap.io') {
                $status = 'captured';
                $errorMessage = 'Configured test mailer captures messages but does not deliver them to the recipient.';
            } else {
                $status = 'sent';
                $sentAt = now();
            }
        } catch (Throwable $exception) {
            $errorMessage = 'Mail transport failed. Check the configured SMTP host, port, encryption, and provider credentials.';
            Log::error('Enrollment application email failed.', [
                'application_id' => $application->id,
                'recipient' => $application->email,
                'exception' => $exception,
            ]);
        }

        return $application->emailHistories()->create([
            'student_id' => $application->student_id,
            'enrollment_id' => $application->enrollment?->id,
            'recipient' => $application->email,
            'type' => $type,
            'subject' => $subject,
            'status' => $status,
            'error_message' => $errorMessage,
            'sent_at' => $sentAt,
        ]);
    }
}
