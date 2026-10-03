@extends('layouts.app')

@section('content')
@php
    $stepNames = [1 => 'Personal', 2 => 'Contact', 3 => 'Academic', 4 => 'Subjects', 5 => 'Review'];
    $selectedSubjectIds = array_map('strval', (array) old('selected_subject_ids', $draft['selected_subject_ids'] ?? []));
@endphp

<div class="mx-auto" style="max-width: 960px;">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
        <div>
            <h1 class="h2 fw-bold mb-1">Student Enrollment</h1>
            <p class="text-muted mb-0">Complete each section to submit an application for review.</p>
        </div>
        <span class="badge text-bg-light border">Step {{ $step }} of 5</span>
    </div>

    <ol class="list-unstyled d-flex flex-wrap gap-2 mb-4" aria-label="Enrollment steps">
        @foreach($stepNames as $number => $name)
            <li class="flex-fill">
                <div class="d-flex align-items-center gap-2 px-2 py-2 rounded border {{ $step === $number ? 'border-primary bg-primary-subtle text-primary fw-semibold' : ($step > $number ? 'bg-light text-dark' : 'text-muted') }}" @if($step === $number) aria-current="step" @endif>
                    <span class="d-inline-flex justify-content-center align-items-center border rounded-circle" style="width: 28px; height: 28px;">{{ $number }}</span>
                    <span>{{ $name }}</span>
                </div>
            </li>
        @endforeach
    </ol>

    @if($errors->any())
        <div class="alert alert-danger" role="alert">
            <div class="fw-semibold mb-1">Please fix the following:</div>
            <ul class="mb-0">@foreach($errors->all() as $message)<li>{{ $message }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-body p-4 p-md-5">
            @if($step < 5)
                <form method="POST" action="{{ route('enrollment.step', $step) }}">
                    @csrf
                    @if($step === 1)
                        <h2 class="h4 fw-bold mb-1">Personal Information</h2>
                        <p class="text-muted mb-4">Fields marked * are required.</p>
                        <div class="row g-3">
                            <div class="col-md-4"><label for="first_name" class="form-label">First Name *</label><input id="first_name" name="first_name" value="{{ old('first_name', $draft['first_name'] ?? '') }}" class="form-control @error('first_name') is-invalid @enderror" required maxlength="60" autocomplete="given-name">@error('first_name')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                            <div class="col-md-4"><label for="middle_name" class="form-label">Middle Name</label><input id="middle_name" name="middle_name" value="{{ old('middle_name', $draft['middle_name'] ?? '') }}" class="form-control" maxlength="60" autocomplete="additional-name"></div>
                            <div class="col-md-4"><label for="last_name" class="form-label">Last Name *</label><input id="last_name" name="last_name" value="{{ old('last_name', $draft['last_name'] ?? '') }}" class="form-control @error('last_name') is-invalid @enderror" required maxlength="60" autocomplete="family-name">@error('last_name')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                            <div class="col-md-4"><label for="suffix" class="form-label">Suffix</label><input id="suffix" name="suffix" value="{{ old('suffix', $draft['suffix'] ?? '') }}" class="form-control" maxlength="20" placeholder="Jr., III"></div>
                            <div class="col-md-4"><label for="birth_date" class="form-label">Date of Birth *</label><input id="birth_date" type="date" name="birth_date" value="{{ old('birth_date', $draft['birth_date'] ?? '') }}" max="{{ now()->subDay()->toDateString() }}" class="form-control @error('birth_date') is-invalid @enderror" required>@error('birth_date')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                            <div class="col-md-4"><label for="gender" class="form-label">Gender *</label><select id="gender" name="gender" class="form-select @error('gender') is-invalid @enderror" required><option value="">Select gender</option>@foreach(['Female', 'Male', 'Non-binary', 'Prefer not to say'] as $gender)<option value="{{ $gender }}" @selected(old('gender', $draft['gender'] ?? '') === $gender)>{{ $gender }}</option>@endforeach</select>@error('gender')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                            <div class="col-md-4"><label for="civil_status" class="form-label">Civil Status</label><select id="civil_status" name="civil_status" class="form-select"><option value="">Select status</option>@foreach(['Single', 'Married', 'Widowed', 'Separated', 'Divorced'] as $status)<option value="{{ $status }}" @selected(old('civil_status', $draft['civil_status'] ?? '') === $status)>{{ $status }}</option>@endforeach</select></div>
                            <div class="col-md-4"><label for="nationality" class="form-label">Nationality</label><input id="nationality" name="nationality" value="{{ old('nationality', $draft['nationality'] ?? '') }}" class="form-control" maxlength="80"></div>
                        </div>
                    @elseif($step === 2)
                        <h2 class="h4 fw-bold mb-1">Contact Information</h2>
                        <p class="text-muted mb-4">We will use this email for application updates.</p>
                        <div class="row g-3">
                            <div class="col-md-6"><label for="email" class="form-label">Email Address *</label><input id="email" type="email" name="email" value="{{ old('email', $draft['email'] ?? '') }}" class="form-control @error('email') is-invalid @enderror" required autocomplete="email">@error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                            <div class="col-md-6"><label for="contact_number" class="form-label">Contact Number *</label><input id="contact_number" name="contact_number" value="{{ old('contact_number', $draft['contact_number'] ?? '') }}" class="form-control @error('contact_number') is-invalid @enderror" required maxlength="30" placeholder="+63 9XX XXX XXXX">@error('contact_number')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                            <div class="col-md-6"><label for="house_block_lot" class="form-label">House / Block / Lot</label><input id="house_block_lot" name="house_block_lot" value="{{ old('house_block_lot', $draft['house_block_lot'] ?? '') }}" class="form-control" maxlength="120"></div>
                            <div class="col-md-6"><label for="street" class="form-label">Street</label><input id="street" name="street" value="{{ old('street', $draft['street'] ?? '') }}" class="form-control" maxlength="160"></div>
                            <div class="col-md-4"><label for="barangay" class="form-label">Barangay *</label><input id="barangay" name="barangay" value="{{ old('barangay', $draft['barangay'] ?? '') }}" class="form-control @error('barangay') is-invalid @enderror" required>@error('barangay')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                            <div class="col-md-4"><label for="city" class="form-label">City / Municipality *</label><input id="city" name="city" value="{{ old('city', $draft['city'] ?? '') }}" class="form-control @error('city') is-invalid @enderror" required>@error('city')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                            <div class="col-md-4"><label for="province" class="form-label">Province *</label><input id="province" name="province" value="{{ old('province', $draft['province'] ?? '') }}" class="form-control @error('province') is-invalid @enderror" required>@error('province')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                        </div>
                    @elseif($step === 3)
                        <h2 class="h4 fw-bold mb-1">Academic Information</h2>
                        <p class="text-muted mb-4">Select your program and academic details.</p>
                        <div class="row g-3">
                            <div class="col-md-12 mb-3">
                                <label for="program_id" class="form-label">Course / Program *</label>
                                <select name="program_id" id="program_id" class="form-select" required>
                                    <option value="">Select course / program</option>
                                    @foreach($programs as $program)
                                        <option value="{{ $program->id }}" @selected((string) old('program_id', $draft['program_id'] ?? '') === (string) $program->id)>
                                            {{ $program->code }} — {{ $program->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6"><label for="student_type" class="form-label">Student Type *</label><select id="student_type" name="student_type" class="form-select" required><option value="">Select student type</option>@foreach(['New Student', 'Transferee', 'Returning Student'] as $type)<option value="{{ $type }}" @selected(old('student_type', $draft['student_type'] ?? '') === $type)>{{ $type }}</option>@endforeach</select></div>
                            <div class="col-md-6"><label for="year_level" class="form-label">Year Level *</label><select id="year_level" name="year_level" class="form-select" required><option value="">Select year</option>@foreach(range(1, 4) as $year)<option value="{{ $year }}" @selected((string) old('year_level', $draft['year_level'] ?? '') === (string) $year)>Year {{ $year }}</option>@endforeach</select></div>
                            <div class="col-md-6"><label for="school_year" class="form-label">School Year *</label><input id="school_year" name="school_year" value="{{ old('school_year', $draft['school_year'] ?? (now()->year.'-'.(now()->year + 1))) }}" pattern="[0-9]{4}-[0-9]{4}" class="form-control" required></div>
                            <div class="col-md-6"><label for="semester" class="form-label">Semester *</label><select id="semester" name="semester" class="form-select" required><option value="">Select semester</option>@foreach(['1st' => '1st Semester', '2nd' => '2nd Semester', 'Summer' => 'Summer'] as $value => $label)<option value="{{ $value }}" @selected(old('semester', $draft['semester'] ?? '') === $value)>{{ $label }}</option>@endforeach</select></div>
                        </div>
                    @elseif($step === 4)
                        <h2 class="h4 fw-bold mb-1">Select Subjects</h2>
                        <p class="text-muted mb-4">Choose subjects available for your program, year level, and semester.</p>
                        @if($subjects->isEmpty())
                            <div class="alert alert-warning mb-0">No active subjects are available for this selection. <a href="{{ route('enrollment.create', ['step' => 3]) }}">Change your academic information</a>.</div>
                        @else
                            <div class="row g-3">
                                @foreach($subjects as $subject)
                                    <div class="col-md-6">
                                        <div class="form-check border rounded p-3 h-100">
                                            <input id="subject-{{ $subject->id }}" class="form-check-input" type="checkbox" name="selected_subject_ids[]" value="{{ $subject->id }}" @checked(in_array((string) $subject->id, $selectedSubjectIds, true))>
                                            <label class="form-check-label ms-2" for="subject-{{ $subject->id }}">
                                                <span class="font-monospace fw-semibold">{{ $subject->code }}</span> — {{ $subject->title }}
                                                <span class="d-block small text-muted">{{ $subject->units }} units</span>
                                            </label>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    @endif

                    <div class="d-flex flex-wrap justify-content-between gap-2 mt-4 pt-3 border-top">
                        <div class="d-flex gap-2">
                            @if($step > 1)<a class="btn btn-outline-secondary" href="{{ route('enrollment.create', ['step' => $step - 1]) }}">Back</a>@endif
                            <button class="btn btn-primary" type="submit">Next</button>
                        </div>
                        <button type="submit" formaction="{{ route('enrollment.cancel') }}" formmethod="POST" formnovalidate class="btn btn-link text-danger text-decoration-none">Cancel</button>
                    </div>
                </form>
            @else
                <form id="submit-application-form" action="{{ route('enrollment.submit') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="submission_token" value="{{ session('enrollment_submission_token') }}">

                    <div class="d-flex justify-content-between align-items-start gap-3 mb-4">
                        <div>
                            <h2 class="h4 fw-bold mb-1">Review Your Application</h2>
                            <p class="text-muted mb-0">Check your information and upload requirements before submitting.</p>
                        </div>
                        <a href="{{ route('enrollment.create', ['step' => 1]) }}" class="btn btn-outline-primary btn-sm">Edit Information</a>
                    </div>

                    <div class="row g-3">
                        <section class="col-md-4">
                            <div class="border rounded p-3 h-100">
                                <h3 class="h6 fw-bold">Personal Information</h3>
                                <dl class="row mb-0 small">
                                    <dt class="col-5">Name</dt>
                                    <dd class="col-7">{{ trim(implode(' ', array_filter([$draft['first_name'] ?? '', $draft['middle_name'] ?? '', $draft['last_name'] ?? '', $draft['suffix'] ?? '']))) }}</dd>
                                    <dt class="col-5">Birth Date</dt>
                                    <dd class="col-7">{{ $draft['birth_date'] ?? '' }}</dd>
                                    <dt class="col-5">Gender</dt>
                                    <dd class="col-7">{{ $draft['gender'] ?? '' }}</dd>
                                    <dt class="col-5">Civil Status</dt>
                                    <dd class="col-7">{{ $draft['civil_status'] ?? 'Not provided' }}</dd>
                                    <dt class="col-5">Nationality</dt>
                                    <dd class="col-7">{{ $draft['nationality'] ?? 'Not provided' }}</dd>
                                </dl>
                            </div>
                        </section>

                        <section class="col-md-4">
                            <div class="border rounded p-3 h-100">
                                <h3 class="h6 fw-bold">Contact Information</h3>
                                <dl class="row mb-0 small">
                                    <dt class="col-5">Email</dt>
                                    <dd class="col-7">{{ $draft['email'] ?? '' }}</dd>
                                    <dt class="col-5">Contact</dt>
                                    <dd class="col-7">{{ $draft['contact_number'] ?? '' }}</dd>
                                    <dt class="col-5">Address</dt>
                                    <dd class="col-7">{{ implode(', ', array_filter([$draft['house_block_lot'] ?? '', $draft['street'] ?? '', $draft['barangay'] ?? '', $draft['city'] ?? '', $draft['province'] ?? ''])) }}</dd>
                                </dl>
                            </div>
                        </section>

                        <section class="col-md-4">
                            <div class="border rounded p-3 h-100">
                                <h3 class="h6 fw-bold">Academic Information</h3>
                                <dl class="row mb-0 small">
                                    <dt class="col-5">Course</dt>
                                    <dd class="col-7">{{ $programs->firstWhere('id', (int) ($draft['program_id'] ?? 0))?->name }}</dd>
                                    <dt class="col-5">Student Type</dt>
                                    <dd class="col-7">{{ $draft['student_type'] ?? '' }}</dd>
                                    <dt class="col-5">Year Level</dt>
                                    <dd class="col-7">Year {{ $draft['year_level'] ?? '' }}</dd>
                                    <dt class="col-5">School Year</dt>
                                    <dd class="col-7">{{ $draft['school_year'] ?? '' }}</dd>
                                    <dt class="col-5">Semester</dt>
                                    <dd class="col-7">{{ $draft['semester'] ?? '' }}</dd>
                                </dl>
                            </div>
                        </section>
                    </div>

                    <section class="mt-4">
                        <h3 class="h5 fw-bold">Selected Subjects</h3>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead><tr><th>Code</th><th>Subject</th><th>Units</th></tr></thead>
                                <tbody>
                                    @foreach($subjects as $subject)
                                        <tr>
                                            <td class="font-monospace">{{ $subject->code }}</td>
                                            <td>{{ $subject->title }}</td>
                                            <td>{{ $subject->units }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot><tr><th colspan="2">{{ $subjects->count() }} subjects</th><th>{{ $subjects->sum('units') }} units</th></tr></tfoot>
                            </table>
                        </div>
                    </section>

                    @if($documentRequirements)
                        <section class="mt-4">
                            <h3 class="h5 fw-bold">Required Documents</h3>
                            <p class="small text-muted">Upload a PDF, JPG, or PNG for each requirement. Maximum 1 MB per file.</p>
                            <div class="row g-3">
                                @foreach($documentRequirements as $key => $label)
                                    <div class="col-md-6">
                                        <label for="requirement-{{ $key }}" class="form-label">{{ $label }} *</label>
                                        <input id="requirement-{{ $key }}" type="file" name="requirements[{{ $key }}]" accept=".pdf,.jpg,.jpeg,.png" required class="form-control @error('requirements.'.$key) is-invalid @enderror">
                                        @error('requirements.'.$key)<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                @endforeach
                            </div>
                        </section>
                    @endif

                    <div class="alert alert-info mt-4 mb-0">Your application will be marked Pending. You will only become officially enrolled after administrator approval.</div>

                    <div class="d-flex flex-wrap justify-content-between gap-2 mt-4 pt-3 border-top">
                        <div class="d-flex gap-2">
                            <a href="{{ route('enrollment.create', ['step' => 3]) }}" class="btn btn-outline-secondary">Back</a>
                            <button id="confirm-submit-button" class="btn btn-success fw-semibold" type="submit">Submit Enrollment</button>
                        </div>
                        <button type="submit" formaction="{{ route('enrollment.cancel') }}" formmethod="POST" formnovalidate class="btn btn-link text-danger text-decoration-none">Cancel</button>
                    </div>
                </form>
            @endif
        </div>
    </div>
</div>

@if($step === 5)
<script>
   document.getElementById('submit-application-form').addEventListener('submit', (e) => {
    const fileInputs = document.querySelectorAll('input[type="file"]');
    const MAX_SIZE_BYTES = 1020 * 1024;

    for (const input of fileInputs) {
        if (input.files[0] && input.files[0].size > MAX_SIZE_BYTES) {
            alert(`The file "${input.files[0].name}" exceeds the maximum allowed size of 1 MB.`);
            e.preventDefault();
            return;
        }
    }

    const button = document.getElementById('confirm-submit-button');
    button.disabled = true;
    button.innerHTML = '<span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>Submitting...';
});
</script>

@endif
@endsection
