<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

/**
 * Hands the caller the CSRF token of their OWN session.
 *
 * Why this exists: every page bakes csrf_token() into a <meta> tag when it is
 * rendered, and that value can go stale while the page stays open. When
 * SESSION_LIFETIME passes, the next request starts a brand-new session with a
 * brand-new token, and the remember-me cookie re-authenticates the user
 * silently - so GETs keep returning 200 while every POST is rejected with 419
 * "CSRF token mismatch". The admin review screens are where this bites, because
 * they are left open for hours and act only through AJAX.
 *
 * Returning the token is not a disclosure: it is the same value already present
 * in the page's HTML, it belongs to the requester's own session, and the
 * same-origin policy stops another site from reading this response. It is
 * worthless without the session cookie that goes with it.
 *
 * A controller, not a route closure - routes/web.php must stay closure-free or
 * `php artisan route:cache` fails (see CLAUDE.md).
 */
class CsrfTokenController extends Controller
{
    public function show(): JsonResponse
    {
        return response()->json(['token' => csrf_token()]);
    }
}
