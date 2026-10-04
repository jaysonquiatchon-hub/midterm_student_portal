@extends('layouts.app')

@section('content')
<div class="card border-0 shadow-sm rounded-3 p-4">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
        <div>
            <h2 class="fw-bold text-dark mb-1">Enrollment {{ $enrollment->reference_number }}</h2>
            <p class="text-muted mb-0">{{ $enrollment->academic_year }} · {{ $enrollment->term }} Semester</p>
        </div>
        <span class="badge bg-{{ $enrollment->status === 'enrolled' ? 'success' : ($enrollment->status === 'rejected' ? 'danger' : 'secondary') }} fs-6">{{ ucfirst($enrollment->status) }}</span>
    </div>
    <dl class="row mb-4">
        <dt class="col-sm-3">Student</dt><dd class="col-sm-9">{{ $student->full_name }} · {{ $student->student_number }}</dd>
        <dt class="col-sm-3">Current Status</dt><dd class="col-sm-9">{{ ucfirst($enrollment->status) }}</dd>
        <dt class="col-sm-3">Curriculum</dt><dd class="col-sm-9">{{ $enrollment->program->name ?? 'Assigned by admin after review' }}</dd>
        @if($enrollment->status === 'rejected' && $enrollment->rejection_reason)
            <dt class="col-sm-3">Admin Note</dt><dd class="col-sm-9">{{ $enrollment->rejection_reason }}</dd>
        @endif
    </dl>

    @if($enrollment->status === 'enrolled')
        <h4 class="fw-bold mb-3">Assigned Courses</h4>
        <p class="text-muted">Total units: <strong>{{ $enrollment->courses->sum('units') }}</strong></p>
        <div class="table-responsive">
            <table class="table align-middle">
                <thead><tr><th>Code</th><th>Course</th><th>Units</th><th>Grade</th></tr></thead>
                <tbody>
                    @forelse($enrollment->courses as $course)
                        <tr><td class="fw-semibold">{{ $course->code }}</td><td>{{ $course->title }}</td><td>{{ $course->units }}</td><td>{{ $course->pivot->grade ?? 'Not posted' }}</td></tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-muted py-3">No courses assigned.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endif
    <div class="mt-3"><a href="{{ route('student.dashboard') }}" class="btn btn-secondary">Back to Portal</a></div>
</div>
@endsection
