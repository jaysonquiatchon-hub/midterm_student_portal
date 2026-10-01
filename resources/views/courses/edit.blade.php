@extends('layouts.app')

@section('content')
<div class="card border-0 shadow-sm rounded-3 p-4">
    <h2 class="fw-bold text-dark mb-3">Edit Course</h2>
    <form action="{{ route('courses.update', $course) }}" method="POST">
        @csrf @method('PUT')
        @include('courses._form')
        <div class="d-flex gap-2 mt-4">
            <button type="submit" class="btn btn-primary fw-semibold">Update Course</button>
            <a href="{{ route('courses.index') }}" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>
@endsection
