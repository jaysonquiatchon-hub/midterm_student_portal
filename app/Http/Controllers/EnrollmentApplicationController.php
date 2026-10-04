<?php

namespace App\Http\Controllers;

use App\Http\Requests\EnrollmentStepRequest;
use App\Http\Requests\StoreEnrollmentApplicationRequest;
use App\Models\Course;
use App\Models\EnrollmentApplication;
use App\Models\Program;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use RuntimeException;
use Throwable;

class EnrollmentApplicationController extends Controller
{
    public function create(Request $request): View|RedirectResponse
    {
        $step = $request->integer('step', 1);
        if ($step < 1 || $step > 5) {
            return redirect()->route('enrollment.create');
        }

        $submissionToken = $request->session()->get('enrollment_submission_token');
        if (
            $submissionToken
            && EnrollmentApplication::query()->where('submission_token', $submissionToken)->exists()
        ) {
            $request->session()->forget(['enrollment_draft', 'enrollment_submission_token']);
        }

        $draft = $request->session()->get('enrollment_draft', []);
        if ($request->user()?->role === 'student' && $request->user()->student) {
            $student = $request->user()->student->loadMissing('program');
            $draft = array_merge([
                'first_name' => $student->first_name,
                'last_name' => $student->last_name,
                'birth_date' => $student->birth_date?->toDateString(),
                'email' => $student->email,
                'program_id' => $student->program_id,
                'year_level' => $student->year_level,
            ], $draft);
        }

        if (! $request->session()->has('enrollment_submission_token')) {
            $request->session()->put('enrollment_submission_token', (string) Str::uuid());
        }

        $requiredFields = [
            1 => ['first_name', 'last_name', 'birth_date', 'gender'],
            2 => ['email', 'contact_number', 'barangay', 'city', 'province'],
            3 => ['program_id', 'student_type', 'year_level', 'school_year', 'semester'],
        ];

        foreach ($requiredFields as $requiredStep => $fields) {
            if ($step >= 4 && collect($fields)->contains(fn (string $field): bool => ! array_key_exists($field, $draft))) {
                return redirect()->route('enrollment.create', ['step' => $requiredStep]);
            }
        }

        $programs = Program::query()->where('status', 'active')->orderBy('name')->get();
        $subjects = collect();
        if ($step >= 4) {
            $subjectsQuery = Course::query()
                ->where('program_id', $draft['program_id'])
                ->where('year_level', $draft['year_level'])
                ->where('semester', $draft['semester'])
                ->where('status', 'active')
                ->orderBy('code');

            if ($step === 4) {
                $subjects = $subjectsQuery->get();
            } else {
                $selectedSubjectIds = $draft['selected_subject_ids'] ?? [];
                $subjects = $subjectsQuery->whereIn('id', $selectedSubjectIds)->get();
                if ($selectedSubjectIds === [] || $subjects->count() !== count($selectedSubjectIds)) {
                    return redirect()->route('enrollment.create', ['step' => 4])
                        ->withErrors(['selected_subject_ids' => 'Select at least one active subject for your program and term.']);
                }
            }
        }

        $documentRequirements = config('enrollment.requirements.'.($draft['student_type'] ?? ''), []);

        return view('enrollment.create', compact('step', 'draft', 'programs', 'subjects', 'documentRequirements'));
    }

    public function saveStep(EnrollmentStepRequest $request, int $step): RedirectResponse
    {
        $draft = $request->session()->get('enrollment_draft', []);
        $validated = $request->validated();

        if ($step === 3) {
            unset($draft['selected_subject_ids']);
        }

        $request->session()->put('enrollment_draft', array_merge($draft, $validated));

        return redirect()->route('enrollment.create', ['step' => min($step + 1, 5)]);
    }

    public function submit(StoreEnrollmentApplicationRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $submissionToken = $data['submission_token'];

        $existingApplication = EnrollmentApplication::query()->where('submission_token', $submissionToken)->first();
        if ($existingApplication) {
            return redirect()->route('enrollment.success', $existingApplication);
        }

        if ($request->session()->get('enrollment_submission_token') !== $submissionToken) {
            throw ValidationException::withMessages([
                'submission_token' => 'Your application session has expired. Please start a new application.',
            ]);
        }

        $applicantUser = $request->user()?->role === 'student' ? $request->user() : null;
        if ($applicantUser && ! $applicantUser->student) {
            throw ValidationException::withMessages([
                'email' => 'Your student account must be linked to a student profile before applying.',
            ]);
        }

        if ($applicantUser && mb_strtolower($data['email']) !== mb_strtolower($applicantUser->email)) {
            throw ValidationException::withMessages([
                'email' => 'Use the email address associated with your student account.',
            ]);
        }

        $uploadedDocuments = $data['requirements'] ?? [];
        $subjectIds = array_values(array_unique(array_map('intval', $data['selected_subject_ids'])));
        unset($data['submission_token'], $data['requirements'], $data['selected_subject_ids']);

        $storedPaths = [];
        try {
            $application = DB::transaction(function () use ($data, $submissionToken, $applicantUser, $uploadedDocuments, $subjectIds, &$storedPaths): EnrollmentApplication {
                $application = EnrollmentApplication::create([
                    ...$data,
                    'applicant_user_id' => $applicantUser?->id,
                    'student_id' => $applicantUser?->student?->id,
                    'submission_token' => $submissionToken,
                    'status' => 'pending',
                    'submitted_at' => now(),
                ]);
                $application->update([
                    'application_number' => sprintf('ENR-%s-%05d', now()->format('Y'), $application->id),
                ]);
                $application->subjects()->sync($subjectIds);

                foreach (config('enrollment.requirements.'.$data['student_type'], []) as $key => $label) {
                    if (! isset($uploadedDocuments[$key])) {
                        continue;
                    }

                    $file = $uploadedDocuments[$key];
                    $path = $file->store("enrollment-requirements/{$application->id}", 'local');

                    if ($path === false) {
                        throw new RuntimeException('Failed to store an enrollment requirement document.');
                    }

                    $storedPaths[] = $path;
                    $originalName = basename(str_replace('\\', '/', $file->getClientOriginalName()));
                    $originalName = preg_replace('/[\x00-\x1F\x7F]/u', '', $originalName) ?: 'document';
                    $application->documents()->create([
                        'requirement_key' => $key,
                        'label' => $label,
                        'file_path' => $path,
                        'original_name' => mb_substr($originalName, 0, 255),
                        'mime_type' => $file->getMimeType() ?? 'application/octet-stream',
                    ]);
                }

                return $application;
            });
        } catch (QueryException $exception) {
            $this->discardStoredDocuments($storedPaths);
            $application = EnrollmentApplication::query()->where('submission_token', $submissionToken)->first();
            if (! $application) {
                Log::error('Enrollment application submission failed.', ['exception' => $exception]);
                throw $exception;
            }
        } catch (Throwable $exception) {
            $this->discardStoredDocuments($storedPaths);
            throw $exception;
        }

        return redirect()->route('enrollment.success', $application);
    }

    private function discardStoredDocuments(array $paths): void
    {
        if ($paths !== [] && ! Storage::disk('local')->delete($paths)) {
            Log::error('Enrollment requirement files could not be removed after a failed submission.', [
                'paths' => $paths,
            ]);
        }
    }

    public function cancel(Request $request): RedirectResponse
    {
        $request->session()->forget('enrollment_draft');
        $request->session()->forget('enrollment_submission_token');

        return redirect()->route('enrollment.create');
    }

    public function success(EnrollmentApplication $application): View
    {
        return view('enrollment.success', compact('application'));
    }

    public function status(): View
    {
        return view('enrollment.status', ['application' => null]);
    }

    public function checkStatus(Request $request): View
    {
        $data = $request->validate([
            'application_number' => ['required', 'string', 'max:40'],
            'email' => ['required', 'email:rfc', 'max:255'],
        ]);

        $application = EnrollmentApplication::query()
            ->where('application_number', trim($data['application_number']))
            ->whereRaw('LOWER(email) = ?', [mb_strtolower(trim($data['email']))])
            ->first();

        if (! $application) {
            throw ValidationException::withMessages([
                'application_number' => 'The application number and email address did not match an application.',
            ]);
        }

        return view('enrollment.status', compact('application'));
    }
}
