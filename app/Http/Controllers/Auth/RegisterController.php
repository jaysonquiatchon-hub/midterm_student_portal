<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Student;
use App\Models\EnrollmentApplication;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class RegisterController extends Controller
{
    public function showRegistrationForm()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $request->validate([
            'first_name'     => 'required|string|max:255',
            'last_name'      => 'required|string|max:255',
            'student_number' => 'required|string|max:255',
            'email'          => 'required|string|email|max:255|unique:users',
            'password'       => 'required|string|min:8|confirmed',
        ]);

        // 1. Check for an approved student record or application matching student_number and email
        $student = Student::where('student_id', $request->student_number)
            ->orWhere('email', $request->email)
            ->first();

        $application = EnrollmentApplication::where('email', $request->email)
            ->where('status', 'approved')
            ->first();

        // 2. Create User account
        $user = User::create([
            'name'     => $request->first_name . ' ' . $request->last_name,
            'email'    => $request->email,
            'username' => $request->student_number,
            'password' => Hash::make($request->password),
            'role'     => 'student',
        ]);

        // 3. Link the student record to the new user account
        if ($student) {
            $student->update(['user_id' => $user->id]);
        } elseif ($application) {
            // Fallback if student entry exists under application
            $studentRecord = Student::where('email', $application->email)->first();
            if ($studentRecord) {
                $studentRecord->update(['user_id' => $user->id]);
            }
        }

        Auth::login($user);

        return redirect()->route('student.dashboard')
            ->with('success', 'Account created! Your enrollment details and courses have been synced.');
    }
}