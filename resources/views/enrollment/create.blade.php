@extends('layouts.app')

@section('content')
@php
    $stepNames = [1 => 'Personal', 2 => 'Contact', 3 => 'Academic', 4 => 'Subjects', 5 => 'Review'];
    $selectedSubjectIds = array_map('intval', old('selected_subject_ids', $draft['selected_subject_ids'] ?? $subjects->pluck('id')->all()));
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
                        <p class="text-muted mb-4">Programs are filtered by the department you choose.</p>
                        <div class="row g-3">
                            <div class="col-md-6"><label for="department_id" class="form-label">Department *</label><select id="department_id" name="department_id" class="form-select @error('department_id') is-invalid @enderror" required><option value="">Select department</option>@foreach($departments as $department)<option value="{{ $department->id }}" @selected((string) old('department_id', $draft['department_id'] ?? '') === (string) $department->id)>{{ $department->name }}</option>@endforeach</select>@error('department_id')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                            <div class="col-md-6"><label for="program_id" class="form-label">Course / Program *</label><select id="program_id" name="program_id" class="form-select @error('program_id') is-invalid @enderror" required><option value="">Select course / program</option>@foreach($programs as $program)<option value="{{ $program->id }}" data-department-id="{{ $program->department_id }}" @selected((string) old('program_id', $draft['program_id'] ?? '') === (string) $program->id)>{{ $program->name }}</option>@endforeach</select>@error('program_id')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                            <div class="col-md-6"><label for="student_type" class="form-label">Student Type *</label><select id="student_type" name="student_type" class="form-select" required><option value="">Select student type</option>@foreach(['New Student', 'Transferee', 'Returning Student'] as $type)<option value="{{ $type }}" @selected(old('student_type', $draft['student_type'] ?? '') === $type)>{{ $type }}</option>@endforeach</select></div>
                            <div class="col-md-6"><label for="year_level" class="form-label">Year Level *</label><select id="year_level" name="year_level" class="form-select" required><option value="">Select year</option>@foreach(range(1, 4) as $year)<option value="{{ $year }}" @selected((string) old('year_level', $draft['year_level'] ?? '') === (string) $year)>Year {{ $year }}</option>@endforeach</select></div>
                            <div class="col-md-6"><label for="school_year" class="form-label">School Year *</label><input id="school_year" name="school_year" value="{{ old('school_year', $draft['school_year'] ?? (now()->year.'-'.(now()->year + 1))) }}" pattern="[0-9]{4}-[0-9]{4}" class="form-control" required></div>
                            <div class="col-md-6"><label for="semester" class="form-label">Semester *</label><select id="semester" name="semester" class="form-select" required><option value="">Select semester</option>@foreach(['1st' => '1st Semester', '2nd' => '2nd Semester', 'Summer' => 'Summer'] as $value => $label)<option value="{{ $value }}" @selected(old('semester', $draft['semester'] ?? '') === $value)>{{ $label }}</option>@endforeach</select></div>
                        </div>
                    @elseif($step === 4)
                        <h2 class="h4 fw-bold mb-1">Subject Selection</h2>
                        <p class="text-muted mb-3">Subjects shown match your selected program, year level, and semester. Curriculum subjects are selected by default.</p>
                        @if($subjects->isEmpty())
                            <div class="alert alert-warning">No active subjects are assigned to this program, year level, and semester. Please go back and choose another semester or contact the school.</div>
                        @else
                            <div class="list-group mb-3" id="subject-list">
                                @foreach($subjects as $subject)
                                    <label class="list-group-item d-flex align-items-center gap-3">
                                        <input class="form-check-input subject-choice" type="checkbox" name="selected_subject_ids[]" value="{{ $subject->id }}" data-units="{{ $subject->units }}" @checked(in_array($subject->id, $selectedSubjectIds, true))>
                                        <span class="flex-grow-1"><span class="font-monospace fw-semibold">{{ $subject->code }}</span> - {{ $subject->title }}</span>
                                        <span class="text-muted text-nowrap">{{ $subject->units }} units</span>
                                    </label>
                                @endforeach
                            </div>
                            @error('selected_subject_ids')<div class="text-danger small mb-2">{{ $message }}</div>@enderror
                            @error('selected_subject_ids.*')<div class="text-danger small mb-2">{{ $message }}</div>@enderror
                            <div class="d-flex gap-4 fw-semibold"><span>Total Subjects: <span id="subject-count">0</span></span><span>Total Units: <span id="unit-count">0</span></span></div>
                        @endif
                    @endif

                    <div class="d-flex flex-wrap justify-content-between gap-2 mt-4 pt-3 border-top">
                        <div class="d-flex gap-2">
                            @if($step > 1)<a class="btn btn-outline-secondary" href="{{ route('enrollment.create', ['step' => $step - 1]) }}">Back</a>@endif
                            <button class="btn btn-primary" type="submit" @if($step === 4 && $subjects->isEmpty()) disabled @endif>Next</button>
                        </div>
                        <button type="submit" formaction="{{ route('enrollment.cancel') }}" formmethod="POST" formnovalidate class="btn btn-link text-danger text-decoration-none">Cancel</button>
                    </div>
                </form>
            @else
                <div class="d-flex justify-content-between align-items-start gap-3 mb-4">
                    <div><h2 class="h4 fw-bold mb-1">Review Your Application</h2><p class="text-muted mb-0">Check your information. You can go back and edit before submitting.</p></div>
                    <a href="{{ route('enrollment.create', ['step' => 1]) }}" class="btn btn-outline-primary btn-sm">Edit Information</a>
                </div>
                <div class="row g-3">
                    <section class="col-md-6"><div class="border rounded p-3 h-100"><h3 class="h6 fw-bold">Personal Information</h3><dl class="row mb-0 small"><dt class="col-5">Name</dt><dd class="col-7">{{ trim(implode(' ', array_filter([$draft['first_name'] ?? '', $draft['middle_name'] ?? '', $draft['last_name'] ?? '', $draft['suffix'] ?? '']))) }}</dd><dt class="col-5">Birth Date</dt><dd class="col-7">{{ $draft['birth_date'] ?? '' }}</dd><dt class="col-5">Gender</dt><dd class="col-7">{{ $draft['gender'] ?? '' }}</dd><dt class="col-5">Civil Status</dt><dd class="col-7">{{ $draft['civil_status'] ?? 'Not provided' }}</dd><dt class="col-5">Nationality</dt><dd class="col-7">{{ $draft['nationality'] ?? 'Not provided' }}</dd></dl></div></section>
                    <section class="col-md-6"><div class="border rounded p-3 h-100"><h3 class="h6 fw-bold">Contact Information</h3><dl class="row mb-0 small"><dt class="col-5">Email</dt><dd class="col-7">{{ $draft['email'] ?? '' }}</dd><dt class="col-5">Contact</dt><dd class="col-7">{{ $draft['contact_number'] ?? '' }}</dd><dt class="col-5">Address</dt><dd class="col-7">{{ implode(', ', array_filter([$draft['house_block_lot'] ?? '', $draft['street'] ?? '', $draft['barangay'] ?? '', $draft['city'] ?? '', $draft['province'] ?? ''])) }}</dd></dl></div></section>
                    <section class="col-md-6"><div class="border rounded p-3 h-100"><h3 class="h6 fw-bold">Academic Information</h3><dl class="row mb-0 small"><dt class="col-5">Department</dt><dd class="col-7">{{ $departments->firstWhere('id', (int) ($draft['department_id'] ?? 0))?->name }}</dd><dt class="col-5">Course</dt><dd class="col-7">{{ $programs->firstWhere('id', (int) ($draft['program_id'] ?? 0))?->name }}</dd><dt class="col-5">Student Type</dt><dd class="col-7">{{ $draft['student_type'] ?? '' }}</dd><dt class="col-5">Year Level</dt><dd class="col-7">Year {{ $draft['year_level'] ?? '' }}</dd><dt class="col-5">School Year</dt><dd class="col-7">{{ $draft['school_year'] ?? '' }}</dd><dt class="col-5">Semester</dt><dd class="col-7">{{ $draft['semester'] ?? '' }}</dd></dl></div></section>
                    <section class="col-md-6"><div class="border rounded p-3 h-100"><h3 class="h6 fw-bold">Subjects</h3><div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>Code</th><th>Subject</th><th>Units</th></tr></thead><tbody>@forelse($subjects as $subject)<tr><td class="font-monospace">{{ $subject->code }}</td><td>{{ $subject->title }}</td><td>{{ $subject->units }}</td></tr>@empty<tr><td colspan="3" class="text-muted">No subjects selected.</td></tr>@endforelse</tbody></table></div><div class="small fw-semibold mt-2">{{ $subjects->count() }} subjects · {{ $subjects->sum('units') }} units</div></div></section>
                </div>
                <div class="alert alert-info mt-4 mb-0">Your application will be marked Pending. You will only become officially enrolled after administrator approval.</div>
                <div class="d-flex flex-wrap justify-content-between gap-2 mt-4 pt-3 border-top">
                    <div class="d-flex gap-2"><a href="{{ route('enrollment.create', ['step' => 4]) }}" class="btn btn-outline-secondary">Back</a><button type="button" class="btn btn-success fw-semibold" data-bs-toggle="modal" data-bs-target="#submit-confirmation">Submit Enrollment</button></div>
                    <form action="{{ route('enrollment.cancel') }}" method="POST">@csrf<button type="submit" class="btn btn-link text-danger text-decoration-none">Cancel</button></form>
                </div>
                <div class="modal fade" id="submit-confirmation" tabindex="-1" aria-labelledby="submit-confirmation-label" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered"><div class="modal-content"><div class="modal-header"><h2 class="modal-title fs-5" id="submit-confirmation-label">Confirm Enrollment Submission</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div><div class="modal-body">Are you sure you want to submit your enrollment? Please make sure all information is correct.</div><div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button><form id="submit-application-form" action="{{ route('enrollment.submit') }}" method="POST">@csrf<input type="hidden" name="submission_token" value="{{ session('enrollment_submission_token') }}"><button id="confirm-submit-button" class="btn btn-success" type="submit">Yes, Submit</button></form></div></div></div>
                </div>
            @endif
        </div>
    </div>
