<?php

namespace App\Http\Controllers;

use App\Models\EnrollmentApplication;
use App\Models\Student;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        return view('dashboard', [
            'studentCount' => Student::count(),
            'activeStudentCount' => Student::query()->where('status', 'active')->count(),
            'inactiveStudentCount' => Student::query()->where('status', 'inactive')->count(),
            'droppedStudentCount' => Student::query()->where('status', 'dropped')->count(),
            'pendingApplicationCount' => EnrollmentApplication::query()->where('status', 'pending')->count(),
            'approvedApplicationCount' => EnrollmentApplication::query()->where('status', 'approved')->count(),
            'rejectedApplicationCount' => EnrollmentApplication::query()->where('status', 'rejected')->count(),
            'recentApplications' => EnrollmentApplication::query()
                ->with('program')
                ->latest('submitted_at')
                ->orderByDesc('id')
                ->limit(5)
                ->get(),
        ]);
    }
}
