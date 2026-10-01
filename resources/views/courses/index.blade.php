@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1>Course Offerings by Program</h1>
    <div class="d-flex gap-2">
        <a href="{{ route('students.index') }}" class="btn btn-outline-secondary">Back to Students</a>
        <a href="{{ route('courses.create') }}" class="btn btn-primary">+ Add Course</a>
    </div>
</div>

@foreach($programs as $program)
    <div class="card border-0 shadow-sm rounded-3 mb-4">
        <div class="card-header bg-white fw-semibold d-flex justify-content-between"><span>{{ $program->name }} ({{ $program->code }})</span><span class="badge bg-{{ $program->status === 'active' ? 'success' : 'secondary' }}">{{ ucfirst($program->status) }}</span></div>
        <div class="table-responsive">
            <table class="table table-striped align-middle mb-0">
                <thead><tr><th>Code</th><th>Subject</th><th>Units</th><th>Year Level</th><th>Semester</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
                <tbody>
                    @forelse($program->courses as $course)
                        <tr>
                            <td class="font-monospace fw-semibold">{{ $course->code }}</td>
                            <td>{{ $course->title }}</td>
                            <td>{{ $course->units }}</td>
                            <td>Year {{ $course->year_level }}</td>
                            <td>{{ $course->semester }} Semester</td>
                            <td><span class="badge bg-{{ $course->status === 'active' ? 'success' : 'secondary' }}">{{ ucfirst($course->status) }}</span></td>
                            <td class="text-end"><a href="{{ route('courses.edit', $course) }}" class="btn btn-sm btn-outline-secondary">Edit</a>@if($course->status === 'active')<form action="{{ route('courses.archive', $course) }}" method="POST" class="d-inline">@csrf<button class="btn btn-sm btn-outline-danger">Archive</button></form>@endif</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-3">No subjects available.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endforeach
@endsection