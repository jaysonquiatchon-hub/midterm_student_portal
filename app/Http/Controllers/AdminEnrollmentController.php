<?php

namespace App\Http\Controllers;

use App\Mail\EnrollmentConfirmed;
use App\Models\Enrollment;
use App\Models\Program;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class AdminEnrollmentController extends Controller
{
    public function index(Request $request): View
    {
        $query = Enrollment::query()->with(['student', 'program'])->latest();
        $status = $request->query('status');
        $search = trim((string) $request->query('search', ''));

        if (in_array($status, ['pending', 'processing', 'enrolled', 'rejected'], true)) {
            $query->where('status', $status);
        }

        if ($search !== '') {
            $query->where(function ($query) use ($search): void {
                $query->where('reference_number', 'like', '%'.$search.'%')
                    ->orWhereHas('student', function ($studentQuery) use ($search): void {
                        $studentQuery->where('student_number', 'like', '%'.$search.'%')
                            ->orWhere('first_name', 'like', '%'.$search.'%')
                            ->orWhere('last_name', 'like', '%'.$search.'%');
                    });
            });
        }

        return view('admin.enrollments.index', [
            'enrollments' => $query->paginate(15)->withQueryString(),
            'status' => $status,
            'search' => $search,
        ]);
    }

    public function show(Enrollment $enrollment): View
    {
        $enrollment->load(['student.program', 'program', 'courses']);

        return view('admin.enrollments.show', [
            'enrollment' => $enrollment,
            'programs' => Program::query()->with('courses')->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Enrollment $enrollment): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['processing', 'enrolled', 'rejected'])],
        ]);

        $this->assertTransitionAllowed($enrollment->status, $data['status']);

        $data += $request->validate([
            'program_id' => ['required_if:status,enrolled', 'nullable', 'exists:programs,id'],
            'course_ids' => ['required_if:status,enrolled', 'array', 'min:1'],
            'course_ids.*' => [
                'integer',
                Rule::exists('courses', 'id')->where(fn ($query) => $query
                    ->where('program_id', $request->input('program_id'))),
            ],
            'rejection_reason' => ['required_if:status,rejected', 'nullable', 'string', 'max:2000'],
        ]);

        DB::transaction(function () use ($enrollment, $data): void {
            $lockedEnrollment = Enrollment::query()->lockForUpdate()->findOrFail($enrollment->id);
            $this->assertTransitionAllowed($lockedEnrollment->status, $data['status']);

            $lockedEnrollment->update([
                'status' => $data['status'],
                'processed_at' => now(),
                'program_id' => $data['status'] === 'enrolled' ? $data['program_id'] : null,
                'rejection_reason' => $data['status'] === 'rejected' ? $data['rejection_reason'] : null,
            ]);

            if ($data['status'] === 'enrolled') {
                $courseIds = array_values(array_unique(array_map('intval', $data['course_ids'])));
                $lockedEnrollment->courses()->sync($courseIds);
                $lockedEnrollment->student->update(['program_id' => $data['program_id']]);
                $studentCourses = $lockedEnrollment->student->courses();
                $studentCourses->syncWithoutDetaching(
                    collect($courseIds)->mapWithKeys(fn (int $courseId): array => [
                        $courseId => ['enrollment_id' => $enrollment->id],
                    ])->all(),
                );
                foreach ($courseIds as $courseId) {
                    $studentCourses->updateExistingPivot($courseId, ['enrollment_id' => $lockedEnrollment->id]);
                }
            }
        });

        if ($data['status'] === 'enrolled') {
            try {
                Mail::to($enrollment->student->email)->send(
                    new EnrollmentConfirmed($enrollment->fresh(['student', 'program'])),
                );
            } catch (Throwable $exception) {
                Log::error('Enrollment confirmation email failed.', [
                    'enrollment_id' => $enrollment->id,
                    'exception' => $exception,
                ]);

                return redirect()->route('admin.enrollments.show', $enrollment)
                    ->with('success', 'Enrollment processed successfully.')
                    ->with('warning', 'The enrollment confirmation email could not be sent.');
            }
        }

        return redirect()->route('admin.enrollments.show', $enrollment)
            ->with('success', 'Enrollment updated successfully.');
    }

    private function assertTransitionAllowed(string $current, string $next): void
    {
        $isAllowed = match ($current) {
            'pending' => in_array($next, ['processing', 'rejected'], true),
            'processing' => in_array($next, ['enrolled', 'rejected'], true),
            default => false,
        };

        if (! $isAllowed) {
            throw ValidationException::withMessages([
                'status' => 'This enrollment cannot be moved from '.$current.' to '.$next.'.',
            ]);
        }
    }
}
