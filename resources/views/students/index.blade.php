@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1>Students</h1>
    <a href="{{ route('students.create') }}" class="btn btn-primary">+ Add Student</a>
</div>

<table class="table table-striped bg-white align-middle">
    <thead>
        <tr>
            <th>Student No.</th>
            <th>Name</th>
            <th>Email</th>
            <th>Program</th>
            <th>Year</th>
            <th class="text-end">Actions</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($students as $student)
        <tr>
            <td>{{ $student->student_number }}</td>
            <td>{{ $student->first_name }} {{ $student->last_name }}</td>
            <td>{{ $student->email }}</td>
            <td>{{ $student->program->code ?? 'N/A' }}</td>
            <td>{{ $student->year_level }}</td>
            <td class="text-end">
                <a href="{{ route('students.show', $student) }}" class="btn btn-sm btn-outline-primary">View</a>
                <a href="{{ route('students.edit', $student) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                <form action="{{ route('students.destroy', $student) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this student?')">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-sm btn-outline-danger">Delete</button>
                </form>
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="6" class="text-center py-3">No students found.</td>
        </tr>
        @endforelse
    </tbody>
</table>

{{ $students->links() }}
@endsection