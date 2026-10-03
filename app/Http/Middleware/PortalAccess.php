<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PortalAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! session('portal_access')) {
            return redirect()->route('student.login')->with('error', 'Please enter access key first.');
        }

        return $next($request);
    }
}
