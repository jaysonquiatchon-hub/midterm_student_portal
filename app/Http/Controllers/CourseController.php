<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Program;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CourseController extends Controller
{
    public function index(): View
    {
        return view('courses.index', [
            'programs' => Program::query()->with(['courses' => fn ($query) => $query
                ->orderBy('year_level')
                ->orderBy('code')])->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('courses.create', ['programs' => Program::query()->where('status', 'active')->orderBy('name')->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        Course::create($this->validatedData($request) + ['status' => 'active']);

        return redirect()->route('courses.index')->with('success', 'Course added successfully.');
    }

    public function edit(Course $course): View
    {
        return view('courses.edit', [
            'course' => $course,
            'programs' => Program::query()->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Course $course): RedirectResponse
    {
        $course->update($this->validatedData($request, $course));

        return redirect()->route('courses.index')->with('success', 'Course updated successfully.');
    }

    public function archive(Course $course): RedirectResponse
    {
        $course->update(['status' => 'archived']);

        return redirect()->route('courses.index')->with('success', 'Subject archived successfully.');
    }

    private function validatedData(Request $request, ?Course $course = null): array
    {
        return $request->validate([
            'code' => ['required', 'string', 'max:20', Rule::unique('courses', 'code')->ignore($course)],
            'title' => ['required', 'string', 'max:255'],
            'units' => ['required', 'integer', 'between:1,10'],
            'year_level' => ['required', 'integer', 'between:1,4'],
            'semester' => ['required', Rule::in(['1st', '2nd', 'Summer'])],
            'program_id' => ['required', 'exists:programs,id'],
        ]);
    }
}
