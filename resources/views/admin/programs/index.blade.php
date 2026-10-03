@extends('layouts.app')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3"><h1 class="h2 mb-0">Courses / Programs</h1><a href="{{ route('programs.create') }}" class="btn btn-primary">+ Add Program</a></div>
<div class="table-responsive"><table class="table table-striped bg-white align-middle"><thead><tr><th>Code</th><th>Program</th><th>Subjects</th><th>Status</th><th class="text-end">Actions</th></tr></thead><tbody>
@forelse($programs as $program)
<tr><td class="font-monospace fw-semibold">{{ $program->code }}</td><td>{{ $program->name }}</td><td>{{ $program->courses_count }}</td><td><span class="badge bg-{{ $program->status === 'active' ? 'success' : 'secondary' }}">{{ ucfirst($program->status) }}</span></td><td class="text-end"><a href="{{ route('programs.edit', $program) }}" class="btn btn-sm btn-outline-secondary">Edit</a>@if($program->status === 'active')<form action="{{ route('programs.archive', $program) }}" method="POST" class="d-inline">@csrf<button class="btn btn-sm btn-outline-danger">Archive</button></form>@endif</td></tr>
@empty<tr><td colspan="5" class="text-center text-muted py-3">No programs found.</td></tr>@endforelse
</tbody></table></div>
{{ $programs->links() }}
@endsection
