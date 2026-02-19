<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class IsAdmin
{
    public function handle(Request $request, Closure $next)
    {
        if (auth()->check() && in_array(auth()->user()->role, ['admin', 'super_admin'])) {
            return $next($request);
        }

        return redirect('/')->with('error', 'غير مصرح');
    }
}