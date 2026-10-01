<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureStudentPortalAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        // Check if session flag is set
        if (! $request->session()->get('portal_access')) {
            // Intercept & redirect with flash warning message
            return redirect()
                ->route('portal.login')
                ->with('warning', 'Unauthorized access! Please enter your access credentials first.');
        }

        return $next($request);
    }
}