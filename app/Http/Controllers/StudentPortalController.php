<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class StudentPortalController extends Controller
{
    public function index(Request $request): View
    {
        $student = $this->studentFor($request);
        $student->load(['program', 'courses', 'enrollments.program', 'enrollments.courses']);
        $applications = $request->user()->enrollmentApplications()->latest('submitted_at')->get();
        $enrollmentCourseIds = $student->enrollments
            ->flatMap(fn (Enrollment $enrollment) => $enrollment->courses->modelKeys())
            ->unique();
        $otherCourses = $student->courses
            ->reject(fn (Course $course): bool => $enrollmentCourseIds->contains($course->getKey()))
            ->values();

        return view('student.dashboard', compact('student', 'applications', 'otherCourses'));
    }

    public function editProfile(Request $request): View
    {
        return view('student.profile.edit', [
            'student' => $this->studentFor($request),
        ]);
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $student = $this->studentFor($request);
        $request->merge([
            'email' => mb_strtolower(trim((string) $request->input('email', ''))),
        ]);

        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:60'],
            'middle_name' => ['nullable', 'string', 'max:60'],
            'last_name' => ['required', 'string', 'max:60'],
            'suffix' => ['nullable', 'string', 'max:20'],
            'birth_date' => ['required', 'date', 'before:today'],
            'gender' => ['nullable', Rule::in(['Female', 'Male', 'Non-binary', 'Prefer not to say'])],
            'civil_status' => ['nullable', Rule::in(['Single', 'Married', 'Widowed', 'Separated', 'Divorced'])],
            'nationality' => ['nullable', 'string', 'max:80'],
            'email' => [
                'required',
                'email:rfc',
                'max:255',
                Rule::unique('students', 'email')->ignore($student->id),
                Rule::unique('users', 'email')->ignore($request->user()->id),
            ],
            'contact_number' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:2000'],
        ], [
            'email.email' => 'Enter a valid email address, such as name@example.com.',
            'email.unique' => 'That email address is already being used by another account.',
        ]);

        DB::transaction(function () use ($data, $request, $student): void {
            $student->update($data);
            $request->user()->update([
                'name' => $student->fresh()->full_name,
                'email' => $data['email'],
            ]);
        });

        return redirect()->route('student.profile.edit')
            ->with('success', 'Your personal information has been updated.');
    }

    public function updateProfilePhoto(Request $request): RedirectResponse
    {
        $student = $this->studentFor($request);
        $data = $request->validate([
            'profile_photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ], [
            'profile_photo.required' => 'Choose a profile photo to upload.',
            'profile_photo.image' => 'The profile photo must be a valid image.',
            'profile_photo.mimes' => 'Use a JPG, PNG, or WebP image.',
            'profile_photo.max' => 'The profile photo must be 2 MB or smaller.',
        ]);

        $path = $data['profile_photo']->store('student-photos', 'local');
        if ($path === false) {
            throw ValidationException::withMessages([
                'profile_photo' => 'The profile photo could not be saved. Please try again.',
            ]);
        }

        $previousPath = $student->profile_photo_path;
        $student->update(['profile_photo_path' => $path]);
        if ($previousPath && $previousPath !== $path) {
            if (! Storage::disk('local')->delete($previousPath)) {
                Log::warning('An old student profile photo could not be removed.', [
                    'student_id' => $student->id,
                    'path' => $previousPath,
                ]);
            }
        }

        return redirect()->route('student.profile.edit')
            ->with('success', 'Your profile photo has been updated.');
    }

    public function profilePhoto(Request $request): BinaryFileResponse
    {
        $student = $this->studentFor($request);
        abort_unless(
            $student->profile_photo_path
                && Storage::disk('local')->exists($student->profile_photo_path),
            404,
        );

        return response()->file(Storage::disk('local')->path($student->profile_photo_path));
    }

    public function showEnrollment(Request $request, Enrollment $enrollment): View
    {
        $student = $this->studentFor($request);
        $enrollment = $student->enrollments()
            ->with(['program', 'courses'])
            ->findOrFail($enrollment->id);

        return view('student.enrollments.show', compact('student', 'enrollment'));
    }

    private function studentFor(Request $request): Student
    {
        $student = $request->user()->student;
        abort_unless($student, 403);

        return $student;
    }
}
