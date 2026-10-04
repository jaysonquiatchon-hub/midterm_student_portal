@extends('layouts.app')

@section('content')
<div class="row justify-content-center align-items-center mt-5">
    <div class="col-md-5 col-lg-4">
        
        <!-- Card Container styled to match the Student Directory UI -->
        <div class="card auth-card rounded-3">
            <div class="card-body p-4">
                <div class="text-center mb-4">
                    <h3 class="fw-bold text-dark mb-1">{{ $portalTitle }}</h3>
                <p class="text-muted small mb-0">{{ $portalDescription }}</p>
                </div>

                <form action="{{ route($loginRoute) }}" method="POST">
                    @csrf

                    <!-- Username Field with Sticky Input -->
                    <div class="mb-3">
                        <label for="username" class="form-label fw-semibold">{{ $isStudentLogin ? 'Email address or student ID' : 'Email address' }}</label>
                        <input type="text"
                               name="username"
                               id="username"
                               class="form-control @error('username') is-invalid @enderror"
                               value="{{ old('username') }}"
                               placeholder="{{ $isStudentLogin ? 'name@example.com or student ID' : 'admin@example.com' }}"
                               autocomplete="username"
                               required 
                               autofocus>
                        @error('username')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Password Field -->
                    <div class="mb-3">
                        <label for="password" class="form-label fw-semibold">Password</label>
                        <div class="input-group">
                            <input type="password"
                                   name="password"
                                   id="password"
                                   class="form-control @error('password') is-invalid @enderror"
                                   placeholder="Enter your password"
                                   autocomplete="current-password"
                                   required>
                            <button class="btn btn-outline-secondary" type="button" data-password-toggle="password" aria-label="Show password" aria-pressed="false">
                                <svg aria-hidden="true" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>
                            </button>
                        </div>
                        @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Primary Submit Button -->
                    <div class="d-grid gap-2 mt-4">
                        <button type="submit" class="btn btn-primary fw-semibold py-2">
                            Access Portal
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="text-center mt-3">
            @if ($isStudentLogin)
                <a href="{{ route('portal.register') }}" class="btn btn-outline-primary btn-sm">Create student account</a>
                <a href="{{ route('enrollment.create') }}" class="btn btn-outline-primary btn-sm">Start an application</a>
                <a href="{{ route('enrollment.status') }}" class="btn btn-link btn-sm">Check application status</a>
            @endif
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
