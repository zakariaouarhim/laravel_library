<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class IsSuperAdmin
{
    public function handle(Request $request, Closure $next)
    {
        if (auth()->check() && auth()->user()->role === 'super_admin') {
            return $next($request);
        }

        return redirect('/')->with('error', 'غير مصرح - هذه الصفحة للمشرف العام فقط');
    }
}
