@extends('layouts.app')

@section('content')
<div class="container my-4">
    <h1 class="h3 fw-bold mb-3">Edit Student</h1>
    <form action="{{ route('students.update', $student) }}" method="POST" class="card card-body shadow-sm border-0">
        @csrf
        @method('PUT')

        @include('students._form')

        <div class="mt-3">
            <button type="submit" class="btn btn-primary fw-semibold">Update Student</button>
            <a href="{{ route('students.index') }}" class="btn btn-link text-secondary">Cancel</a>
        </div>
    </form>
</div>
@endsection