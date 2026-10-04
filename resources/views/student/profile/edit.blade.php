@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1">Edit Profile</h1>
            <p class="text-muted mb-0">Update the personal information shown in your student portal.</p>
        </div>
        <a href="{{ route('student.dashboard') }}" class="btn btn-outline-secondary">Back to portal</a>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-body p-4">
                    <h2 class="h5 fw-bold mb-3">Personal information</h2>
                    <form method="POST" action="{{ route('student.profile.update') }}">
                        @csrf
                        @method('PUT')
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="first_name" class="form-label">First name</label>
                                <input id="first_name" name="first_name" class="form-control @error('first_name') is-invalid @enderror" value="{{ old('first_name', $student->first_name) }}" autocomplete="given-name" required>
                                @error('first_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label for="middle_name" class="form-label">Middle name</label>
                                <input id="middle_name" name="middle_name" class="form-control @error('middle_name') is-invalid @enderror" value="{{ old('middle_name', $student->middle_name) }}" autocomplete="additional-name">
                                @error('middle_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label for="last_name" class="form-label">Last name</label>
                                <input id="last_name" name="last_name" class="form-control @error('last_name') is-invalid @enderror" value="{{ old('last_name', $student->last_name) }}" autocomplete="family-name" required>
                                @error('last_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label for="suffix" class="form-label">Suffix</label>
                                <input id="suffix" name="suffix" class="form-control @error('suffix') is-invalid @enderror" value="{{ old('suffix', $student->suffix) }}" placeholder="e.g. Jr.">
                                @error('suffix')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label for="birth_date" class="form-label">Date of birth</label>
                                <input id="birth_date" type="date" name="birth_date" class="form-control @error('birth_date') is-invalid @enderror" value="{{ old('birth_date', $student->birth_date?->toDateString()) }}" max="{{ now()->subDay()->toDateString() }}" required>
                                @error('birth_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label for="gender" class="form-label">Gender</label>
                                <select id="gender" name="gender" class="form-select @error('gender') is-invalid @enderror">
                                    <option value="">Prefer not to specify</option>
                                    @foreach(['Female', 'Male', 'Non-binary', 'Prefer not to say'] as $gender)
                                        <option value="{{ $gender }}" @selected(old('gender', $student->gender) === $gender)>{{ $gender }}</option>
                                    @endforeach
                                </select>
                                @error('gender')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label for="civil_status" class="form-label">Civil status</label>
                                <select id="civil_status" name="civil_status" class="form-select @error('civil_status') is-invalid @enderror">
                                    <option value="">Select status</option>
                                    @foreach(['Single', 'Married', 'Widowed', 'Separated', 'Divorced'] as $status)
                                        <option value="{{ $status }}" @selected(old('civil_status', $student->civil_status) === $status)>{{ $status }}</option>
                                    @endforeach
                                </select>
                                @error('civil_status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label for="nationality" class="form-label">Nationality</label>
                                <input id="nationality" name="nationality" class="form-control @error('nationality') is-invalid @enderror" value="{{ old('nationality', $student->nationality) }}" placeholder="Your nationality">
                                @error('nationality')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label for="email" class="form-label">Email address</label>
                                <input id="email" type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $student->email) }}" autocomplete="email" placeholder="name@example.com" required>
                                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label for="contact_number" class="form-label">Contact number</label>
                                <input id="contact_number" name="contact_number" class="form-control @error('contact_number') is-invalid @enderror" value="{{ old('contact_number', $student->contact_number) }}" autocomplete="tel" placeholder="Your phone number">
                                @error('contact_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-12">
                                <label for="address" class="form-label">Address</label>
                                <textarea id="address" name="address" rows="3" class="form-control @error('address') is-invalid @enderror" autocomplete="street-address" placeholder="Your current address">{{ old('address', $student->address) }}</textarea>
                                @error('address')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary mt-4">Save changes</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-body p-4">
                    <h2 class="h5 fw-bold mb-3">Profile photo</h2>
                    @if($student->profile_photo_path)
                        <img src="{{ route('student.profile.photo') }}" alt="Current profile photo" class="rounded-circle object-fit-cover mb-3" width="120" height="120">
                    @endif
                    <form method="POST" action="{{ route('student.profile.photo.update') }}" enctype="multipart/form-data">
                        @csrf
                        <label for="profile_photo" class="form-label">Choose an image</label>
                        <input id="profile_photo" name="profile_photo" type="file" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" class="form-control @error('profile_photo') is-invalid @enderror" required>
                        <div class="form-text">JPG, PNG, or WebP. Maximum file size: 2 MB.</div>
                        @error('profile_photo')<div class="d-block invalid-feedback">{{ $message }}</div>@enderror
                        <button type="submit" class="btn btn-outline-primary mt-3">Upload photo</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
