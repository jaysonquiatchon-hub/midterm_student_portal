@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="mb-0">Course Offerings</h1>
    @if (auth()->user()?->role === 'admin')
        <a href="{{ route('students.index') }}" class="btn btn-outline-primary">Student Directory</a>
    @endif
</div>

<div class="table-responsive">
    <table class="table table-striped bg-white align-middle">
        <thead>
            <tr>
                <th>Code</th>
                <th>Title</th>
                <th>Units</th>
                <th>Year Level</th>
                <th>Program</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($courses as $course)
                <tr>
                    <td class="fw-semibold">{{ $course->code }}</td>
                    <td>{{ $course->title }}</td>
                    <td>{{ $course->units }}</td>
                    <td>Year {{ $course->year_level }}</td>
                    <td>{{ $course->program?->name ?? 'Unassigned' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="text-center text-muted py-3">No courses are currently available.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{ $courses->links() }}
@endsection
