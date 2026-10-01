<div class="mb-3">
    <label for="code" class="form-label fw-semibold">Course Code</label>
    <input id="code" name="code" value="{{ old('code', $course?->code) }}" class="form-control @error('code') is-invalid @enderror" required maxlength="20">
    @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
<div class="mb-3">
    <label for="title" class="form-label fw-semibold">Course Title</label>
    <input id="title" name="title" value="{{ old('title', $course?->title) }}" class="form-control @error('title') is-invalid @enderror" required maxlength="255">
    @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
<div class="row g-3">
    <div class="col-md-4">
        <label for="units" class="form-label fw-semibold">Units</label>
        <input id="units" type="number" name="units" value="{{ old('units', $course?->units) }}" class="form-control @error('units') is-invalid @enderror" required min="1" max="10">
        @error('units')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label for="year_level" class="form-label fw-semibold">Year Level</label>
        <select id="year_level" name="year_level" class="form-select @error('year_level') is-invalid @enderror" required>
            @foreach(range(1, 4) as $year)
                <option value="{{ $year }}" @selected(old('year_level', $course?->year_level) == $year)>Year {{ $year }}</option>
            @endforeach
        </select>
        @error('year_level')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label for="semester" class="form-label fw-semibold">Semester</label>
        <select id="semester" name="semester" class="form-select @error('semester') is-invalid @enderror" required>
            @foreach(['1st' => '1st Semester', '2nd' => '2nd Semester', 'Summer' => 'Summer'] as $value => $label)
                <option value="{{ $value }}" @selected(old('semester', $course?->semester ?? '1st') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        @error('semester')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label for="program_id" class="form-label fw-semibold">Program</label>
        <select id="program_id" name="program_id" class="form-select @error('program_id') is-invalid @enderror" required>
            <option value="">Select a program</option>
            @foreach($programs as $program)
                <option value="{{ $program->id }}" @selected(old('program_id', $course?->program_id) == $program->id)>{{ $program->code }} - {{ $program->name }}</option>
            @endforeach
        </select>
        @error('program_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
</div>