</div>

@if($step === 3)
<script>
    const departmentSelect = document.getElementById('department_id');
    const programSelect = document.getElementById('program_id');
    const filterPrograms = () => {
        const selectedDepartment = departmentSelect.value;
        for (const option of programSelect.options) {
            if (!option.value) continue;
            option.hidden = option.dataset.departmentId !== selectedDepartment;
            option.disabled = option.hidden;
            if (option.disabled && option.selected) programSelect.value = '';
        }
    };
    departmentSelect.addEventListener('change', filterPrograms);
    filterPrograms();
</script>
@endif

@if($step === 4)
<script>
    const updateSubjectTotals = () => {
        const selectedSubjects = [...document.querySelectorAll('.subject-choice:checked')];
        document.getElementById('subject-count').textContent = selectedSubjects.length;
        document.getElementById('unit-count').textContent = selectedSubjects.reduce((total, subject) => total + Number(subject.dataset.units), 0);
    };
    document.querySelectorAll('.subject-choice').forEach((subject) => subject.addEventListener('change', updateSubjectTotals));
    updateSubjectTotals();
</script>
@endif

@if($step === 5)
<script>
    document.getElementById('submit-application-form').addEventListener('submit', () => {
        const button = document.getElementById('confirm-submit-button');
        button.disabled = true;
        button.innerHTML = '<span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>Submitting...';
    });
</script>
@endif
@endsection
