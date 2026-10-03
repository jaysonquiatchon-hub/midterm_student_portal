<?php

namespace App\Http\Controllers;

use App\Models\Course;
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
}
