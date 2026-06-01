<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        $response->headers->set('X-Frame-Options',        'SAMEORIGIN');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-XSS-Protection',       '1; mode=block');
        $response->headers->set('Referrer-Policy',         'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy',      'camera=(), microphone=(), geolocation=()');

        // HSTS — enforce HTTPS for 1 year, include subdomains
        $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');

        // Content Security Policy
        // GA4: gtag.js loads from googletagmanager.com; analytics beacons go to
        // *.google-analytics.com (sometimes region-prefixed) and stats.g.doubleclick.net;
        // pixel fallback fires from googletagmanager.com/google-analytics.com.
        $csp = implode('; ', [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://code.jquery.com https://www.google.com https://books.google.com https://www.googletagmanager.com https://*.google-analytics.com",
            "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://fonts.googleapis.com",
            "font-src 'self' https://fonts.gstatic.com https://cdnjs.cloudflare.com https://cdn.jsdelivr.net",
            "img-src 'self' data: blob: https://books.google.com https://*.googleusercontent.com https://www.googletagmanager.com https://www.google-analytics.com https://*.google-analytics.com",
            "connect-src 'self' https://www.google.com https://books.google.com https://www.google-analytics.com https://*.google-analytics.com https://*.analytics.google.com https://stats.g.doubleclick.net",
            "frame-src https://books.google.com https://www.google.com",
            "frame-ancestors 'self'",
            "base-uri 'self'",
            "form-action 'self'",
        ]);
        $response->headers->set('Content-Security-Policy', $csp);

        return $response;
    }
}
