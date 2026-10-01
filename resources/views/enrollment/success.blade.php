@extends('layouts.app')

@section('content')
<div class="row justify-content-center mt-4">
    <div class="col-md-8 col-lg-7">
        <div class="card border-0 shadow-sm rounded-3 text-center">
            <div class="card-body p-4 p-md-5">
                <div class="text-success display-5 mb-3" aria-hidden="true">&#10003;</div>
                <h1 class="h3 fw-bold">Enrollment Application Submitted</h1>
                <p class="text-muted">Your application has been received and is waiting for administrator review. You are not officially enrolled yet.</p>
                <div class="border rounded p-3 my-4 text-start">
                    <dl class="row mb-0"><dt class="col-sm-5">Application Number</dt><dd class="col-sm-7 fw-semibold">{{ $application->application_number }}</dd><dt class="col-sm-5">Status</dt><dd class="col-sm-7"><span class="badge text-bg-secondary">Pending Review</span></dd><dt class="col-sm-5">Submitted</dt><dd class="col-sm-7">{{ $application->submitted_at?->format('F j, Y g:i A') }}</dd></dl>
                </div>
                <p class="mb-4">The administrator will review your enrollment application. Keep your application number for reference.</p>
                <a href="{{ route('enrollment.create') }}" class="btn btn-primary">Start Another Application</a>
            </div>
        </div>
    </div>
</div>
@endsection
