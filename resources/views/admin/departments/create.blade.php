@extends('layouts.app')
@section('content')
<div class="card border-0 shadow-sm rounded-3 p-4"><h1 class="h3 fw-bold mb-3">Add Department</h1><form method="POST" action="{{ route('departments.store') }}">@csrf @include('admin.departments._form', ['department' => null])<div class="d-flex gap-2 mt-4"><button class="btn btn-primary">Save Department</button><a href="{{ route('departments.index') }}" class="btn btn-secondary">Cancel</a></div></form></div>
@endsection
