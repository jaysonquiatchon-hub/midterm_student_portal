<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureStudentPortalAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $student = $request->user()?->student;

        if ($request->user()?->role === 'student' && (! $student || $student->status !== 'active')) {
            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('student.login')->withErrors([
                'username' => $student
                    ? 'This student account is inactive or dropped. Contact the administrator for help.'
                    : 'This account is not linked to a student profile. Contact the administrator for help.',
            ]);
        }

        return $next($request);
    }
}
