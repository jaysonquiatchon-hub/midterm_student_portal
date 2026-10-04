@extends('layouts.app')

@section('content')
<div class="row justify-content-center py-4">
    <div class="col-md-8 col-lg-6">
        <section class="card auth-card border-0">
            <div class="card-body p-4 p-md-5">
                <h1 class="h3 fw-bold mb-2">Check Application Status</h1>
                <p class="text-muted">Enter the application number and email address used on your application.</p>

                <form method="POST" action="{{ route('enrollment.status.lookup') }}" class="mb-4">
                    @csrf
                    <div class="mb-3">
                        <label for="application_number" class="form-label">Application number</label>
                        <input id="application_number" name="application_number" value="{{ old('application_number') }}" class="form-control @error('application_number') is-invalid @enderror" required maxlength="40" autocomplete="off">
                        @error('application_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email address</label>
                        <input id="email" type="email" name="email" value="{{ old('email') }}" class="form-control @error('email') is-invalid @enderror" required autocomplete="email">
                        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <button type="submit" class="btn btn-primary">Check status</button>
                </form>

                @if ($application)
                    <div class="border rounded p-3" aria-live="polite">
                        <h2 class="h5">{{ $application->application_number }}</h2>
                        <p>Status: <span class="badge text-bg-{{ $application->status === 'approved' ? 'success' : ($application->status === 'rejected' ? 'danger' : 'warning') }}">{{ ucwords(str_replace('_', ' ', $application->status)) }}</span></p>
                        @if ($application->status === 'rejected')
                            <p class="mb-0"><strong>Administrator remarks:</strong> {{ $application->rejection_reason }}</p>
                        @elseif ($application->status === 'approved')
                            <p class="mb-0">Your application is approved. You can now <a href="{{ route('portal.register') }}">create your student account</a>.</p>
                        @elseif ($application->status === 'under_review')
                            <p class="mb-0">Your application is being reviewed. Check again later for an update.</p>
                        @else
                            <p class="mb-0">Your application is waiting for administrator review.</p>
                        @endif
                    </div>
                @endif
            </div>
        </section>
        <p class="text-center mt-3"><a href="{{ route('enrollment.create') }}">Submit an enrollment application</a></p>
    </div>
</div>
@endsection
