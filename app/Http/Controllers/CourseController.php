<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\EnrollmentApplication;
use App\Models\Program;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CourseController extends Controller
{
    public function index(): View
    {
        $courses = Course::query()
            ->with('program')
            ->where('status', 'active')
            ->orderBy('program_id')
            ->orderBy('year_level')
            ->orderBy('code')
            ->paginate(20);

        return view('courses.index', compact('courses'));
    }

    public function create(): View
    {
        return view('courses.create', [
            'programs' => Program::query()->where('status', 'active')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Course::create($this->validatedData($request) + ['status' => 'active']);

        return redirect()->route('courses.index')->with('success', 'Course created.');
    }

    public function edit(Course $course): View
    {
        return view('courses.edit', [
            'course' => $course,
            'programs' => Program::query()
                ->where('status', 'active')
                ->orWhereKey($course->program_id)
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function update(Request $request, Course $course): RedirectResponse
    {
        $data = $this->validatedData($request, $course);
        $catalogFieldsChanged = collect(['code', 'title', 'units', 'year_level', 'semester', 'program_id'])
            ->contains(fn (string $field): bool => (string) $course->{$field} !== (string) $data[$field]);
        $isReferenced = $course->students()->exists()
            || Enrollment::query()->whereHas('courses', fn ($query) => $query->whereKey($course->id))->exists()
            || EnrollmentApplication::query()->whereHas('subjects', fn ($query) => $query->whereKey($course->id))->exists();

        if ($catalogFieldsChanged && $isReferenced) {
            return back()
                ->withErrors(['course' => 'This subject is part of an enrollment or application history. Archive it and create a new subject to preserve those academic records.'])
                ->withInput();
        }

        $course->update($data);

        return redirect()->route('courses.index')->with('success', 'Course updated.');
    }

    public function archive(Course $course): RedirectResponse
    {
        $course->update(['status' => 'archived']);

        return redirect()->route('courses.index')->with('success', 'Course archived.');
    }

    private function validatedData(Request $request, ?Course $course = null): array
    {
        return $request->validate([
            'code' => [
                'required',
                'string',
                'max:20',
                Rule::unique('courses', 'code')
                    ->where('program_id', $request->input('program_id'))
                    ->ignore($course),
            ],
            'title' => ['required', 'string', 'max:255'],
            'units' => ['required', 'integer', 'min:1', 'max:12'],
            'year_level' => ['required', 'integer', 'min:1', 'max:5'],
            'semester' => ['required', 'string', 'max:20'],
            'program_id' => ['required', 'exists:programs,id'],
        ]);
    }
}
