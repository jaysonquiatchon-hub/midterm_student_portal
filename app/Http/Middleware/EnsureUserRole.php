<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureUserRole
{
    public function handle(Request $request, Closure $next, string $role)
    {
        if (! Auth::check()) {
            return redirect()->route($role.'.login');
        }

        if (Auth::user()->role !== $role) {
            abort(403);
        }

        return $next($request);
    }
}
