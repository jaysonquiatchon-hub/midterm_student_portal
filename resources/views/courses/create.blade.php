@extends('layouts.app')

@section('content')
<div class="card border-0 shadow-sm rounded-3 p-4">
    <h2 class="fw-bold text-dark mb-3">Add Course</h2>
    <form action="{{ route('courses.store') }}" method="POST">
        @csrf
        @include('courses._form', ['course' => null])
        <div class="d-flex gap-2 mt-4">
            <button type="submit" class="btn btn-primary fw-semibold">Save Course</button>
            <a href="{{ route('courses.index') }}" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>
@endsection
