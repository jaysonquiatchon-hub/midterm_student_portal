<?php

namespace App\Http\Controllers;

use App\Models\EnrollmentApplication;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PortalAuthController extends Controller
{
    public function showStudentLogin(): View
    {
        return view('portal.login', [
            'portalTitle' => 'Student Login',
            'portalDescription' => 'Sign in to view your student information, courses, and grades.',
            'loginRoute' => 'student.login.submit',
            'isStudentLogin' => true,
        ]);
    }

    public function showAdminLogin(): View
    {
        return view('portal.login', [
            'portalTitle' => 'Administrator Login',
            'portalDescription' => 'Sign in to manage the student portal and enrollment applications.',
            'loginRoute' => 'admin.login.submit',
            'isStudentLogin' => false,
        ]);
    }

    public function create(): View
    {
        return view('portal.register');
    }

    public function register(Request $request): RedirectResponse
    {
        $request->merge([
            'student_number' => mb_strtoupper(trim((string) $request->input('student_number', ''))),
            'first_name' => trim(strip_tags((string) $request->input('first_name', ''))),
            'last_name' => trim(strip_tags((string) $request->input('last_name', ''))),
            'email' => mb_strtolower(trim((string) $request->input('email', ''))),
        ]);

        $data = $request->validate([
            'student_number' => ['required', 'string', 'max:20', 'regex:/^[A-Z0-9-]+$/'],
            'first_name' => ['required', 'string', 'max:60'],
            'last_name' => ['required', 'string', 'max:60'],
            'email' => ['required', 'email:rfc', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'student_number.required' => 'Enter the student number shown in your enrollment approval.',
            'student_number.regex' => 'Enter a valid student number using letters, numbers, or hyphens.',
            'email.email' => 'Enter a valid email address, such as name@example.com.',
            'password.confirmed' => 'The password confirmation does not match.',
        ]);

        DB::transaction(function () use ($data): void {
            $student = Student::query()
                ->where(function ($query) use ($data): void {
                    $query->whereRaw('UPPER(student_number) = ?', [$data['student_number']])
                        ->orWhereRaw('UPPER(student_id) = ?', [$data['student_number']]);
                })
                ->lockForUpdate()
                ->first();

            if (! $student || mb_strtolower($student->email) !== $data['email']) {
                throw ValidationException::withMessages([
                    'student_number' => 'Student account creation is available only after an enrollment application is approved. Check your application status using the application number and email address.',
                ]);
            }

            $application = EnrollmentApplication::query()
                ->where('student_id', $student->id)
                ->where('status', 'approved')
                ->whereRaw('LOWER(email) = ?', [$data['email']])
                ->latest('approved_at')
                ->first();

            if (! $application || $student->status !== 'active') {
                throw ValidationException::withMessages([
                    'student_number' => 'Student account creation is available only after an enrollment application is approved. Check your application status using the application number and email address.',
                ]);
            }

            if (
                mb_strtolower($student->first_name) !== mb_strtolower($data['first_name'])
                || mb_strtolower($student->last_name) !== mb_strtolower($data['last_name'])
                || mb_strtolower($application->first_name) !== mb_strtolower($data['first_name'])
                || mb_strtolower($application->last_name) !== mb_strtolower($data['last_name'])
            ) {
                throw ValidationException::withMessages([
                    'first_name' => 'Your name must match the approved enrollment record.',
                ]);
            }

            if ($student->user_id || User::query()->whereRaw('LOWER(email) = ?', [$data['email']])->exists()) {
                throw ValidationException::withMessages([
                    'email' => 'An account already exists for this email address. Please sign in instead.',
                ]);
            }

            $user = User::create([
                'name' => $student->full_name,
                'email' => $data['email'],
                'password' => $data['password'],
                'role' => 'student',
            ]);

            $student->update(['user_id' => $user->id]);
            EnrollmentApplication::query()
                ->where('student_id', $student->id)
                ->where('status', 'approved')
                ->update(['applicant_user_id' => $user->id]);
        });

        return redirect()->route('student.login')
            ->with('success', 'Your student account has been created. Sign in with your email and password.');
    }

    public function studentLogin(Request $request): RedirectResponse
    {
        return $this->loginForRole($request, 'student');
    }

    public function adminLogin(Request $request): RedirectResponse
    {
        return $this->loginForRole($request, 'admin');
    }

    public function logout(Request $request): RedirectResponse
    {
        $wasAdmin = $request->user()?->role === 'admin';
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route($wasAdmin ? 'admin.login' : 'student.login')
            ->with('success', 'You have been signed out.');
    }

    private function loginForRole(Request $request, string $expectedRole): RedirectResponse
    {
        $identifierPrompt = $expectedRole === 'admin'
            ? 'email address'
            : 'email address or student ID';
        $credentials = $request->validate([
            'username' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
        ], [
            'username.required' => "Enter your {$identifierPrompt}.",
            'password.required' => 'Enter your password.',
        ]);

        $username = mb_strtolower(trim($credentials['username']));
        $user = User::query()->whereRaw('LOWER(email) = ?', [$username])->first();

        if (! $user && $expectedRole === 'student') {
            $student = Student::query()
                ->whereRaw('LOWER(student_id) = ?', [$username])
                ->orWhereRaw('LOWER(student_number) = ?', [$username])
                ->first();
            $user = $student?->user;
        }

        if (! $user || $user->role !== $expectedRole || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'username' => $expectedRole === 'admin'
                    ? 'The email address or password is incorrect.'
                    : 'The email address/student ID or password is incorrect.',
            ]);
        }

        if ($expectedRole === 'student') {
            $student = $user->student;
            if (! $student || $student->status !== 'active') {
                throw ValidationException::withMessages([
                    'username' => $student
                        ? 'This student account is inactive or dropped and cannot sign in. Contact the administrator for help.'
                        : 'This account is not linked to a student profile. Contact the administrator for help.',
                ]);
            }
        }

        Auth::login($user);
        $request->session()->regenerate();
        $request->session()->put('portal_access', true);

        return redirect()->route($expectedRole === 'admin' ? 'dashboard' : 'student.dashboard');
    }
}
