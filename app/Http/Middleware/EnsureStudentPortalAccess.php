<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureStudentPortalAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->session()->get('portal_access') && ! $request->user()) {
            return redirect()
                ->route('student.login')
                ->with('warning', 'Unauthorized access! Please enter your access credentials first.');
        }

        return $next($request);
    }
}
