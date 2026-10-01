<?php

namespace App\Http\Controllers;

use App\Http\Requests\EnrollmentStepRequest;
use App\Http\Requests\StoreEnrollmentApplicationRequest;
use App\Models\Course;
use App\Models\Department;
use App\Models\EnrollmentApplication;
use App\Models\Program;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;

class EnrollmentApplicationController extends Controller
{
    public function create(Request $request): View|RedirectResponse
    {
        $step = $request->integer('step', 1);
        if ($step < 1 || $step > 5) {
            return redirect()->route('enrollment.create');
        }

        $draft = $request->session()->get('enrollment_draft', []);
        if (! $request->session()->has('enrollment_submission_token')) {
            $request->session()->put('enrollment_submission_token', (string) Str::uuid());
        }
        $requiredFields = [1 => ['first_name', 'last_name', 'birth_date', 'gender'], 2 => ['email', 'contact_number', 'barangay', 'city', 'province'], 3 => ['department_id', 'program_id', 'student_type', 'year_level', 'school_year', 'semester'], 4 => ['selected_subject_ids']];
        foreach ($requiredFields as $requiredStep => $fields) {
            if ($step === 5 && collect($fields)->contains(fn (string $field): bool => ! array_key_exists($field, $draft))) {
                return redirect()->route('enrollment.create', ['step' => $requiredStep]);
            }
        }

        $departments = Department::query()->where('status', 'active')->orderBy('name')->get();
        $programs = Program::query()->where('status', 'active')->with('department')->orderBy('name')->get();
        $subjects = collect();

        if ($step >= 4 && isset($draft['program_id'], $draft['year_level'], $draft['semester'])) {
            $subjects = Course::query()
                ->where('program_id', $draft['program_id'])
                ->where('year_level', $draft['year_level'])
                ->where('semester', $draft['semester'])
                ->where('status', 'active')
                ->orderBy('code')
                ->get();

            if ($step === 4 && ! isset($draft['selected_subject_ids'])) {
                $draft['selected_subject_ids'] = $subjects->pluck('id')->all();
                $request->session()->put('enrollment_draft', $draft);
            }

            if ($step === 5) {
                $subjects = $subjects->whereIn('id', $draft['selected_subject_ids'] ?? [])->values();
            }
        }

        return view('enrollment.create', compact('step', 'draft', 'departments', 'programs', 'subjects'));
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
            $request->session()->forget(['enrollment_draft', 'enrollment_submission_token']);

            return redirect()->route('enrollment.success', $existingApplication);
        }

        $subjectIds = array_values(array_unique(array_map('intval', $data['selected_subject_ids'])));
        unset($data['selected_subject_ids'], $data['submission_token']);

        try {
            $application = DB::transaction(function () use ($data, $subjectIds, $submissionToken): EnrollmentApplication {
                $application = EnrollmentApplication::create([
                    ...$data,
                    'submission_token' => $submissionToken,
                    'status' => 'pending',
                    'submitted_at' => now(),
                ]);
                $application->update([
                    'application_number' => sprintf('ENR-%s-%05d', now()->format('Y'), $application->id),
                ]);
                $application->subjects()->sync($subjectIds);

                return $application;
            });
        } catch (QueryException $exception) {
            $application = EnrollmentApplication::query()->where('submission_token', $submissionToken)->first();
            if (! $application) {
                Log::error('Enrollment application submission failed.', ['exception' => $exception]);
                throw $exception;
            }
        }

        $request->session()->forget(['enrollment_draft', 'enrollment_submission_token']);

        return redirect()->route('enrollment.success', $application);
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
}
