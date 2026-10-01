@extends('layouts.app')

@section('content')
<div class="card border-0 shadow-sm rounded-3 p-4">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
        <div>
            <h2 class="fw-bold text-dark mb-1">{{ $enrollment->reference_number }}</h2>
            <p class="text-muted mb-0">Submitted {{ $enrollment->created_at->format('M j, Y g:i A') }}</p>
        </div>
        <span class="badge bg-{{ $enrollment->status === 'enrolled' ? 'success' : ($enrollment->status === 'rejected' ? 'danger' : 'secondary') }} fs-6">{{ ucfirst($enrollment->status) }}</span>
    </div>

    @if(session('warning'))<div class="alert alert-warning">{{ session('warning') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

    <h4 class="fw-bold mb-3">Student Information</h4>
    <dl class="row mb-4">
        <dt class="col-sm-3">Name</dt><dd class="col-sm-9">{{ $enrollment->student->full_name }}</dd>
        <dt class="col-sm-3">Student Number</dt><dd class="col-sm-9">{{ $enrollment->student->student_number }}</dd>
        <dt class="col-sm-3">Email</dt><dd class="col-sm-9">{{ $enrollment->student->email }}</dd>
        <dt class="col-sm-3">Current Program</dt><dd class="col-sm-9">{{ $enrollment->student->program->name }}</dd>
        <dt class="col-sm-3">Year Level</dt><dd class="col-sm-9">Year {{ $enrollment->student->year_level }}</dd>
        <dt class="col-sm-3">Requested Period</dt><dd class="col-sm-9">{{ $enrollment->academic_year }} · {{ $enrollment->term }} Semester</dd>
    </dl>

    @if($enrollment->status === 'pending')
        <div class="d-flex flex-wrap gap-2 mb-3">
            <form action="{{ route('admin.enrollments.update', $enrollment) }}" method="POST">
                @csrf @method('PATCH')
                <input type="hidden" name="status" value="processing">
                <button class="btn btn-primary fw-semibold">Start Processing</button>
            </form>
        </div>
    @endif

    @if($enrollment->status === 'processing')
        <h4 class="fw-bold mb-3">Assign Curriculum and Courses</h4>
        <form action="{{ route('admin.enrollments.update', $enrollment) }}" method="POST" class="mb-4">
            @csrf @method('PATCH')
            <input type="hidden" name="status" value="enrolled">
            <div class="mb-3">
                <label for="program_id" class="form-label fw-semibold">Curriculum / Program</label>
                <select id="program_id" name="program_id" class="form-select @error('program_id') is-invalid @enderror" required>
                    <option value="">Select a curriculum</option>
                    @foreach($programs as $program)
                        <option value="{{ $program->id }}" @selected(old('program_id', $enrollment->student->program_id) == $program->id)>{{ $program->code }} - {{ $program->name }}</option>
                    @endforeach
                </select>
                @error('program_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label for="course_ids" class="form-label fw-semibold">Courses</label>
                <select id="course_ids" name="course_ids[]" class="form-select @error('course_ids') is-invalid @enderror" multiple size="8" required>
                    @foreach($programs as $program)
                        <optgroup label="{{ $program->code }}">
                            @foreach($program->courses as $course)
                                <option value="{{ $course->id }}" @selected(in_array($course->id, old('course_ids', $enrollment->courses->pluck('id')->all())))>{{ $course->code }} - {{ $course->title }} ({{ $course->units }} units)</option>
                            @endforeach
                        </optgroup>
                    @endforeach
                </select>
                @error('course_ids')<div class="invalid-feedback">{{ $message }}</div>@enderror
                @error('course_ids.*')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>
            <button class="btn btn-success fw-semibold">Confirm Enrollment</button>
        </form>
    @endif

    @if(in_array($enrollment->status, ['pending', 'processing'], true))
        <form action="{{ route('admin.enrollments.update', $enrollment) }}" method="POST" class="border-top pt-3">
            @csrf @method('PATCH')
            <input type="hidden" name="status" value="rejected">
            <label for="rejection_reason" class="form-label fw-semibold">Reject Request</label>
            <textarea id="rejection_reason" name="rejection_reason" class="form-control mb-2" rows="2" maxlength="2000" placeholder="Reason for rejection" required>{{ old('rejection_reason') }}</textarea>
            <button class="btn btn-outline-danger">Reject Enrollment</button>
        </form>
    @endif

    @if($enrollment->status === 'enrolled')
        <h4 class="fw-bold mb-3">Assigned Courses</h4>
        <div class="table-responsive"><table class="table align-middle"><thead><tr><th>Code</th><th>Course</th><th>Units</th></tr></thead><tbody>
            @forelse($enrollment->courses as $course)
                <tr><td class="fw-semibold">{{ $course->code }}</td><td>{{ $course->title }}</td><td>{{ $course->units }}</td></tr>
            @empty
                <tr><td colspan="3" class="text-muted text-center py-3">No courses assigned.</td></tr>
            @endforelse
        </tbody></table></div>
    @endif

    <div class="mt-3"><a href="{{ route('admin.enrollments.index') }}" class="btn btn-secondary">Back to Enrollments</a></div>
</div>
@endsection
