@extends('layouts.app')

@section('content')
<div class="card border-0 shadow-sm rounded-3 p-4">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
        <div><h1 class="h3 fw-bold mb-1">Edit Pending Application</h1><p class="text-muted mb-0">{{ $application->application_number }} · {{ $application->full_name }}</p></div>
        <a href="{{ route('admin.enrollment-applications.show', $application) }}" class="btn btn-outline-secondary">Back to Review</a>
    </div>
    @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <form action="{{ route('admin.enrollment-applications.update', $application) }}" method="POST">
        @csrf @method('PATCH')
        <h2 class="h5 fw-bold border-bottom pb-2">Personal Information</h2>
        <div class="row g-3 mb-4">
            @foreach(['first_name' => 'First Name', 'middle_name' => 'Middle Name', 'last_name' => 'Last Name', 'suffix' => 'Suffix'] as $field => $label)
                <div class="col-md-3"><label for="{{ $field }}" class="form-label">{{ $label }}{{ in_array($field, ['first_name', 'last_name'], true) ? ' *' : '' }}</label><input id="{{ $field }}" name="{{ $field }}" value="{{ old($field, $application->{$field}) }}" class="form-control @error($field) is-invalid @enderror" {{ in_array($field, ['first_name', 'last_name'], true) ? 'required' : '' }}>@error($field)<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            @endforeach
            <div class="col-md-3"><label for="birth_date" class="form-label">Date of Birth *</label><input id="birth_date" type="date" name="birth_date" value="{{ old('birth_date', $application->birth_date->toDateString()) }}" class="form-control" required max="{{ now()->subDay()->toDateString() }}"></div>
            <div class="col-md-3"><label for="gender" class="form-label">Gender *</label><select id="gender" name="gender" class="form-select" required>@foreach(['Female', 'Male', 'Non-binary', 'Prefer not to say'] as $gender)<option value="{{ $gender }}" @selected(old('gender', $application->gender) === $gender)>{{ $gender }}</option>@endforeach</select></div>
            <div class="col-md-3"><label for="civil_status" class="form-label">Civil Status</label><select id="civil_status" name="civil_status" class="form-select"><option value="">Not provided</option>@foreach(['Single', 'Married', 'Widowed', 'Separated', 'Divorced'] as $status)<option value="{{ $status }}" @selected(old('civil_status', $application->civil_status) === $status)>{{ $status }}</option>@endforeach</select></div>
            <div class="col-md-3"><label for="nationality" class="form-label">Nationality</label><input id="nationality" name="nationality" value="{{ old('nationality', $application->nationality) }}" class="form-control"></div>
        </div>

        <h2 class="h5 fw-bold border-bottom pb-2">Contact Information</h2>
        <div class="row g-3 mb-4">
            <div class="col-md-6"><label for="email" class="form-label">Email *</label><input id="email" type="email" name="email" value="{{ old('email', $application->email) }}" class="form-control" required></div>
            <div class="col-md-6"><label for="contact_number" class="form-label">Contact Number *</label><input id="contact_number" name="contact_number" value="{{ old('contact_number', $application->contact_number) }}" class="form-control" required></div>
            @foreach(['house_block_lot' => 'House / Block / Lot', 'street' => 'Street', 'barangay' => 'Barangay', 'city' => 'City / Municipality', 'province' => 'Province'] as $field => $label)
                <div class="col-md-4"><label for="{{ $field }}" class="form-label">{{ $label }}{{ in_array($field, ['barangay', 'city', 'province'], true) ? ' *' : '' }}</label><input id="{{ $field }}" name="{{ $field }}" value="{{ old($field, $application->{$field}) }}" class="form-control" {{ in_array($field, ['barangay', 'city', 'province'], true) ? 'required' : '' }}></div>
            @endforeach
        </div>

        <h2 class="h5 fw-bold border-bottom pb-2">Academic Information</h2>
        <div class="row g-3 mb-4">
            <div class="col-md-6"><label for="department_id" class="form-label">Department *</label><select id="department_id" name="department_id" class="form-select" required>@foreach($departments as $department)<option value="{{ $department->id }}" @selected((string) old('department_id', $application->department_id) === (string) $department->id)>{{ $department->name }}</option>@endforeach</select></div>
            <div class="col-md-6"><label for="program_id" class="form-label">Program *</label><select id="program_id" name="program_id" class="form-select" required>@foreach($programs as $program)<option value="{{ $program->id }}" data-department="{{ $program->department_id }}" @selected((string) old('program_id', $application->program_id) === (string) $program->id)>{{ $program->name }}</option>@endforeach</select></div>
            <div class="col-md-4"><label for="student_type" class="form-label">Student Type *</label><select id="student_type" name="student_type" class="form-select" required>@foreach(['New Student', 'Transferee', 'Returning Student'] as $type)<option value="{{ $type }}" @selected(old('student_type', $application->student_type) === $type)>{{ $type }}</option>@endforeach</select></div>
            <div class="col-md-4"><label for="year_level" class="form-label">Year Level *</label><select id="year_level" name="year_level" class="form-select" required>@foreach(range(1, 4) as $year)<option value="{{ $year }}" @selected((int) old('year_level', $application->year_level) === $year)>Year {{ $year }}</option>@endforeach</select></div>
            <div class="col-md-4"><label for="school_year" class="form-label">School Year *</label><input id="school_year" name="school_year" value="{{ old('school_year', $application->school_year) }}" class="form-control" required pattern="[0-9]{4}-[0-9]{4}"></div>
            <div class="col-md-4"><label for="semester" class="form-label">Semester *</label><select id="semester" name="semester" class="form-select" required>@foreach(['1st' => '1st Semester', '2nd' => '2nd Semester', 'Summer' => 'Summer'] as $value => $label)<option value="{{ $value }}" @selected(old('semester', $application->semester) === $value)>{{ $label }}</option>@endforeach</select></div>
        </div>

        <h2 class="h5 fw-bold border-bottom pb-2">Selected Subjects</h2>
        <div class="row g-2 mb-4">
            @foreach($subjects as $subject)
                <div class="col-md-6"><label class="border rounded p-2 d-flex align-items-center gap-2"><input type="checkbox" class="form-check-input m-0" name="selected_subject_ids[]" value="{{ $subject->id }}" @checked(in_array($subject->id, old('selected_subject_ids', $application->subjects->pluck('id')->all())))><span class="font-monospace">{{ $subject->code }}</span><span>{{ $subject->title }}</span><span class="ms-auto text-muted">{{ $subject->units }} units</span></label></div>
            @endforeach
        </div>
        @error('selected_subject_ids')<div class="text-danger small mb-3">{{ $message }}</div>@enderror
        @error('selected_subject_ids.*')<div class="text-danger small mb-3">{{ $message }}</div>@enderror
        <div class="d-flex gap-2"><button class="btn btn-primary">Save Pending Application</button><a href="{{ route('admin.enrollment-applications.show', $application) }}" class="btn btn-secondary">Cancel</a></div>
    </form>
</div>
@endsection
