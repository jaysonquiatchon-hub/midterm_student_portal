<?php

namespace App\Http\Controllers;

use App\Models\Enrollment;
use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class StudentPortalController extends Controller
{
    public function index(Request $request): View
    {
        $student = $this->studentFor($request);
        $student->load(['program', 'courses', 'enrollments.program', 'enrollments.courses']);

        return view('student.dashboard', compact('student'));
    }

    public function createEnrollment(Request $request): View
    {
        $this->studentFor($request);

        return view('student.enrollments.create', [
            'academicYear' => now()->year.'-'.(now()->year + 1),
        ]);
    }

    public function storeEnrollment(Request $request): RedirectResponse
    {
        $student = $this->studentFor($request);
        $data = $request->validate([
            'academic_year' => ['required', 'regex:/^\d{4}-\d{4}$/'],
            'term' => [
                'required',
                Rule::in(['1st', '2nd', 'Summer']),
                Rule::unique('enrollments', 'term')->where(fn ($query) => $query
                    ->where('student_id', $student->id)
                    ->where('academic_year', $request->input('academic_year'))),
            ],
        ]);

        $enrollment = DB::transaction(function () use ($student, $data): Enrollment {
            $enrollment = $student->enrollments()->create([
                'academic_year' => $data['academic_year'],
                'term' => $data['term'],
                'status' => 'pending',
            ]);
            $enrollment->update([
                'reference_number' => sprintf('ENR-%s-%06d', now()->format('Y'), $enrollment->id),
            ]);

            return $enrollment;
        });

        return redirect()->route('student.enrollments.show', $enrollment)
            ->with('success', 'Enrollment request submitted. Keep your reference number for tracking.');
    }

    public function showEnrollment(Request $request, Enrollment $enrollment): View
    {
        $student = $this->studentFor($request);
        $enrollment = $student->enrollments()
            ->with(['program', 'courses'])
            ->findOrFail($enrollment->id);

        return view('student.enrollments.show', compact('student', 'enrollment'));
    }

    private function studentFor(Request $request): Student
    {
        $student = $request->user()->student;
        abort_unless($student, 403);

        return $student;
    }
}
