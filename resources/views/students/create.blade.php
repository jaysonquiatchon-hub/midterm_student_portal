@extends('layouts.app')

@section('content')
<h1>Add Student</h1>
<form action="{{ route('students.store') }}" method="POST" class="card card-body">
    @csrf
    @include('students._form')
    <div class="mt-3">
        <button class="btn btn-primary">Save</button>
        <a href="{{ route('students.index') }}" class="btn btn-link">Cancel</a>
    </div>
</form>

@endsection