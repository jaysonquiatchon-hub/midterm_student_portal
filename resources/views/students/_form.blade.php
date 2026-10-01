@if ($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach ($errors->all() as $error) 
                <li>{{ $error }}</li> 
            @endforeach
        </ul>
    </div>
@endif

<div class="row g-3">
    <div class="col-md-4">
        <label class="form-label">Student Number</label>
        <input name="student_number" class="form-control @error('student_number') is-invalid @enderror" value="{{ old('student_number', $student->student_number ?? '') }}">
    </div>
    <div class="col-md-4">
        <label class="form-label">First Name</label>
        <input name="first_name" class="form-control @error('first_name') is-invalid @enderror" value="{{ old('first_name', $student->first_name ?? '') }}">
    </div>
    <div class="col-md-4">
        <label class="form-label">Last Name</label>
        <input name="last_name" class="form-control @error('last_name') is-invalid @enderror" value="{{ old('last_name', $student->last_name ?? '') }}">
    </div>
    <div class="col-md-6">
        <label class="form-label">Email</label>
        <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $student->email ?? '') }}">
    </div>
    <div class="col-md-3">
        <label class="form-label">Birth Date</label>
        <input type="date" name="birth_date" class="form-control @error('birth_date') is-invalid @enderror" value="{{ old('birth_date', isset($student->birth_date) ? (is_string($student->birth_date) ? $student->birth_date : $student->birth_date->format('Y-m-d')) : '') }}">
    </div>
    <div class="col-md-3">
        <label class="form-label">Year Level</label>
        <select name="year_level" class="form-select @error('year_level') is-invalid @enderror">
            @foreach ([1, 2, 3, 4] as $y)
                <option value="{{ $y }}" @selected(old('year_level', $student->year_level ?? 1) == $y)>{{ $y }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-6">
    <label class="form-label">Program Enrolled</label>
    <select name="program_id" class="form-select @error('program_id') is-invalid @enderror">
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