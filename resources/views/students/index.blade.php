@extends('layouts.app')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
    <h1 class="mb-0">Students</h1>
</div>

<form method="GET" action="{{ route('students.index') }}" class="row g-2 mb-3">
    <div class="col-md-6">
        <label for="student-search" class="visually-hidden">Search students</label>
        <input id="student-search" name="search" value="{{ $search }}" class="form-control" placeholder="Search name, student number, or email">
    </div>
    <div class="col-md-3">
        <label for="student-status" class="visually-hidden">Filter by status</label>
        <select id="student-status" name="status" class="form-select">
            <option value="">All statuses</option>
            @foreach (['active' => 'Active', 'inactive' => 'Inactive', 'dropped' => 'Dropped'] as $value => $label)
                <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3 d-flex gap-2">
        <button type="submit" class="btn btn-primary">Search</button>
        <a href="{{ route('students.index') }}" class="btn btn-outline-secondary">Clear</a>
    </div>
</form>

<div class="table-responsive">
    <table class="table table-striped bg-white align-middle">
        <thead>
            <tr><th>Student No.</th><th>Name</th><th>Email</th><th>Program</th><th>Year</th><th>Status</th><th>Registered</th><th class="text-end">Actions</th></tr>
        </thead>
        <tbody>
            @forelse ($students as $student)
                <tr>
                    <td>{{ $student->student_number }}</td>
                    <td>{{ $student->full_name }}</td>
                    <td>{{ $student->email }}</td>
                    <td>{{ $student->program?->code ?? 'N/A' }}</td>
                    <td>{{ $student->year_level }}</td>
                    <td><span class="badge text-bg-{{ $student->status === 'active' ? 'success' : ($student->status === 'dropped' ? 'danger' : 'secondary') }}">{{ ucfirst($student->status) }}</span></td>
                    <td>{{ $student->created_at?->format('M j, Y') ?? '—' }}</td>
                    <td class="text-end text-nowrap">
                        <a href="{{ route('students.show', $student) }}" class="btn btn-sm btn-outline-primary">View</a>
                        <a href="{{ route('students.edit', $student) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="8" class="text-center py-3">No students match your search.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

{{ $students->links() }}
@endsection
