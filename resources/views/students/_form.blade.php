<div class="row g-3">
    <div class="col-md-4">
        <label for="student_number" class="form-label">Student Number</label>
        <input id="student_number" name="student_number" class="form-control @error('student_number') is-invalid @enderror" value="{{ old('student_number', $student->student_number ?? '') }}" required>
    </div>
    <div class="col-md-4">
        <label for="first_name" class="form-label">First Name</label>
        <input id="first_name" name="first_name" class="form-control @error('first_name') is-invalid @enderror" value="{{ old('first_name', $student->first_name ?? '') }}" required>
    </div>
    <div class="col-md-4">
        <label for="last_name" class="form-label">Last Name</label>
        <input id="last_name" name="last_name" class="form-control @error('last_name') is-invalid @enderror" value="{{ old('last_name', $student->last_name ?? '') }}" required>
    </div>
    <div class="col-md-6">
        <label for="email" class="form-label">Email</label>
        <input id="email" type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $student->email ?? '') }}" required>
    </div>
    <div class="col-md-6">
        <label for="middle_name" class="form-label">Middle Name</label>
        <input id="middle_name" name="middle_name" class="form-control @error('middle_name') is-invalid @enderror" value="{{ old('middle_name', $student->middle_name ?? '') }}">
    </div>
    <div class="col-md-6">
        <label for="suffix" class="form-label">Suffix</label>
        <input id="suffix" name="suffix" class="form-control @error('suffix') is-invalid @enderror" value="{{ old('suffix', $student->suffix ?? '') }}">
    </div>
    <div class="col-md-6">
        <label for="contact_number" class="form-label">Contact Number</label>
        <input id="contact_number" name="contact_number" class="form-control @error('contact_number') is-invalid @enderror" value="{{ old('contact_number', $student->contact_number ?? '') }}">
    </div>
    <div class="col-12">
        <label for="address" class="form-label">Address</label>
        <textarea id="address" name="address" class="form-control @error('address') is-invalid @enderror" rows="2">{{ old('address', $student->address ?? '') }}</textarea>
    </div>
    <div class="col-md-3">
        <label for="birth_date" class="form-label">Birth Date</label>
        <input id="birth_date" type="date" name="birth_date" class="form-control @error('birth_date') is-invalid @enderror" value="{{ old('birth_date', isset($student->birth_date) ? (is_string($student->birth_date) ? $student->birth_date : $student->birth_date->format('Y-m-d')) : '') }}" required>
    </div>
    <div class="col-md-3">
        <label for="year_level" class="form-label">Year Level</label>
        <select id="year_level" name="year_level" class="form-select @error('year_level') is-invalid @enderror">
            @foreach ([1, 2, 3, 4] as $y)
                <option value="{{ $y }}" @selected(old('year_level', $student->year_level ?? 1) == $y)>{{ $y }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-6">
        <label for="program_id" class="form-label">Program Enrolled</label>
        <select id="program_id" name="program_id" class="form-select @error('program_id') is-invalid @enderror" required>
            <option value="" disabled @selected(!old('program_id', $student->program_id ?? ''))>Select Program...</option>
            @foreach ($programs as $program)
                <option value="{{ $program->id }}" @selected(old('program_id', $student->program_id ?? '') == $program->id)>
                    {{ $program->name }}
                </option>
            @endforeach
        </select>
        @error('program_id')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
</div>
<div class="row g-3 mt-1">
    <div class="col-md-6">
        <label for="status" class="form-label">Student Status</label>
        <select id="status" name="status" class="form-select @error('status') is-invalid @enderror" required>
            @foreach (['active' => 'Active', 'inactive' => 'Inactive', 'dropped' => 'Dropped'] as $value => $label)
                <option value="{{ $value }}" @selected(old('status', $student->status ?? 'active') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
</div>
