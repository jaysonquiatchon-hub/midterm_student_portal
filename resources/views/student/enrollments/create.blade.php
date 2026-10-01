@extends('layouts.app')

@section('content')
<div class="card border-0 shadow-sm rounded-3 p-4">
    <h2 class="fw-bold text-dark mb-1">Enrollment Request</h2>
    <p class="text-muted mb-4">Submit your request for admin review.</p>
    <form action="{{ route('student.enrollments.store') }}" method="POST" class="row g-3">
        @csrf
        <div class="col-md-6">
            <label for="academic_year" class="form-label fw-semibold">Academic Year</label>
            <input id="academic_year" name="academic_year" value="{{ old('academic_year', $academicYear) }}" class="form-control @error('academic_year') is-invalid @enderror" pattern="[0-9]{4}-[0-9]{4}" placeholder="2026-2027" required>
            @error('academic_year')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6">
            <label for="term" class="form-label fw-semibold">Term</label>
            <select id="term" name="term" class="form-select @error('term') is-invalid @enderror" required>
                <option value="">Select term</option>
                <option value="1st" @selected(old('term') === '1st')>1st Semester</option>
                <option value="2nd" @selected(old('term') === '2nd')>2nd Semester</option>
                <option value="Summer" @selected(old('term') === 'Summer')>Summer</option>
            </select>
            @error('term')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-12 d-flex gap-2 mt-4">
            <button type="submit" class="btn btn-primary fw-semibold">Submit Enrollment</button>
            <a href="{{ route('student.dashboard') }}" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>
@endsection
