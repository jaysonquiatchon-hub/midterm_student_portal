@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1>Term Enrollment Requests</h1>
    <div class="btn-group" role="group" aria-label="Enrollment views">
        <a href="{{ route('admin.enrollment-applications.index') }}" class="btn btn-outline-primary">Pending Applications</a>
        <a href="{{ route('admin.enrollment-applications.history') }}" class="btn btn-outline-primary">Enrollment History</a>
        <a href="{{ route('admin.enrollments.index') }}" class="btn btn-outline-primary active" aria-current="page">Term Enrollment Requests</a>
    </div>
</div>
<form action="{{ route('admin.enrollments.index') }}" method="GET" class="row g-2 mb-3">
    <div class="col-md-6">
        <label class="visually-hidden" for="search">Search reference or student</label>
        <input id="search" name="search" value="{{ $search }}" class="form-control" placeholder="Reference number, student number, or name">
    </div>
    <div class="col-md-3">
        <label class="visually-hidden" for="status">Filter by status</label>
        <select id="status" name="status" class="form-select">
            <option value="">All statuses</option>
            @foreach(['pending' => 'Pending', 'processing' => 'Processing', 'enrolled' => 'Enrolled', 'rejected' => 'Rejected'] as $value => $label)
                <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3 d-flex gap-2">
        <button type="submit" class="btn btn-primary">Search</button>
        <a href="{{ route('admin.enrollments.index') }}" class="btn btn-outline-secondary">Clear</a>
    </div>
</form>

<table class="table table-striped bg-white align-middle">
    <thead><tr><th>Reference</th><th>Student</th><th>Academic Year</th><th>Term</th><th>Curriculum</th><th>Status</th><th class="text-end">Action</th></tr></thead>
    <tbody>
        @forelse($enrollments as $enrollment)
            <tr>
                <td class="fw-semibold">{{ $enrollment->reference_number }}</td>
                <td>{{ $enrollment->student->full_name }}<div class="small text-muted">{{ $enrollment->student->student_number }}</div></td>
                <td>{{ $enrollment->academic_year }}</td>
                <td>{{ $enrollment->term }}</td>
                <td>{{ $enrollment->program->code ?? 'Not assigned' }}</td>
                <td><span class="badge bg-{{ $enrollment->status === 'enrolled' ? 'success' : ($enrollment->status === 'rejected' ? 'danger' : 'secondary') }}">{{ ucfirst($enrollment->status) }}</span></td>
                <td class="text-end"><a href="{{ route('admin.enrollments.show', $enrollment) }}" class="btn btn-sm btn-outline-primary">Review</a></td>
            </tr>
        @empty
            <tr><td colspan="7" class="text-center text-muted py-3">No enrollment transactions found.</td></tr>
        @endforelse
    </tbody>
</table>
{{ $enrollments->links() }}
@endsection
