/**
 * CSRF staleness recovery, installed once for the whole page.
 *
 * THE BUG THIS EXISTS FOR
 * Every page bakes csrf_token() into a <meta> tag when it is rendered, and
 * pages stay open for hours. Once SESSION_LIFETIME passes, the next request
 * starts a fresh session with a fresh token, and the remember-me cookie
 * re-authenticates silently - so GETs keep returning 200 while every POST is
 * rejected with 419 and the raw "CSRF token mismatch." reaches the user.
 * Observed in production on the admin review screens AND on a customer
 * wishlist click. Reloading fixed it, which is why it looked intermittent.
 *
 * WHY THIS IS CENTRAL AND NOT PER CALL SITE
 * There were ~26 fetch() call sites across 12 files plus inline page scripts,
 * each formatted differently, several of which captured the token once at page
 * load and so could never recover. Patching them one by one meant 26 chances
 * to miss one, and would not cover inline script blocks at all. Wrapping fetch
 * once fixes every current and future call site, and the retry is provably
 * safe: VerifyCsrfToken rejects before the controller runs, so the first
 * attempt had no effect and nothing can be applied twice.
 *
 * WHAT IS DELIBERATELY NOT COVERED
 * jQuery uses XMLHttpRequest, not fetch, so it needs its own hook below. There
 * the recovery is only partial - see installJQuery().
 */
