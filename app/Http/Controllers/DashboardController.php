<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\EnrollmentApplication;
use App\Models\Program;
use App\Models\Student;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $programs = Program::query()
            ->withCount('students')
            ->orderByDesc('students_count')
            ->orderBy('name')
            ->get();

        $largestProgramCount = max((int) $programs->max('students_count'), 1);

        $programStats = $programs->map(fn (Program $program): array => [
            'program' => $program,
            'student_count' => $program->students_count,
            'percentage' => (int) round($program->students_count / $largestProgramCount * 100),
        ]);

        return view('dashboard', [
            'studentCount' => Student::count(),
            'activeCourseCount' => Course::query()->where('status', 'active')->count(),
            'programCount' => Program::count(),
            'pendingApplicationCount' => EnrollmentApplication::query()->where('status', 'pending')->count(),
            'programStats' => $programStats,
            'recentStudents' => Student::query()
                ->with('program')
                ->latest()
                ->limit(5)
                ->get(),
        ]);
    }
}
