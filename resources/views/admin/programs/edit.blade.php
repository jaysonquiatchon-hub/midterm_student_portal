@extends('layouts.app')
@section('content')
<div class="card border-0 shadow-sm rounded-3 p-4"><h1 class="h3 fw-bold mb-3">Edit Program</h1><form method="POST" action="{{ route('programs.update', $program) }}">@csrf @method('PUT') @include('admin.programs._form')<div class="d-flex gap-2 mt-4"><button class="btn btn-primary">Update Program</button><a href="{{ route('programs.index') }}" class="btn btn-secondary">Cancel</a></div></form></div>
@endsection
