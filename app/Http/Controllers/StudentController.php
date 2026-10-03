<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Program;
use App\Models\Student;
use Illuminate\Http\Request;
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
        $data = $request->validate([
            'student_number' => 'required|string|max:20|unique:students',
            'first_name' => 'required|string|max:60',
            'last_name' => 'required|string|max:60',
            'email' => 'required|email|unique:students',
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

        $availableCourses = Course::query()
            ->where('program_id', $student->program_id)
            ->where('status', 'active')
            ->whereDoesntHave('students', fn ($query) => $query->whereKey($student->getKey()))
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
        $data = $request->validate([
            'student_number' => 'required|string|max:20|unique:students,student_number,'.$student->id,
            'first_name' => 'required|string|max:60',
            'last_name' => 'required|string|max:60',
            'email' => 'required|email|unique:students,email,'.$student->id,
            'birth_date' => 'required|date',
            'year_level' => 'required|integer|between:1,4',
            'program_id' => 'required|exists:programs,id',
        ]);

        $student->update($data);

        return redirect()->route('students.index')
            ->with('success', 'Student updated successfully.');
    }

    public function destroy(Student $student)
    {
        $student->delete();

        return redirect()->route('students.index')
            ->with('success', 'Student deleted successfully.');
    }

    public function archive(Student $student)
    {
        $student->update(['status' => 'archived']);

        return redirect()->route('students.index')
            ->with('success', 'Student archived successfully.');
    }

    public function enroll(Request $request, Student $student)
    {
        $data = $request->validate([
            'course_id' => [
                'required',
                Rule::exists('courses', 'id')
                    ->where('program_id', $student->program_id)
                    ->where('status', 'active'),
            ],
            'grade' => ['nullable', 'string', 'max:10', 'numeric', 'decimal:0,2'],
        ]);

        if ($student->courses()->whereKey($data['course_id'])->exists()) {
            return back()
                ->withErrors(['course_id' => 'This student is already enrolled in that course.'])
                ->withInput();
        }

        $student->courses()->attach($data['course_id'], ['grade' => $data['grade'] ?? null]);

        return back()->with('success', 'Subject enrolled successfully!');
    }

    public function updateGrade(Request $request, Student $student, Course $course)
    {
        $data = $request->validate([
            'grade' => ['nullable', 'string', 'max:10', 'numeric', 'decimal:0,2'],
        ]);

        $student->courses()->updateExistingPivot($course->id, [
            'grade' => $data['grade'] ?? null,
        ]);

        return back()->with('success', 'Grade updated successfully.');
    }
}
