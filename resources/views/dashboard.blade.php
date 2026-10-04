@extends('layouts.app')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h1 class="h2 fw-bold mb-1">Administrator Dashboard</h1>
        <p class="text-muted mb-0">Current student and application totals from the SIS records.</p>
    </div>
    <a href="{{ route('admin.enrollment-applications.index') }}" class="btn btn-primary">Review applications</a>
</div>

<div class="row g-3 mb-4">
    @foreach ([
        ['Total Students', $studentCount, 'students.index', null],
        ['Active Students', $activeStudentCount, 'students.index', 'active'],
        ['Inactive Students', $inactiveStudentCount, 'students.index', 'inactive'],
        ['Dropped Students', $droppedStudentCount, 'students.index', 'dropped'],
    ] as [$label, $count, $route, $status])
        <div class="col-6 col-xl-3">
            <a href="{{ route($route, $status ? ['status' => $status] : []) }}" class="card dashboard-stat-card h-100 text-decoration-none">
                <div class="card-body"><p class="text-muted mb-2">{{ $label }}</p><p class="display-6 fw-bold mb-0">{{ number_format($count) }}</p></div>
            </a>
        </div>
    @endforeach
    @foreach ([
        ['Pending Applications', $pendingApplicationCount, 'pending'],
        ['Approved Applications', $approvedApplicationCount, 'approved'],
        ['Rejected Applications', $rejectedApplicationCount, 'rejected'],
    ] as [$label, $count, $status])
        <div class="col-6 col-xl-3">
            <a href="{{ route('admin.enrollment-applications.history', ['status' => $status]) }}" class="card dashboard-stat-card h-100 text-decoration-none">
                <div class="card-body"><p class="text-muted mb-2">{{ $label }}</p><p class="display-6 fw-bold mb-0">{{ number_format($count) }}</p></div>
            </a>
        </div>
    @endforeach
</div>

<section class="card border-0 shadow-sm" aria-labelledby="recent-applications-heading">
    <div class="card-body">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <div><h2 id="recent-applications-heading" class="h5 fw-bold mb-1">Recent Enrollment Applications</h2><p class="text-muted small mb-0">Most recently submitted applications.</p></div>
            <a href="{{ route('admin.enrollment-applications.history') }}" class="btn btn-sm btn-outline-primary">View all</a>
        </div>
        <div class="table-responsive">
            <table class="table table-striped align-middle mb-0">
                <thead><tr><th>Application No.</th><th>Applicant</th><th>Program</th><th>Submitted</th><th>Status</th><th></th></tr></thead>
                <tbody>
                    @forelse ($recentApplications as $application)
                        <tr>
                            <td class="font-monospace">{{ $application->application_number }}</td>
                            <td>{{ $application->full_name }}</td>
                            <td>{{ $application->program?->code ?? $application->program?->name ?? '—' }}</td>
                            <td>{{ $application->submitted_at?->format('M j, Y') ?? '—' }}</td>
                            <td><span class="badge text-bg-{{ $application->status === 'approved' ? 'success' : ($application->status === 'rejected' ? 'danger' : ($application->status === 'pending' ? 'warning' : 'secondary')) }}">{{ ucwords(str_replace('_', ' ', $application->status)) }}</span></td>
                            <td class="text-end"><a href="{{ route('admin.enrollment-applications.show', $application) }}" class="btn btn-sm btn-outline-primary">Review</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-4">No enrollment applications have been submitted.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</section>
@endsection
