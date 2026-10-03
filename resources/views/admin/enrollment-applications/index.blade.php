@extends('layouts.app')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h1 class="h2 mb-0">{{ $history ? 'Enrollment History' : 'Pending Applications' }}</h1>
    <div class="btn-group" role="group" aria-label="Enrollment application views">
        <a href="{{ route('admin.enrollment-applications.index') }}" class="btn btn-outline-primary {{ ! $history ? 'active' : '' }}">Pending Applications</a>
        <a href="{{ route('admin.enrollment-applications.history') }}" class="btn btn-outline-primary {{ $history ? 'active' : '' }}">Enrollment History</a>
    </div>
</div>

<form method="GET" action="{{ $history ? route('admin.enrollment-applications.history') : route('admin.enrollment-applications.index') }}" class="row g-2 mb-3">
    <div class="col-md-4"><label class="visually-hidden" for="search">Search applications</label><input id="search" name="search" value="{{ $search }}" class="form-control" placeholder="Application, student name, or email"></div>
    <div class="col-md-2"><label class="visually-hidden" for="status">Status</label><select id="status" name="status" class="form-select"><option value="">All statuses</option>@foreach(['pending' => 'Pending', 'under_review' => 'Under Review', 'approved' => 'Approved', 'rejected' => 'Rejected'] as $value => $label)<option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>@endforeach</select></div>
    <div class="col-md-3"><label class="visually-hidden" for="program_id">Program</label><select id="program_id" name="program_id" class="form-select"><option value="">All programs</option>@foreach($programs as $program)<option value="{{ $program->id }}" @selected($programId === $program->id)>{{ $program->name }}</option>@endforeach</select></div>
    <div class="col-md-1 d-grid"><button class="btn btn-primary">Filter</button></div>
</form>

<div class="table-responsive">
    <table class="table table-striped bg-white align-middle">
        <thead><tr><th>Application No.</th><th>Student</th><th>Email</th><th>Program</th><th>Year</th><th>Submitted</th><th>Status</th><th></th></tr></thead>
        <tbody>
            @forelse($applications as $application)
                <tr>
                    <td class="font-monospace fw-semibold">{{ $application->application_number }}</td>
                    <td>{{ $application->full_name }}</td>
                    <td>{{ $application->email }}</td>
                    <td>{{ $application->program->name }}</td>
                    <td>{{ $application->year_level }}</td>
                    <td>{{ $application->submitted_at?->format('M j, Y') }}</td>
                    <td><span class="badge bg-{{ $application->status === 'approved' ? 'success' : ($application->status === 'rejected' ? 'danger' : 'secondary') }}">{{ ucwords(str_replace('_', ' ', $application->status)) }}</span></td>
                    <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="{{ route('admin.enrollment-applications.show', $application) }}">View</a></td>
                </tr>
            @empty
                <tr><td colspan="8" class="text-center text-muted py-4">No applications found.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
{{ $applications->links() }}
@endsection
