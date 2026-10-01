@extends('layouts.app')

@section('content')
<div class="row justify-content-center align-items-center mt-5">
    <div class="col-md-5 col-lg-4">
        
        <!-- Interception & Logout Flash Warning Alerts -->
        @if(session('warning'))
            <div class="alert alert-warning alert-dismissible fade show text-center mb-3" role="alert">
                {{ session('warning') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <!-- Card Container styled to match the Student Directory UI -->
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-body p-4">
                <div class="text-center mb-4">
                    <h3 class="fw-bold text-dark mb-1">Student Portal</h3>
                    <p class="text-muted small mb-0">Enter your email or admin username to continue</p>
                </div>

                <form action="{{ route('portal.login.submit') }}" method="POST">
                    @csrf

                    <!-- Username Field with Sticky Input -->
                    <div class="mb-3">
                        <label for="username" class="form-label fw-semibold">Email or Admin Username</label>
                        <input type="text" 
                               name="username" 
                               id="username" 
                               class="form-control @error('username') is-invalid @enderror" 
                               value="{{ old('username') }}" 
                               placeholder="you@example.com or admin" 
                               required 
                               autofocus>
                        @error('username')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Password Field -->
                    <div class="mb-3">
                        <label for="password" class="form-label fw-semibold">Password</label>
                        <input type="password" 
                               name="password" 
                               id="password" 
                               class="form-control @error('password') is-invalid @enderror" 
                               placeholder="••••••••" 
                               required>
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

        <p class="text-center text-muted small mt-3">New student? <a href="{{ route('portal.register') }}">Create an account</a></p>
        <p class="text-center text-muted small mt-2">Applying for enrollment? <a href="{{ route('enrollment.create') }}">Start an application</a></p>
    </div>
</div>
@endsection