(function () {
    if (window.__csrfInstalled) { return; }
    window.__csrfInstalled = true;

    var ENDPOINT = '/csrf-token';
    var EXPIRED_MESSAGE = 'انتهت الجلسة. أعد تحميل الصفحة ثم حاول مرة أخرى.';

    // Captured before the wrapper is installed: refreshToken() and the retry
    // must not recurse back through our own wrapper.
    var nativeFetch = window.fetch ? window.fetch.bind(window) : null;

    function metaTag() { return document.querySelector('meta[name="csrf-token"]'); }

    function currentToken() {
        var m = metaTag();
        return m ? (m.content || m.getAttribute('content') || '') : '';
    }

    /** Fresh token for this session, or null when the session is genuinely gone. */
    function refreshToken() {
        if (!nativeFetch) { return Promise.resolve(null); }

        return nativeFetch(ENDPOINT, {
            headers: { 'Accept': 'application/json' },
            credentials: 'same-origin',
        }).then(function (r) {
            return r.ok ? r.json().catch(function () { return {}; }) : {};
        }).then(function (d) {
            if (!d || !d.token) { return null; }

            var m = metaTag();
            if (m) { m.content = d.token; }
            // Keep hidden @csrf inputs in step so ordinary form submits on the
            // page survive the same rollover.
            var inputs = document.querySelectorAll('input[name="_token"]');
            for (var i = 0; i < inputs.length; i++) { inputs[i].value = d.token; }
            return d.token;
        }).catch(function () { return null; });
    }

    /**
     * Stand-in JSON response, so no call site has to recognise a 419 itself:
     * callers already read {success, message} and will show something the user
     * can act on instead of the raw technical message.
     */
    function expiredResponse() {
        return new Response(
            JSON.stringify({ success: false, message: EXPIRED_MESSAGE }),
            { status: 419, headers: { 'Content-Type': 'application/json' } }
        );
    }

    function sameOrigin(url) {
        try {
            return new URL(url, window.location.href).origin === window.location.origin;
        } catch (e) {
            return false;
        }
    }

    /** A body we can send a second time. A stream cannot be replayed. */
    function replayable(body) {
        if (body === undefined || body === null || typeof body === 'string') { return true; }
        if (typeof FormData !== 'undefined' && body instanceof FormData) { return true; }
        if (typeof URLSearchParams !== 'undefined' && body instanceof URLSearchParams) { return true; }
        if (typeof Blob !== 'undefined' && body instanceof Blob) { return true; }
        if (typeof ArrayBuffer !== 'undefined' && (body instanceof ArrayBuffer || ArrayBuffer.isView(body))) { return true; }
        return false;
    }

    /** Set X-CSRF-TOKEN on headers in whichever of the three shapes was used. */
    function withToken(headers, token) {
        if (typeof Headers !== 'undefined' && headers instanceof Headers) {
            var copy = new Headers(headers);
            copy.set('X-CSRF-TOKEN', token);
            return copy;
        }
        if (Array.isArray(headers)) {
            var pairs = headers.filter(function (p) {
                return String(p[0]).toLowerCase() !== 'x-csrf-token';
            });
            pairs.push(['X-CSRF-TOKEN', token]);
            return pairs;
        }

        var obj = {};
        for (var k in (headers || {})) {
            // Drop any differently-cased variant so ours is the only one.
            if (k.toLowerCase() !== 'x-csrf-token') { obj[k] = headers[k]; }
        }
        obj['X-CSRF-TOKEN'] = token;
        return obj;
    }

    /** Some endpoints take the token as a _token field rather than a header. */
    function syncBodyToken(body, token) {
        if (typeof FormData !== 'undefined' && body instanceof FormData && body.has('_token')) {
            body.set('_token', token);
        } else if (typeof URLSearchParams !== 'undefined' && body instanceof URLSearchParams && body.has('_token')) {
            body.set('_token', token);
        }
    }

    if (nativeFetch) {
        window.fetch = function (input, init) {
            // A Request object carries its own immutable headers and a body that
            // may already be consumed; rebuilding one correctly is not worth the
            // risk, so pass those straight through untouched.
            var isUrl = typeof input === 'string' || (typeof URL !== 'undefined' && input instanceof URL);
            if (!isUrl) { return nativeFetch(input, init); }

            var url = String(input);
            var options = init || {};

            return nativeFetch(url, options).then(function (resp) {
                if (resp.status !== 419 || !sameOrigin(url) || !replayable(options.body)) {
                    return resp;
                }

                return refreshToken().then(function (fresh) {
                    if (!fresh) { return expiredResponse(); }

                    var retry = {};
                    for (var k in options) { retry[k] = options[k]; }
                    retry.headers = withToken(options.headers, fresh);
                    syncBodyToken(retry.body, fresh);

                    return nativeFetch(url, retry).then(function (second) {
                        return second.status === 419 ? expiredResponse() : second;
                    });
                });
            });
        };
    }

    /** fetch() with the current token attached. The retry comes from the wrapper. */
    function csrfFetch(url, options) {
        var opts = options || {};
        var copy = {};
        for (var k in opts) { copy[k] = opts[k]; }
        copy.credentials = copy.credentials || 'same-origin';
        copy.headers = withToken(opts.headers, currentToken());

        return window.fetch(url, copy);
    }

    /**
     * The canonical JSON POST for this app: correct headers, CSRF recovery, and
     * Accept: application/json so a 419 comes back as JSON. Without that header
     * Laravel answers with an HTML error page, which callers doing
     * response.json() throw on - turning a recoverable session rollover into a
     * generic "something went wrong".
     *
     * Pass no body for endpoints that take none.
     */
    function csrfPostJson(url, body) {
        var options = {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        };
        if (body !== undefined && body !== null) {
            options.body = JSON.stringify(body);
        }

        return csrfFetch(url, options);
    }

    /**
     * jQuery hook. PARTIAL BY NECESSITY, and the limit is worth knowing:
     *
     *  - the prefilter fixes the common bug, which is a token captured once via
     *    $.ajaxSetup at page load and then reused for the life of the page;
     *  - on a 419 the token is refreshed, so the NEXT action works;
     *  - the failed call itself is replayed only when its settings carry a
     *    success callback. Calls written as $.ajax(...).done(...) keep their
     *    handlers on the returned promise, not in settings, so replaying would
     *    run on the server while the UI never heard about it. Overriding
     *    $.ajax to return our own promise would cover those, but it would also
     *    stop returning a real jqXHR, breaking any caller that uses .abort() or
     *    reads responseText. Not worth it on admin screens, where the next
     *    click now succeeds.
     */
    function installJQuery($) {
        if (!$ || !$.ajaxPrefilter || $.__csrfPatched) { return; }
        $.__csrfPatched = true;

        $.ajaxPrefilter(function (options, originalOptions, xhr) {
            if (options.crossDomain) { return; }
            xhr.setRequestHeader('X-CSRF-TOKEN', currentToken());
        });

        $(document).ajaxError(function (event, xhr, settings) {
            if (xhr.status !== 419 || settings.__csrfRetried) { return; }
            settings.__csrfRetried = true;

            refreshToken().then(function (fresh) {
                if (fresh && typeof settings.success === 'function') {
                    $.ajax(settings);
                }
            });
        });
    }

    installJQuery(window.jQuery);
    if (!window.jQuery && document && typeof document.addEventListener === 'function') {
        // jQuery is often loaded after this file; try again once the page is parsed.
        document.addEventListener('DOMContentLoaded', function () { installJQuery(window.jQuery); });
    }

    window.csrfToken = currentToken;
    window.refreshCsrfToken = refreshToken;
    window.csrfFetch = csrfFetch;
    window.csrfPostJson = csrfPostJson;
    window.CSRF_EXPIRED_MESSAGE = EXPIRED_MESSAGE;
})();
