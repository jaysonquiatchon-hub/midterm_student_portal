<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProcessEnrollmentApplicationRequest;
use App\Http\Requests\UpdateEnrollmentApplicationRequest;
use App\Mail\EnrollmentApplicationApproved;
use App\Models\EmailHistory;
use App\Models\Enrollment;
use App\Models\EnrollmentApplication;
use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class AdminEnrollmentApplicationController extends Controller
{
    public function index(Request $request, bool $history = false): View
    {
        $query = EnrollmentApplication::query()->with(['department', 'program', 'student'])->latest('submitted_at');
        $status = $request->query('status', $history ? null : 'pending');
        $departmentId = $request->integer('department_id') ?: null;
        $programId = $request->integer('program_id') ?: null;
        $search = trim((string) $request->query('search', ''));

        if ($status && in_array($status, ['pending', 'under_review', 'approved', 'rejected'], true)) {
            $query->where('status', $status);
        }
        if ($departmentId) {
            $query->where('department_id', $departmentId);
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
            'departments' => \App\Models\Department::query()->orderBy('name')->get(),
            'programs' => \App\Models\Program::query()->orderBy('name')->get(),
            'status' => $status,
            'departmentId' => $departmentId,
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
            'department', 'program', 'subjects', 'student', 'enrollment',
            'approver', 'rejector', 'emailHistories' => fn ($query) => $query->latest(),
        ]);

        return view('admin.enrollment-applications.show', compact('application'));
    }

    public function edit(EnrollmentApplication $application): View
    {
        abort_unless($application->status === 'pending', 403);
        $application->load(['department', 'program', 'subjects']);

        return view('admin.enrollment-applications.edit', [
            'application' => $application,
            'departments' => \App\Models\Department::query()->where('status', 'active')->orderBy('name')->get(),
            'programs' => \App\Models\Program::query()->where('status', 'active')->orderBy('name')->get(),
            'subjects' => \App\Models\Course::query()
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
            $application->update(['status' => 'under_review']);

            return redirect()->route('admin.enrollment-applications.show', $application)
                ->with('success', 'Application moved to Under Review.');
        }

        if ($data['action'] === 'reject') {
            $application->update([
                'status' => 'rejected',
                'rejection_reason' => $data['rejection_reason'],
                'rejected_by' => $request->user()->id,
                'rejected_at' => now(),
            ]);

            return redirect()->route('admin.enrollment-applications.show', $application)
                ->with('success', 'Application rejected. No student or enrollment record was created.');
        }

        $approvedApplication = DB::transaction(function () use ($application, $request): EnrollmentApplication {
            $lockedApplication = EnrollmentApplication::query()->lockForUpdate()->findOrFail($application->id);
            if (! in_array($lockedApplication->status, ['pending', 'under_review'], true)) {
                throw ValidationException::withMessages([
                    'action' => 'This application has already been approved or reviewed.',
                ]);
            }

            $student = Student::query()
                ->whereRaw('LOWER(email) = ?', [mb_strtolower($lockedApplication->email)])
                ->lockForUpdate()
                ->first();

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

            $studentId = $student?->student_id;
            if (! $student) {
                $student = Student::create([
                    'student_number' => 'APP-'.$lockedApplication->id,
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
            }

            $studentId ??= sprintf('%s-%05d', now()->format('Y'), $student->id);
            if (Student::query()->where('student_id', $studentId)->whereKeyNot($student->id)->exists()) {
                throw ValidationException::withMessages(['action' => 'Could not generate a unique Student ID. Please retry.']);
            }

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
            $enrollment->courses()->sync($lockedApplication->subjects()->pluck('courses.id')->all());
            $student->courses()->syncWithoutDetaching(
                $lockedApplication->subjects->mapWithKeys(fn ($subject): array => [
                    $subject->id => ['enrollment_id' => $enrollment->id],
                ])->all(),
            );

            $lockedApplication->update([
                'student_id' => $student->id,
                'status' => 'approved',
                'sample_username' => $studentId,
                'sample_password' => config('student_portal.enrollment_demo_password', 'Student@123'),
                'approved_by' => $request->user()->id,
                'approved_at' => now(),
            ]);

            return $lockedApplication->fresh(['student', 'department', 'program', 'enrollment']);
        });

        $history = $this->sendApprovalEmail($approvedApplication);
        $redirect = redirect()->route('admin.enrollment-applications.show', $approvedApplication)
            ->with('success', 'Application approved and student enrollment created.');

        if ($history->status !== 'sent') {
            $message = $history->status === 'logged'
                ? 'The message was written to the configured log mailer, not delivered to an inbox.'
                : 'The application is approved, but the confirmation email failed. Use Resend Email after checking mail settings.';

            return $redirect->with('warning', $message);
        }

        return $redirect;
    }

    public function resend(EnrollmentApplication $application): RedirectResponse
    {
        abort_unless($application->status === 'approved' && $application->student, 422);
        $history = $this->sendApprovalEmail($application->fresh(['student', 'department', 'program', 'enrollment']));

        return redirect()->route('admin.enrollment-applications.show', $application)
            ->with($history->status === 'sent' ? 'success' : 'warning', $history->status === 'sent'
                ? 'Confirmation email sent.'
                : ($history->status === 'logged' ? 'Message written to log; it was not delivered to an inbox.' : 'Email delivery failed again. Check the email history for details.'));
    }

    private function sendApprovalEmail(EnrollmentApplication $application): EmailHistory
    {
        $subject = 'Enrollment Confirmation';
        $status = 'failed';
        $errorMessage = null;
        $sentAt = null;

        try {
            Mail::to($application->email)->send(new EnrollmentApplicationApproved($application));
            if (config('mail.default') === 'log') {
                $status = 'logged';
                $errorMessage = 'Configured log mailer does not deliver email to an inbox.';
            } else {
                $status = 'sent';
                $sentAt = now();
            }
        } catch (Throwable $exception) {
            $errorMessage = $exception->getMessage();
            Log::error('Enrollment application confirmation email failed.', [
                'application_id' => $application->id,
                'recipient' => $application->email,
                'exception' => $exception,
            ]);
        }

        return $application->emailHistories()->create([
            'student_id' => $application->student_id,
            'enrollment_id' => $application->enrollment?->id,
            'recipient' => $application->email,
            'type' => 'Enrollment Confirmation',
            'subject' => $subject,
            'status' => $status,
            'error_message' => $errorMessage,
            'sent_at' => $sentAt,
        ]);
    }
}
