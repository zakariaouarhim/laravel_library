/**
 * CSRF-resilient POST helper.
 *
 * The problem it solves: a page bakes csrf_token() into its <meta> tag when it
 * is rendered, and the admin screens stay open for hours. Once SESSION_LIFETIME
 * passes, the next request starts a fresh session carrying a fresh token, and
 * the remember-me cookie re-authenticates silently - so GETs keep returning 200
 * while every POST is rejected with 419 and Laravel's raw
 * "CSRF token mismatch." message surfaces in a toast.
 *
 * Two fixes here:
 *   1. the token is read at call time, never captured once at page load;
 *   2. a 419 triggers a token refresh and ONE retry.
 *
 * The retry cannot double-apply anything: VerifyCsrfToken rejects the request
 * before it reaches the controller, so the first attempt had no effect.
 */
(function () {
    var ENDPOINT = '/csrf-token';

    function metaTag() { return document.querySelector('meta[name="csrf-token"]'); }

    function currentToken() {
        var m = metaTag();
        return m ? m.content : '';
    }

    /** Fresh token for this session, or null if even that failed (truly logged out). */
    async function refreshToken() {
        try {
            var r = await fetch(ENDPOINT, {
                headers: { 'Accept': 'application/json' },
                credentials: 'same-origin',
            });
            if (!r.ok) { return null; }
            var d = await r.json().catch(function () { return {}; });
            if (!d.token) { return null; }

            var m = metaTag();
            if (m) { m.content = d.token; }
            // Keep hidden @csrf inputs in step, so a normal form submit on the
            // same page survives the rollover too.
            document.querySelectorAll('input[name="_token"]').forEach(function (i) {
                i.value = d.token;
            });
            return d.token;
        } catch (e) {
            return null;
        }
    }

    /** Arabic message for a 419 that survived the retry (session really is gone). */
    var EXPIRED_MESSAGE = 'انتهت الجلسة. أعد تحميل الصفحة ثم حاول مرة أخرى.';

    /**
     * A stand-in JSON response, so no call site has to recognise a 419 itself:
     * every caller already reads {success, message} and will show something the
     * admin can act on instead of Laravel's raw "CSRF token mismatch."
     */
    function expiredResponse() {
        return new Response(
            JSON.stringify({ success: false, message: EXPIRED_MESSAGE }),
            { status: 419, headers: { 'Content-Type': 'application/json' } }
        );
    }

    /** fetch(), but the CSRF header is always current and a 419 is retried once. */
    async function csrfFetch(url, options) {
        var opts = Object.assign({}, options || {});
        opts.headers = Object.assign({}, opts.headers || {});
        opts.credentials = opts.credentials || 'same-origin';
        opts.headers['X-CSRF-TOKEN'] = currentToken();

        var resp = await fetch(url, opts);
        if (resp.status !== 419) { return resp; }

        var fresh = await refreshToken();
        if (fresh) {
            opts.headers['X-CSRF-TOKEN'] = fresh;
            resp = await fetch(url, opts);
            if (resp.status !== 419) { return resp; }
        }

        return expiredResponse();
    }

    window.csrfToken = currentToken;
    window.refreshCsrfToken = refreshToken;
    window.csrfFetch = csrfFetch;
    window.CSRF_EXPIRED_MESSAGE = EXPIRED_MESSAGE;
})();
