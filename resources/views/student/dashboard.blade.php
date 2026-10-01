@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h1 class="mb-1">My Student Portal</h1>
        <p class="text-muted mb-0">{{ $student->full_name }} · {{ $student->student_number }}</p>
    </div>
    <a href="{{ route('student.enrollments.create') }}" class="btn btn-primary">Start Enrollment</a>
</div>

<div class="card border-0 shadow-sm rounded-3 mb-4">
    <div class="card-body p-4">
        <h5 class="fw-bold mb-3">Student Information</h5>
        <div class="row g-3">
            <div class="col-md-4"><span class="text-muted">Email</span><div>{{ $student->email }}</div></div>
            <div class="col-md-4"><span class="text-muted">Program</span><div>{{ $student->program->name }}</div></div>
            <div class="col-md-4"><span class="text-muted">Year Level</span><div>Year {{ $student->year_level }}</div></div>
        </div>
    </div>
</div>

<div class="d-flex justify-content-between align-items-center mb-2">
    <h4 class="fw-bold mb-0">Enrollment History</h4>
</div>
<div class="table-responsive mb-4">
    <table class="table table-striped bg-white align-middle">
        <thead>
            <tr><th>Reference Number</th><th>Academic Year</th><th>Term</th><th>Curriculum</th><th>Status</th><th></th></tr>
        </thead>
        <tbody>
            @forelse($student->enrollments as $enrollment)
                <tr>
                    <td class="fw-semibold">{{ $enrollment->reference_number }}</td>
                    <td>{{ $enrollment->academic_year }}</td>
                    <td>{{ $enrollment->term }}</td>
                    <td>{{ $enrollment->program->code ?? 'Not assigned' }}</td>
                    <td><span class="badge bg-{{ $enrollment->status === 'enrolled' ? 'success' : ($enrollment->status === 'rejected' ? 'danger' : 'secondary') }}">{{ ucfirst($enrollment->status) }}</span></td>
                    <td class="text-end"><a href="{{ route('student.enrollments.show', $enrollment) }}" class="btn btn-sm btn-outline-primary">View</a></td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center text-muted py-3">No enrollment requests yet.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

@if($student->enrollments->where('status', 'enrolled')->isNotEmpty())
    <h4 class="fw-bold mb-3">Assigned Curriculum</h4>
    @foreach($student->enrollments->where('status', 'enrolled') as $enrollment)
        <div class="card border-0 shadow-sm rounded-3 mb-3">
            <div class="card-body p-4">
                <h5 class="fw-bold">{{ $enrollment->program->name ?? 'Curriculum' }} <span class="text-muted fw-normal">· {{ $enrollment->academic_year }} {{ $enrollment->term }}</span></h5>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead><tr><th>Code</th><th>Course</th><th>Units</th><th>Grade</th></tr></thead>
                        <tbody>
                            @forelse($enrollment->courses as $course)
                                @php($recordedCourse = $student->courses->firstWhere('id', $course->id))
                                <tr>
                                    <td class="fw-semibold">{{ $course->code }}</td>
                                    <td>{{ $course->title }}</td>
                                    <td>{{ $course->units }}</td>
                                    <td>{{ $recordedCourse?->pivot?->grade ?? 'Not posted' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-muted text-center py-3">No courses assigned.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endforeach
@endif
@endsection
