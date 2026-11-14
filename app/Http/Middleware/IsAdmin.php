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

        // AJAX callers need a 403, not a 302 redirect their fetch() can't follow usefully.
        if ($request->expectsJson()) {
            return response()->json(['success' => false, 'message' => 'غير مصرح'], 403);
        }

        return redirect('/')->with('error', 'غير مصرح');
    }
}