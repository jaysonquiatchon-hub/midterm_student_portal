<?php

namespace App\Http\Controllers;

use App\Models\Program;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PortalAuthController extends Controller
{
    public function showLogin(): View
    {
        return view('portal.login');
    }

    public function create(): View
    {
        return view('portal.register', [
            'programs' => Program::query()->orderBy('name')->get(),
        ]);
    }

    public function register(Request $request): RedirectResponse
    {
        $request->merge([
            'student_number' => mb_strtoupper(trim((string) $request->input('student_number', ''))),
            'first_name' => trim((string) $request->input('first_name', '')),
            'last_name' => trim((string) $request->input('last_name', '')),
            'email' => mb_strtolower(trim((string) $request->input('email', ''))),
        ]);

        $data = $request->validate([
            'student_number' => ['required', 'string', 'max:20'],
            'first_name' => ['required', 'string', 'max:60'],
            'last_name' => ['required', 'string', 'max:60'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'birth_date' => ['required', 'date', 'before:today'],
            'year_level' => ['required', 'integer', 'between:1,4'],
            'program_id' => ['required', 'exists:programs,id'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        DB::transaction(function () use ($data): void {
            $student = Student::query()
                ->whereRaw('UPPER(student_number) = ?', [$data['student_number']])
                ->lockForUpdate()
                ->first();

            if ($student && (
                $student->user_id !== null
                || mb_strtolower($student->email) !== $data['email']
                || $student->birth_date->toDateString() !== $data['birth_date']
                || mb_strtolower($student->first_name) !== mb_strtolower($data['first_name'])
                || mb_strtolower($student->last_name) !== mb_strtolower($data['last_name'])
                || (int) $student->program_id !== (int) $data['program_id']
                || $student->year_level !== (int) $data['year_level']
            )) {
                throw ValidationException::withMessages([
                    'student_number' => 'The submitted information must match the unlinked student record.',
                ]);
            }

            if (User::query()->whereRaw('LOWER(email) = ?', [$data['email']])->exists()) {
                throw ValidationException::withMessages([
                    'email' => 'This email is already associated with an account.',
                ]);
            }

            if (! $student && Student::query()->whereRaw('LOWER(email) = ?', [$data['email']])->exists()) {
                throw ValidationException::withMessages([
                    'email' => 'This email belongs to a different student record.',
                ]);
            }

            $user = User::create([
                'name' => $student?->full_name ?? trim($data['first_name'].' '.$data['last_name']),
                'email' => $data['email'],
                'password' => $data['password'],
                'role' => 'student',
            ]);

            if ($student) {
                $student->update(['user_id' => $user->id]);

                return;
            }

            Student::create([
                'user_id' => $user->id,
                'student_number' => $data['student_number'],
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'email' => $data['email'],
                'birth_date' => $data['birth_date'],
                'year_level' => $data['year_level'],
                'program_id' => $data['program_id'],
            ]);
        });

        return redirect()->route('portal.login')->with('success', 'Your student account has been created. Please log in.');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'username' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
        ]);

        $email = mb_strtolower(trim($credentials['username'])) === 'admin'
            ? config('student_portal.admin_email')
            : mb_strtolower(trim($credentials['username']));

        if (! Auth::attempt(['email' => $email, 'password' => $credentials['password']])) {
            throw ValidationException::withMessages([
                'username' => 'The supplied credentials are incorrect.',
            ]);
        }

        $user = $request->user();

        if ($user->role === 'admin') {
            return redirect()->intended(route('students.index'));
        }

        if ($user->role === 'student' && $user->student()->exists()) {
            if ($user->student->status !== 'active') {
                Auth::logout();

                return redirect()->route('portal.login')->withErrors([
                    'username' => 'This student account is archived and cannot sign in.',
                ]);
            }

            return redirect()->intended(route('student.dashboard'));
        }

        Auth::logout();

        return redirect()->route('portal.login')->withErrors([
            'username' => 'This account is not linked to a student profile.',
        ]);
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('portal.login')->with('warning', 'You have been logged out.');
    }
}
