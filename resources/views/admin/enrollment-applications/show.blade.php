@extends('layouts.app')

@section('content')
<div class="card border-0 shadow-sm rounded-3 p-4">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
        <div><h1 class="h3 fw-bold mb-1">{{ $application->application_number }}</h1><p class="text-muted mb-0">Submitted {{ $application->submitted_at?->format('F j, Y g:i A') }}</p></div>
        <span class="badge bg-{{ $application->status === 'approved' ? 'success' : ($application->status === 'rejected' ? 'danger' : 'secondary') }} fs-6">{{ ucwords(str_replace('_', ' ', $application->status)) }}</span>
    </div>
    @if(session('warning'))<div class="alert alert-warning">{{ session('warning') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

    <div class="row g-3">
        <section class="col-md-6"><div class="border rounded p-3 h-100"><h2 class="h5 fw-bold">Personal Information</h2><dl class="row mb-0"><dt class="col-sm-5">Full Name</dt><dd class="col-sm-7">{{ $application->full_name }}</dd><dt class="col-sm-5">Date of Birth</dt><dd class="col-sm-7">{{ $application->birth_date->format('F j, Y') }}</dd><dt class="col-sm-5">Gender</dt><dd class="col-sm-7">{{ $application->gender }}</dd><dt class="col-sm-5">Civil Status</dt><dd class="col-sm-7">{{ $application->civil_status ?? 'Not provided' }}</dd><dt class="col-sm-5">Nationality</dt><dd class="col-sm-7">{{ $application->nationality ?? 'Not provided' }}</dd></dl></div></section>
        <section class="col-md-6"><div class="border rounded p-3 h-100"><h2 class="h5 fw-bold">Contact Information</h2><dl class="row mb-0"><dt class="col-sm-5">Email</dt><dd class="col-sm-7">{{ $application->email }}</dd><dt class="col-sm-5">Contact Number</dt><dd class="col-sm-7">{{ $application->contact_number }}</dd><dt class="col-sm-5">Address</dt><dd class="col-sm-7">{{ $application->address }}</dd></dl></div></section>
        <section class="col-md-6"><div class="border rounded p-3 h-100"><h2 class="h5 fw-bold">Academic Information</h2><dl class="row mb-0"><dt class="col-sm-5">Course</dt><dd class="col-sm-7">{{ $application->program->name }}</dd><dt class="col-sm-5">Student Type</dt><dd class="col-sm-7">{{ $application->student_type }}</dd><dt class="col-sm-5">Year Level</dt><dd class="col-sm-7">{{ $application->year_level }}</dd><dt class="col-sm-5">School Year</dt><dd class="col-sm-7">{{ $application->school_year }}</dd><dt class="col-sm-5">Semester</dt><dd class="col-sm-7">{{ $application->semester }}</dd></dl></div></section>
        <section class="col-md-6"><div class="border rounded p-3 h-100"><h2 class="h5 fw-bold">Selected Subjects</h2><div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>Code</th><th>Subject</th><th>Units</th></tr></thead><tbody>@forelse($application->subjects as $subject)<tr><td class="font-monospace">{{ $subject->code }}</td><td>{{ $subject->title }}</td><td>{{ $subject->units }}</td></tr>@empty<tr><td colspan="3" class="text-muted">No subjects selected.</td></tr>@endforelse</tbody></table></div><div class="small fw-semibold mt-2">{{ $application->subjects->count() }} subjects · {{ $application->subjects->sum('units') }} units</div></div></section>
    </div>

    <section class="mt-4">
        <h2 class="h5 fw-bold">Submitted Requirements</h2>
        @if($application->documents->isEmpty())
            <p class="text-muted mb-0">No documents were submitted.</p>
        @else
            <ul class="list-group">
                @foreach($application->documents as $document)
                    <li class="list-group-item d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <span>{{ $document->label }} <small class="text-muted">({{ $document->original_name }})</small></span>
                        <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.enrollment-applications.documents.show', [$application, $document]) }}">Download</a>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>

    @if($application->status === 'rejected')
        <div class="alert alert-danger mt-3"><strong>Rejection reason:</strong> {{ $application->rejection_reason }}<div class="small mt-1">{{ $application->rejector?->name }} · {{ $application->rejected_at?->format('M j, Y g:i A') }}</div></div>
    @endif
    @if($application->status === 'approved')
        <div class="alert alert-success mt-3">
            <strong>Student ID:</strong> {{ $application->student?->student_id }}
            @if($application->applicant_user_id)
                <div class="mt-2">The student already has portal access. They can sign in with their registered email and password.</div>
            @else
                <div class="mt-2">The student can create a portal account after approval using their name, email address, and student number.</div>
            @endif
        </div>
    @endif

    @if(in_array($application->status, ['pending', 'under_review'], true))
        <div class="d-flex flex-wrap gap-2 mt-4">
            <form action="{{ route('admin.enrollment-applications.process', $application) }}" method="POST">@csrf<input type="hidden" name="action" value="under_review"><button class="btn btn-outline-primary">Mark Under Review</button></form>
            <button class="btn btn-success" type="button" data-bs-toggle="modal" data-bs-target="#approve-application">Approve Enrollment</button>
            <button class="btn btn-outline-danger" type="button" data-bs-toggle="modal" data-bs-target="#reject-application">Reject Enrollment</button>
        </div>
        <div class="modal fade" id="approve-application" tabindex="-1" aria-labelledby="approve-application-label" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><div class="modal-content"><div class="modal-header"><h2 class="modal-title fs-5" id="approve-application-label">Approve this enrollment?</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>        <div class="modal-body">This will approve the official enrollment and generate a Student ID. If the applicant created a portal account, that access will remain available.</div><div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button><form action="{{ route('admin.enrollment-applications.process', $application) }}" method="POST" class="approval-form">@csrf<input type="hidden" name="action" value="approve"><button class="btn btn-success approval-button">Approve</button></form></div></div></div></div>
        <div class="modal fade" id="reject-application" tabindex="-1" aria-labelledby="reject-application-label" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><div class="modal-content"><form action="{{ route('admin.enrollment-applications.process', $application) }}" method="POST">@csrf<input type="hidden" name="action" value="reject"><div class="modal-header"><h2 class="modal-title fs-5" id="reject-application-label">Reject Enrollment</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div><div class="modal-body"><label for="rejection_reason" class="form-label">Reason for rejection *</label><textarea id="rejection_reason" name="rejection_reason" class="form-control" rows="4" required maxlength="2000"></textarea></div><div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button><button class="btn btn-danger">Reject Enrollment</button></div></form></div></div></div></div>
    @endif

    @if($application->emailHistories->isNotEmpty() || $application->status === 'approved')
        <h2 class="h5 fw-bold mt-4">Email History</h2>
        <p class="small text-muted">“Sent” means the mail provider accepted the message; final inbox delivery depends on that provider and recipient. Test mailers only capture messages and do not deliver them.</p>
        <div class="table-responsive"><table class="table table-sm align-middle"><thead><tr><th>Recipient</th><th>Type</th><th>Subject</th><th>Status</th><th>Attempted At</th><th>Details</th></tr></thead><tbody>@forelse($application->emailHistories as $history)<tr><td>{{ $history->recipient }}</td><td>{{ $history->type }}</td><td>{{ $history->subject }}</td><td><span class="badge bg-{{ $history->status === 'sent' ? 'success' : ($history->status === 'failed' ? 'danger' : ($history->status === 'captured' ? 'info' : 'secondary')) }}">{{ $history->status === 'captured' ? 'Captured (not delivered)' : ucfirst($history->status) }}</span></td><td>{{ $history->sent_at?->format('M j, Y g:i A') ?? '—' }}</td><td>{{ $history->error_message }}</td></tr>@empty<tr><td colspan="6" class="text-muted text-center">No email attempts recorded.</td></tr>@endforelse</tbody></table></div>
        @if(in_array($application->status, ['approved', 'rejected'], true))
        <form action="{{ route('admin.enrollment-applications.resend', $application) }}" method="POST" class="mt-2">@csrf<button class="btn btn-outline-primary">{{ $application->status === 'rejected' ? 'Resend Rejection Email' : 'Resend Email' }}</button></form>
        @endif
    @endif
    <div class="mt-4"><a href="{{ route('admin.enrollment-applications.index') }}" class="btn btn-secondary">Back to Applications</a></div>
</div>
<script>
    document.querySelectorAll('.approval-form').forEach((form) => form.addEventListener('submit', () => {
        const button = form.querySelector('.approval-button');
        button.disabled = true;
        button.textContent = 'Approving...';
    }));
</script>
@endsection
