@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h1 class="mb-1">My Student Portal</h1>
        <p class="text-muted mb-0">{{ $student->full_name }} · {{ $student->student_id ?? ($student->status === 'pending' ? 'Applicant' : $student->student_number) }}</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="{{ route('student.profile.edit') }}" class="btn btn-outline-secondary">Edit Profile</a>
        <a href="{{ route('enrollment.create') }}" class="btn btn-primary">Apply / Submit Requirements</a>
        <a href="{{ route('student.enrollments.create') }}" class="btn btn-outline-primary">Request Term Enrollment</a>
    </div>
</div>

@if($student->status === 'pending')
    <div class="alert alert-info">Your student account is active. Your official student ID will be assigned after your application is approved.</div>
@endif

<div class="card border-0 shadow-sm rounded-3 mb-4">
    <div class="card-body p-4">
        <div class="d-flex align-items-center gap-3 mb-3">
            @if($student->profile_photo_path)
                <img src="{{ route('student.profile.photo') }}" alt="Profile photo of {{ $student->full_name }}" class="rounded-circle object-fit-cover" width="72" height="72">
            @else
                <div class="rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center fw-bold" style="width:72px;height:72px" aria-hidden="true">{{ mb_substr($student->first_name, 0, 1) }}{{ mb_substr($student->last_name, 0, 1) }}</div>
            @endif
            <div>
                <h5 class="fw-bold mb-1">Student Information</h5>
                <a href="{{ route('student.profile.edit') }}" class="small">Update your personal information and profile photo</a>
            </div>
        </div>
        <div class="row g-3">
            <div class="col-md-4"><span class="text-muted">Student number</span><div>{{ $student->student_id ?? $student->student_number }}</div></div>
            <div class="col-md-4"><span class="text-muted">Email</span><div>{{ $student->email }}</div></div>
            <div class="col-md-4"><span class="text-muted">Program</span><div>{{ $student->program?->name ?? 'Not assigned' }}</div></div>
            <div class="col-md-4"><span class="text-muted">Year level</span><div>{{ $student->year_level ? 'Year '.$student->year_level : 'Not assigned' }}</div></div>
            <div class="col-md-4"><span class="text-muted">Contact number</span><div>{{ $student->contact_number ?: 'Not provided' }}</div></div>
            <div class="col-md-4"><span class="text-muted">Address</span><div>{{ $student->address ?: 'Not provided' }}</div></div>
            <div class="col-md-4"><span class="text-muted">Date of birth</span><div>{{ $student->birth_date?->format('M j, Y') ?? 'Not provided' }}</div></div>
            <div class="col-md-4"><span class="text-muted">Gender</span><div>{{ $student->gender ?: 'Not provided' }}</div></div>
            <div class="col-md-4"><span class="text-muted">Nationality</span><div>{{ $student->nationality ?: 'Not provided' }}</div></div>
        </div>
    </div>
</div>

<h4 class="fw-bold mb-2">My Applications</h4>
<div class="table-responsive mb-4">
    <table class="table table-striped bg-white align-middle">
        <thead><tr><th>Application Number</th><th>Student Type</th><th>School Year</th><th>Term</th><th>Status</th><th>Submitted</th></tr></thead>
        <tbody>
            @forelse($applications as $application)
                <tr>
                    <td class="fw-semibold">{{ $application->application_number }}</td>
                    <td>{{ $application->student_type }}</td>
                    <td>{{ $application->school_year }}</td>
                    <td>{{ $application->semester }}</td>
                    <td><span class="badge bg-{{ $application->status === 'approved' ? 'success' : ($application->status === 'rejected' ? 'danger' : 'secondary') }}">{{ ucwords(str_replace('_', ' ', $application->status)) }}</span></td>
                    <td>{{ $application->submitted_at?->format('M j, Y') }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center text-muted py-3">No applications submitted yet.</td></tr>
            @endforelse
        </tbody>
    </table>
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
