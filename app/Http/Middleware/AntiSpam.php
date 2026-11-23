<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;

/**
 * Drops automated submissions on public forms.
 *
 * Built for the signup flood on POST /adduser: ~4,500 attempts across 314 IPs,
 * mostly Tor exits, pacing just under the route's throttle:3,1. No IP-based
 * limit can answer that, so every check here is source-agnostic.
 *
 * Three layers, cheapest first — a bot has to beat all of them:
 *   1. a honeypot field a human never sees,
 *   2. a minimum time between rendering the form and submitting it,
 *   3. Cloudflare Turnstile, if (and only if) keys are configured.
 *
 * Nothing is logged per request. Filling storage/logs is half the problem this
 * middleware exists to solve, and production runs LOG_LEVEL=error, so an info
 * line would be dropped anyway. Effectiveness is observable through the
 * spam_blocked:<date> cache counter instead.
 */
class AntiSpam
{
    /**
     * The decoy input. Named for what a spam bot wants to fill; it is rendered
     * inside a display:none wrapper, which also keeps browser autofill and
     * screen readers away from it, so a real user can never populate it.
     */
    public const HONEYPOT = 'website';

    /** Hidden field carrying the encrypted render time. */
    public const TIMESTAMP = '_ts';

    /** Every logged attempt posted instantly; no human fills a form this fast. */
    private const MIN_SECONDS = 3;

    /**
     * 2h, matching the XSRF-TOKEN cookie lifetime: CSRF always rejects a
     * genuinely stale form first, so this bound can never be the reason a real
     * visitor is turned away.
     */
    private const MAX_SECONDS = 7200;

    /**
     * @param  string|null  $mode  Pass "turnstile" to also require a Turnstile
     *        token. Opt-in per route on purpose: a form that does not render
     *        the widget must not start failing the moment keys are configured.
     */
    public function handle(Request $request, Closure $next, ?string $mode = null)
    {
        if ($this->looksAutomated($request, $mode === 'turnstile')) {
            Cache::increment('spam_blocked:' . now()->toDateString());

            // One message for every check, so a bot cannot learn which caught
            // it. Passwords must not be flashed back into the session.
            return back()
                ->withInput($request->except(['password', 'password_confirmation', self::HONEYPOT]))
                ->with('error', 'تعذّر إرسال النموذج. يرجى إعادة تحميل الصفحة والمحاولة مرة أخرى.');
        }

        return $next($request);
    }

    private function looksAutomated(Request $request, bool $withTurnstile): bool
    {
        if (filled($request->input(self::HONEYPOT))) {
            return true;
        }

        $age = $this->formAge($request->input(self::TIMESTAMP));
        if ($age === null || $age < self::MIN_SECONDS || $age > self::MAX_SECONDS) {
            return true;
        }

        return $withTurnstile && !$this->passesTurnstile($request);
    }

    /** Seconds since the form was rendered, or null if the token is unusable. */
    private function formAge($token): ?int
    {
        if (!is_string($token) || $token === '') {
            return null;
        }

        try {
            // Encrypted, so the value can't be back-dated without APP_KEY.
            $renderedAt = (int) Crypt::decryptString($token);
        } catch (\Throwable $e) {
            return null;
        }

        return $renderedAt > 0 ? time() - $renderedAt : null;
    }

    /**
     * True when Turnstile is satisfied — or not configured, which is the state
     * this ships in: no keys means this layer simply does not exist yet, and
     * adding them to .env switches it on with no redeploy.
     */
    private function passesTurnstile(Request $request): bool
    {
        $secret = config('services.turnstile.secret_key');
        if (blank($secret)) {
            return true;
        }

        $token = $request->input('cf-turnstile-response');
        if (blank($token)) {
            return false;
        }

        try {
            // Only the token and the visitor's IP go to Cloudflare — never the
            // name, email or anything else the visitor typed.
            $response = Http::timeout(5)->asForm()->post(
                'https://challenges.cloudflare.com/turnstile/v0/siteverify',
                ['secret' => $secret, 'response' => $token, 'remoteip' => $request->ip()]
            );
        } catch (\Throwable $e) {
            // Fail open on purpose. A Cloudflare incident must not be able to
            // block every signup on the site; the honeypot, the timing check
            // and the name rules still apply.
            return true;
        }

        // A 5xx is an outage too, not a verdict, so treat it like the timeout
        // above rather than as a failed challenge.
        if ($response->serverError()) {
            return true;
        }

        return $response->successful() && $response->json('success') === true;
    }
}
