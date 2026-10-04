<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Program;
use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class StudentController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $status = $request->query('status');
        $nameSearchTerms = array_values(array_filter(preg_split('/\s+/', $search) ?: []));
        $students = Student::query()
            ->with('program')
            ->when($search !== '', function ($query) use ($search, $nameSearchTerms): void {
                $query->where(function ($students) use ($search, $nameSearchTerms): void {
                    $students->where('student_number', 'like', '%'.$search.'%')
                        ->orWhere('email', 'like', '%'.$search.'%')
                        ->orWhere(function ($names) use ($nameSearchTerms): void {
                            foreach ($nameSearchTerms as $searchTerm) {
                                $names->where(function ($name) use ($searchTerm): void {
                                    $name->where('first_name', 'like', '%'.$searchTerm.'%')
                                        ->orWhere('middle_name', 'like', '%'.$searchTerm.'%')
                                        ->orWhere('last_name', 'like', '%'.$searchTerm.'%');
                                });
                            }
                        });
                });
            })
            ->when(in_array($status, ['active', 'inactive', 'dropped'], true), fn ($query) => $query->where('status', $status))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('students.index', compact('students', 'search', 'status'));
    }

    public function show(Student $student): View
    {
        $student->load('program', 'courses', 'enrollments.program', 'enrollments.courses', 'enrollmentApplications.program', 'enrollmentApplications.documents');
        $enrollmentCourseIds = $student->enrollments
            ->flatMap(fn (Enrollment $enrollment) => $enrollment->courses->modelKeys())
            ->unique();
        $otherCourses = $student->courses
            ->reject(fn (Course $course): bool => $enrollmentCourseIds->contains($course->getKey()))
            ->values();

        $availableCourses = Course::query()
            ->where('program_id', $student->program_id)
            ->where('status', 'active')
            ->whereDoesntHave('students', fn ($query) => $query->whereKey($student->getKey()))
            ->orderBy('year_level')
            ->orderBy('code')
            ->get();

        $gradeTrackingAvailable = Schema::hasColumn('enrollment_course', 'grade');

        return view('students.show', compact('student', 'availableCourses', 'otherCourses', 'gradeTrackingAvailable'));
    }

    public function edit(Student $student): View
    {
        $programs = Program::query()
            ->where('status', 'active')
            ->orWhereKey($student->program_id)
            ->orderBy('name')
            ->get();

        return view('students.edit', compact('student', 'programs'));
    }

    public function update(Request $request, Student $student): RedirectResponse
    {
        $request->merge([
            'email' => mb_strtolower(trim((string) $request->input('email', ''))),
        ]);

        $data = $request->validate([
            'student_number' => ['required', 'string', 'max:20', Rule::unique('students', 'student_number')->ignore($student->id)],
            'first_name' => 'required|string|max:60',
            'middle_name' => 'nullable|string|max:60',
            'last_name' => 'required|string|max:60',
            'suffix' => 'nullable|string|max:20',
            'email' => ['required', 'email', Rule::unique('students', 'email')->ignore($student->id), Rule::unique('users', 'email')->ignore($student->user_id)],
            'contact_number' => 'nullable|string|max:30',
            'address' => 'nullable|string|max:2000',
            'birth_date' => 'required|date|before:today',
            'year_level' => 'required|integer|between:1,4',
            'program_id' => 'required|exists:programs,id',
            'status' => ['required', Rule::in(['active', 'inactive', 'dropped'])],
        ]);

        $academicInformationChanged = (int) $student->program_id !== (int) $data['program_id']
            || (int) $student->year_level !== (int) $data['year_level'];
        $hasAcademicRecords = $student->enrollments()->exists() || $student->courses()->exists();

        if ($academicInformationChanged && $hasAcademicRecords) {
            throw ValidationException::withMessages([
                'program_id' => 'Program and year level cannot be changed while academic records exist. Preserve historical records and update the student through a valid enrollment workflow.',
            ]);
        }

        if ($academicInformationChanged && ! Program::query()->whereKey($data['program_id'])->where('status', 'active')->exists()) {
            throw ValidationException::withMessages([
                'program_id' => 'Students can only be transferred to an active academic program.',
            ]);
        }

        DB::transaction(function () use ($data, $student): void {
            $student->update($data);
            $student->user?->update([
                'name' => $student->fresh()->full_name,
                'email' => $data['email'],
            ]);
        });

        return redirect()->route('students.index')
            ->with('success', 'Student updated successfully.');
    }

    public function enroll(Request $request, Student $student): RedirectResponse
    {
        $data = $request->validate([
            'course_id' => [
                'required',
                Rule::exists('courses', 'id')
                    ->where('program_id', $student->program_id)
                    ->where('status', 'active'),
            ],
        ]);

        if ($student->courses()->whereKey($data['course_id'])->exists()) {
            return back()
                ->withErrors(['course_id' => 'This student is already enrolled in that course.'])
                ->withInput();
        }

        $student->courses()->attach($data['course_id']);

        return back()->with('success', 'Subject enrolled successfully!');
    }

    public function updateEnrollmentGrade(Request $request, Student $student, Enrollment $enrollment, Course $course): RedirectResponse
    {
        abort_unless(
            Schema::hasColumn('enrollment_course', 'grade'),
            503,
            'Grade editing is temporarily unavailable until the enrollment grade migration has been applied.',
        );

        $data = $request->validate([
            'grade' => ['nullable', 'string', 'max:10', 'numeric', 'decimal:0,2'],
        ]);

        $enrollment = $student->enrollments()->findOrFail($enrollment->id);
        abort_unless($enrollment->courses()->whereKey($course->id)->exists(), 404);

        $enrollment->courses()->updateExistingPivot($course->id, [
            'grade' => $data['grade'] ?? null,
        ]);

        return back()->with('success', 'Enrollment grade updated successfully.');
    }
}
