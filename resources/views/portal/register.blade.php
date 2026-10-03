@extends('layouts.app')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-7 col-lg-6">
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-body p-4 p-md-5">
                    <h1 class="h3 text-center fw-bold mb-1">Create Student Account</h1>
                    <p class="text-center text-muted mb-4">Use the details from your approved enrollment notice.</p>

                    @if ($errors->any())
                        <div class="alert alert-danger" role="alert">
                            <strong>Please review the following:</strong>
                            <ul class="mb-0 mt-1">
                                @foreach ($errors->all() as $message)
                                    <li>{{ $message }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('portal.register.submit') }}">
                        @csrf
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label for="first_name" class="form-label fw-semibold">First name</label>
                                <input type="text" class="form-control @error('first_name') is-invalid @enderror" id="first_name" name="first_name" value="{{ old('first_name') }}" autocomplete="given-name" placeholder="Your first name" required autofocus>
                                @error('first_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label for="last_name" class="form-label fw-semibold">Last name</label>
                                <input type="text" class="form-control @error('last_name') is-invalid @enderror" id="last_name" name="last_name" value="{{ old('last_name') }}" autocomplete="family-name" placeholder="Your last name" required>
                                @error('last_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="student_number" class="form-label fw-semibold">Student number</label>
                            <input type="text" class="form-control @error('student_number') is-invalid @enderror" id="student_number" name="student_number" value="{{ old('student_number') }}" autocomplete="username" placeholder="For example, 2026-00032" required>
                            <div class="form-text">Your student number is included in your enrollment approval.</div>
                            @error('student_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label fw-semibold">Email address</label>
                            <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email') }}" autocomplete="email" placeholder="name@example.com" required>
                            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label for="password" class="form-label fw-semibold">Password</label>
                                <div class="input-group">
                                    <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password" autocomplete="new-password" placeholder="At least 8 characters" required>
                                    <button class="btn btn-outline-secondary" type="button" data-password-toggle="password" aria-label="Show password" aria-pressed="false">
                                        <svg aria-hidden="true" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>
                                    </button>
                                </div>
                                @error('password')<div class="d-block invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label for="password_confirmation" class="form-label fw-semibold">Confirm password</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" autocomplete="new-password" placeholder="Re-enter your password" required>
                                    <button class="btn btn-outline-secondary" type="button" data-password-toggle="password_confirmation" aria-label="Show confirmation password" aria-pressed="false">
                                        <svg aria-hidden="true" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">Create account</button>
                    </form>
                    <div class="text-center mt-3 small">Already registered? <a href="{{ route('student.login') }}">Student log in</a></div>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    document.querySelectorAll('[data-password-toggle]').forEach((button) => {
        button.addEventListener('click', () => {
            const input = document.getElementById(button.dataset.passwordToggle);
            const visible = input.type === 'password';
            input.type = visible ? 'text' : 'password';
            button.setAttribute('aria-pressed', String(visible));
            button.setAttribute('aria-label', visible ? 'Hide password' : 'Show password');
        });
    });
</script>
@endsection