@extends('layouts.app')

@section('content')
<div class="card border-0 shadow-sm rounded-3 p-4">
    <!-- Student Header Information with Action Buttons -->
    <div class="d-flex justify-content-between align-items-start mb-3">
        <div>
            <h2 class="fw-bold text-dark mb-1">{{ $student->first_name }} {{ $student->last_name }}</h2>
            <p class="text-muted mb-1">
                <strong>Student No:</strong> {{ $student->student_number }} | 
                <strong>Program:</strong> {{ $student->program->name ?? 'No Program Assigned' }} | 
                <strong>Year Level:</strong> Year {{ $student->year_level }}
            </p>
            <p class="text-muted mb-0"><strong>Email:</strong> {{ $student->email }}</p>
            <p class="text-muted mb-0"><strong>Status:</strong> <span class="badge text-bg-{{ $student->status === 'active' ? 'success' : ($student->status === 'dropped' ? 'danger' : 'secondary') }}">{{ ucfirst($student->status) }}</span></p>
        </div>

        <!-- Upper Right Action Buttons (Edit & Back) -->
        <div class="d-flex gap-2">
            <a href="{{ route('students.edit', $student) }}" class="btn btn-warning btn-sm fw-semibold px-3">
                Edit
            </a>
            <a href="{{ route('students.index') }}" class="btn btn-secondary btn-sm fw-semibold px-3">
                Back
            </a>
        </div>
    </div>

    <hr class="my-4 text-muted">
    <h4 class="fw-bold text-dark mb-3">Enrollment Applications and Requirements</h4>
    @forelse ($student->enrollmentApplications as $application)
        <section class="border rounded p-3 mb-3" aria-label="Application {{ $application->application_number }}">
            <div class="d-flex flex-wrap justify-content-between gap-2 mb-2">
                <div><strong>{{ $application->application_number }}</strong> · {{ $application->program?->name ?? 'Program unavailable' }} · {{ $application->school_year }} {{ $application->semester }}</div>
                <span class="badge text-bg-{{ $application->status === 'approved' ? 'success' : ($application->status === 'rejected' ? 'danger' : 'warning') }}">{{ ucwords(str_replace('_', ' ', $application->status)) }}</span>
            </div>
            @if ($application->documents->isNotEmpty())
                <ul class="list-unstyled mb-0">
                    @foreach ($application->documents as $document)
                        <li><a href="{{ route('admin.enrollment-applications.documents.show', [$application, $document]) }}">{{ $document->label }} ({{ $document->original_name }})</a></li>
                    @endforeach
                </ul>
            @else
                <p class="text-muted mb-0">No supporting documents are attached to this application.</p>
            @endif
        </section>
    @empty
        <p class="text-muted">No linked enrollment applications.</p>
    @endforelse

    <hr class="my-4 text-muted">

    <h4 class="fw-bold text-dark mb-3">Enrollment History and Grades</h4>
    @unless ($gradeTrackingAvailable)
        <div class="alert alert-warning" role="status">
            Student profile and enrollment history are available. Grade tracking will be enabled after the pending database migration is applied.
        </div>
    @endunless
    @forelse ($student->enrollments as $enrollment)
        <section class="border rounded p-3 mb-3">
            <h5 class="h6 fw-bold">{{ $enrollment->academic_year }} · {{ $enrollment->term }} · {{ $enrollment->program?->name ?? 'Program unavailable' }}</h5>
            <p class="text-muted small">Reference {{ $enrollment->reference_number }} · {{ ucfirst($enrollment->status) }}</p>
            <p class="text-muted small">Total units: <strong>{{ $enrollment->courses->sum('units') }}</strong></p>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead><tr><th>Subject</th><th>Units</th><th>Grade</th><th>Update grade</th></tr></thead>
                    <tbody>
                        @forelse ($enrollment->courses as $course)
                            <tr>
                                <td>{{ $course->code }} — {{ $course->title }}</td>
                                <td>{{ $course->units }}</td>
                                <td>{{ $course->pivot->grade ?? 'Not posted' }}</td>
                                <td>
                                    @if ($gradeTrackingAvailable)
                                    <form action="{{ route('students.enrollments.courses.update-grade', [$student, $enrollment, $course]) }}" method="POST" class="d-flex gap-2">
                                        @csrf
                                        <label class="visually-hidden" for="grade-{{ $enrollment->id }}-{{ $course->id }}">Grade for {{ $course->code }}</label>
                                        <input id="grade-{{ $enrollment->id }}-{{ $course->id }}" type="number" min="0" max="99.99" step="0.01" name="grade" value="{{ old('grade', $course->pivot->grade) }}" class="form-control form-control-sm" style="max-width: 7rem" aria-describedby="grade-help-{{ $enrollment->id }}-{{ $course->id }}">
                                        <button type="submit" class="btn btn-sm btn-outline-primary">Save</button>
                                        <span id="grade-help-{{ $enrollment->id }}-{{ $course->id }}" class="visually-hidden">Enter a numeric grade to two decimal places.</span>
                                    </form>
                                    @else
                                        <span class="text-muted">Unavailable until migration</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-muted">No subjects are recorded for this enrollment.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    @empty
        <p class="text-muted">No enrollment history is available.</p>
    @endforelse

    <hr class="my-4 text-muted">

    <h4 class="fw-bold text-dark mb-3">Other Recorded Courses</h4>
    
    <div class="table-responsive mb-4">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Title</th>
                    <th>Units</th>
                    <th>Legacy Grade</th>
                </tr>
            </thead>
            <tbody>
                @forelse($otherCourses as $course)
                    <tr>
                        <td class="fw-semibold">{{ $course->code }}</td>
                        <td>{{ $course->title }}</td>
                        <td>{{ $course->units }}</td>
                        <td>{{ $course->pivot->grade ?? 'Not posted' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center text-muted py-3">
                            No courses enrolled yet. Use the form below to add a course.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Enroll New Subject Form -->
    <div class="card bg-light border-0 p-3 mb-2">
        <h5 class="fw-bold text-dark mb-3">Enroll Course</h5>
        <form action="{{ route('students.enroll', $student->id) }}" method="POST" class="row g-3 align-items-center">
            @csrf
            
            <div class="col-md-9">
                <label for="course_id" class="form-label fw-semibold">Select Course</label>
                <select name="course_id" id="course_id" class="form-select" required>
                    @if($availableCourses->count() > 0)
                        <option value="" selected disabled>Choose a course...</option>
                        @foreach($availableCourses->groupBy('year_level') as $year => $courses)
                            <optgroup label="Year {{ $year }} Subjects">
                                @foreach($courses as $course)
                                    <option value="{{ $course->id }}">
                                        {{ $course->code }} - {{ $course->title }} ({{ $course->units }} Units)
                                    </option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    @else
                        <option value="" disabled selected>
                            No remaining subjects available for {{ $student->program->name ?? 'this program' }}
                        </option>
                    @endif
                </select>
                @error('course_id')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-3 align-self-end">
                <button type="submit" class="btn btn-primary w-100 fw-semibold" {{ $availableCourses->count() === 0 ? 'disabled' : '' }}>
                    Enroll Course
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
