@extends('layouts.app')

@section('content')
<div class="row justify-content-center mt-4">
    <div class="col-md-8 col-lg-7">
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-body p-4">
                <div class="text-center mb-4">
                    <h3 class="fw-bold text-dark mb-1">Create Student Account</h3>
                    <p class="text-muted small mb-0">Register your student information and sign-in details</p>
                </div>
                <form action="{{ route('portal.register.submit') }}" method="POST">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="first_name" class="form-label fw-semibold">First Name</label>
                            <input id="first_name" name="first_name" value="{{ old('first_name') }}" class="form-control @error('first_name') is-invalid @enderror" required maxlength="60" autocomplete="given-name">
                            @error('first_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label for="last_name" class="form-label fw-semibold">Last Name</label>
                            <input id="last_name" name="last_name" value="{{ old('last_name') }}" class="form-control @error('last_name') is-invalid @enderror" required maxlength="60" autocomplete="family-name">
                            @error('last_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label for="student_number" class="form-label fw-semibold">Student Number</label>
                            <input id="student_number" name="student_number" value="{{ old('student_number') }}" class="form-control @error('student_number') is-invalid @enderror" required maxlength="20">
                            @error('student_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label for="email" class="form-label fw-semibold">Email</label>
                            <input id="email" type="email" name="email" value="{{ old('email') }}" class="form-control @error('email') is-invalid @enderror" required autocomplete="email">
                            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label for="birth_date" class="form-label fw-semibold">Birth Date</label>
                            <input id="birth_date" type="date" name="birth_date" value="{{ old('birth_date') }}" class="form-control @error('birth_date') is-invalid @enderror" required>
                            @error('birth_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label for="program_id" class="form-label fw-semibold">Program</label>
                            <select id="program_id" name="program_id" class="form-select @error('program_id') is-invalid @enderror" required>
                                <option value="">Select a program</option>
                                @foreach($programs as $program)
                                    <option value="{{ $program->id }}" @selected(old('program_id') == $program->id)>{{ $program->code }} - {{ $program->name }}</option>
                                @endforeach
                            </select>
                            @error('program_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label for="year_level" class="form-label fw-semibold">Year Level</label>
                            <select id="year_level" name="year_level" class="form-select @error('year_level') is-invalid @enderror" required>
                                <option value="">Select year level</option>
                                @foreach(range(1, 4) as $year)
                                    <option value="{{ $year }}" @selected(old('year_level') == $year)>Year {{ $year }}</option>
                                @endforeach
                            </select>
                            @error('year_level')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label for="password" class="form-label fw-semibold">Password</label>
                            <input id="password" type="password" name="password" class="form-control @error('password') is-invalid @enderror" required minlength="8" autocomplete="new-password">
                            @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label for="password_confirmation" class="form-label fw-semibold">Confirm Password</label>
                            <input id="password_confirmation" type="password" name="password_confirmation" class="form-control" required minlength="8" autocomplete="new-password">
                        </div>
                    </div>
                    <div class="d-grid mt-4">
                        <button type="submit" class="btn btn-primary fw-semibold py-2">Create Account</button>
                    </div>
                </form>
                <p class="text-center text-muted small mt-3 mb-0">Already registered? <a href="{{ route('portal.login') }}">Log in</a></p>
            </div>
        </div>
    </div>
</div>
@endsection
