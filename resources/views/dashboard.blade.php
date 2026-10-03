@extends('layouts.app')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h1 class="h2 fw-bold mb-1">Dashboard</h1>
        <p class="text-muted mb-0">Quick overview of students and academic records.</p>
    </div>
    <a href="{{ route('students.create') }}" class="btn btn-primary">+ Add Student</a>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <p class="text-muted mb-2">Total Students</p>
                <p class="display-6 fw-bold mb-0">{{ number_format($studentCount) }}</p>
                <a href="{{ route('students.index') }}" class="small">View student directory</a>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <p class="text-muted mb-2">Active Courses</p>
                <p class="display-6 fw-bold mb-0">{{ number_format($activeCourseCount) }}</p>
                <a href="{{ route('courses.index') }}" class="small">View course catalog</a>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <p class="text-muted mb-2">Programs</p>
                <p class="display-6 fw-bold mb-0">{{ number_format($programCount) }}</p>
                <span class="small text-muted">Academic programs in the portal</span>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <p class="text-muted mb-2">Pending Applications</p>
                <p class="display-6 fw-bold mb-0">{{ number_format($pendingApplicationCount) }}</p>
                <span class="small text-muted">Applications waiting for review</span>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <section class="col-lg-5" aria-labelledby="program-breakdown-heading">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h2 id="program-breakdown-heading" class="h5 fw-bold mb-1">Students by Program</h2>
                <p class="text-muted small mb-4">Student totals for each academic program.</p>

                @forelse ($programStats as $stat)
                    <div class="mb-3">
                        <div class="d-flex justify-content-between gap-3 mb-1">
                            <span>{{ $stat['program']->name }}</span>
                            <strong>{{ number_format($stat['student_count']) }}</strong>
                        </div>
                        <div
                            class="progress"
                            role="progressbar"
                            aria-label="Students in {{ $stat['program']->name }}"
                            aria-valuenow="{{ $stat['student_count'] }}"
                            aria-valuemin="0"
                            aria-valuemax="{{ max($studentCount, 1) }}"
                        >
                            <div class="progress-bar" style="width: {{ $stat['percentage'] }}%"></div>
                        </div>
                    </div>
                @empty
                    <p class="text-muted mb-0">No programs have been added yet.</p>
                @endforelse
            </div>
        </div>
    </section>

    <section class="col-lg-7" aria-labelledby="recent-students-heading">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center gap-2 mb-3">
                    <div>
                        <h2 id="recent-students-heading" class="h5 fw-bold mb-1">Recently Added Students</h2>
                        <p class="text-muted small mb-0">The latest student records added to the portal.</p>
                    </div>
                    <a href="{{ route('students.index') }}" class="btn btn-sm btn-outline-primary">View All</a>
                </div>

                <div class="table-responsive">
                    <table class="table table-striped align-middle mb-0">
                        <thead>
                            <tr>
                                <th scope="col">Student</th>
                                <th scope="col">Program</th>
                                <th scope="col">Year</th>
                                <th scope="col"><span class="visually-hidden">Action</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($recentStudents as $student)
                                <tr>
                                    <td>
                                        <span class="d-block fw-semibold">{{ $student->full_name }}</span>
                                        <small class="text-muted">{{ $student->student_number }}</small>
                                    </td>
                                    <td>{{ $student->program?->code ?? 'N/A' }}</td>
                                    <td>{{ $student->year_level }}</td>
                                    <td class="text-end">
                                        <a href="{{ route('students.show', $student) }}" class="btn btn-sm btn-outline-primary">View</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">No students have been added yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection
