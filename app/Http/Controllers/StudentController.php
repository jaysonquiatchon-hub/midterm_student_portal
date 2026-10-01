<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Program;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class StudentController extends Controller
{
    public function index()
    {
        $students = Student::with('program')
            ->orderBy('last_name')
            ->paginate(10);

        return view('students.index', compact('students'));
    }

    public function create()
    {
        $programs = Program::orderBy('name')->get();

        return view('students.create', compact('programs'));
    }

    public function store(Request $request)
    {
        $request->merge(['email' => mb_strtolower(trim((string) $request->input('email', '')))]);
        $data = $request->validate([
            'student_number' => 'required|string|max:20|unique:students',
            'first_name' => 'required|string|max:60',
            'last_name' => 'required|string|max:60',
            'email' => ['required', 'email', Rule::unique('students', 'email'), Rule::unique('users', 'email')],
            'birth_date' => 'required|date',
            'year_level' => 'required|integer|between:1,4',
            'program_id' => 'required|exists:programs,id',
        ]);

        Student::create($data);

        return redirect()->route('students.index')
            ->with('success', 'Student added successfully.');
    }

    public function show(Student $student)
    {
        $student->load('program', 'courses');

        // Fetch ONLY courses matching the student's enrolled program,
        // excluding courses they have already enrolled in.
        $availableCourses = Course::where('program_id', $student->program_id)
            ->whereNotIn('id', $student->courses->pluck('id'))
            ->orderBy('year_level')
            ->orderBy('code')
            ->get();

        return view('students.show', compact('student', 'availableCourses'));
    }

    public function edit(Student $student)
    {
        $programs = Program::orderBy('name')->get();

        return view('students.edit', compact('student', 'programs'));
    }

    public function update(Request $request, Student $student)
    {
        $request->merge(['email' => mb_strtolower(trim((string) $request->input('email', '')))]);
        $data = $request->validate([
            'student_number' => 'required|string|max:20|unique:students,student_number,'.$student->id,
            'first_name' => 'required|string|max:60',
            'last_name' => 'required|string|max:60',
            'email' => [
                'required',
                'email',
                Rule::unique('students', 'email')->ignore($student),
                Rule::unique('users', 'email')->ignore($student->user_id),
            ],
            'birth_date' => 'required|date',
            'year_level' => 'required|integer|between:1,4',
            'program_id' => 'required|exists:programs,id',
        ]);

        DB::transaction(function () use ($student, $data): void {
            $student->update($data);
            $student->user?->update([
                'name' => trim($data['first_name'].' '.$data['last_name']),
                'email' => $data['email'],
            ]);
        });

        return redirect()->route('students.index')
            ->with('success', 'Student updated successfully.');
    }

    public function destroy(Student $student)
    {
        DB::transaction(function () use ($student): void {
            $student->user?->delete();
            $student->delete();
        });

        return redirect()->route('students.index')
            ->with('success', 'Student deleted successfully.');
    }

    public function archive(Student $student)
    {
        $student->update(['status' => 'archived']);

        return redirect()->route('students.index')->with('success', 'Student archived successfully.');
    }

    public function enroll(Request $request, Student $student)
    {
        $request->validate([
            'course_id' => 'required|exists:courses,id',
            'grade' => 'nullable|string|max:10',
        ]);

        $student->courses()->syncWithoutDetaching([
            $request->course_id => ['grade' => $request->grade],
        ]);

        return back()->with('success', 'Subject enrolled successfully!');
    }

    public function updateGrade(Request $request, Student $student, Course $course)
    {
        abort_unless($student->courses()->whereKey($course->id)->exists(), 404);

        $request->validate([
            'grade' => 'present|nullable|string|max:10',
        ]);

        // Updates grade directly beside the course row in the table
        $student->courses()->updateExistingPivot($course->id, [
            'grade' => $request->grade,
        ]);

        return back()->with('success', 'Grade updated successfully.');
    }
}
