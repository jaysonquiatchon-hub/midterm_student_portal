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

    <!-- Enrolled Courses & Grade Management Table -->
    <h4 class="fw-bold text-dark mb-3">Enrolled Courses</h4>
    
    <div class="table-responsive mb-4">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Title</th>
                    <th>Units</th>
                    <th>Grade</th>
                    <th style="width: 200px;">Input Grade</th>
                </tr>
            </thead>
            <tbody>
                @forelse($student->courses as $course)
                    <tr>
                        <td class="fw-semibold">{{ $course->code }}</td>
                        <td>{{ $course->title }}</td>
                        <td>{{ $course->units }}</td>
                        <td>
                            @if($course->pivot->grade)
                                <span class="badge bg-primary fs-6 fw-normal px-2 py-1">
                                    {{ $course->pivot->grade }}
                                </span>
                            @else
                                <span class="badge bg-secondary fs-6 fw-normal px-2 py-1">
                                    No grade yet
                                </span>
                            @endif
                        </td>
                        <td>
                            <!-- Form to Add or Edit Grade Inline -->
                            <form action="{{ route('students.courses.update-grade', [$student->id, $course->id]) }}" method="POST" class="d-flex align-items-center gap-2">
                                @csrf
                                <input 
                                    type="text" 
                                    name="grade" 
                                    value="{{ old('grade', $course->pivot->grade) }}" 
                                    class="form-control form-control-sm @error('grade') is-invalid @enderror"
                                    placeholder="e.g. 1.25"
                                    style="max-width: 90px;"
                                >
                                <button type="submit" class="btn btn-sm btn-outline-primary fw-semibold">
                                    Save
                                </button>
                            </form>
                            @error('grade')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted py-3">
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