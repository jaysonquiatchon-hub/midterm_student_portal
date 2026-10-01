@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3"><h1 class="h2 mb-0">Departments</h1><a href="{{ route('departments.create') }}" class="btn btn-primary">+ Add Department</a></div>
<div class="table-responsive"><table class="table table-striped bg-white align-middle"><thead><tr><th>Code</th><th>Department</th><th>Programs</th><th>Status</th><th class="text-end">Actions</th></tr></thead><tbody>
@forelse($departments as $department)
<tr><td class="font-monospace fw-semibold">{{ $department->code }}</td><td>{{ $department->name }}</td><td>{{ $department->programs_count }}</td><td><span class="badge bg-{{ $department->status === 'active' ? 'success' : 'secondary' }}">{{ ucfirst($department->status) }}</span></td><td class="text-end"><a href="{{ route('departments.edit', $department) }}" class="btn btn-sm btn-outline-secondary">Edit</a>@if($department->status === 'active')<form action="{{ route('departments.archive', $department) }}" method="POST" class="d-inline">@csrf<button class="btn btn-sm btn-outline-danger">Archive</button></form>@endif</td></tr>
@empty<tr><td colspan="5" class="text-center text-muted py-3">No departments found.</td></tr>@endforelse
</tbody></table></div>
{{ $departments->links() }}
@endsection
