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
 * JQUERY
 * jQuery uses XMLHttpRequest, not fetch, so the wrapper cannot see it and
 * $.ajax is replaced instead - same guarantee, including $.get/$.post and
 * promise-style .done()/.fail() callers. See installJQuery().
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
     * jQuery hook. jQuery uses XMLHttpRequest, so the fetch wrapper above
     * cannot see it and $.ajax needs replacing to get the same guarantee.
     *
     * Two parts:
     *
     *  1. a prefilter that sets X-CSRF-TOKEN from the meta tag at SEND time.
     *     This alone fixes ManagementSystem.js, which captured a token via
     *     $.ajaxSetup at init and then reused it for the life of the page. It
     *     also means a replay needs no header handling of its own: the
     *     prefilter runs again and picks up the refreshed token.
     *
     *  2. an $.ajax replacement that, on a same-origin 419, refreshes the
     *     token and replays the request ONCE, resolving the caller's own
     *     promise with the result of whichever attempt succeeded.
     *
     * Why replace $.ajax rather than retry from an ajaxError handler: a
     * handler only sees `settings`, so it can only re-fire calls whose
     * callbacks were passed IN settings. authors.js is written entirely as
     * $.get(...).done(...) / $.post(...).fail(...), which keep their handlers
     * on the returned promise - replaying those would run on the server while
     * the UI never heard about it. $.get, $.post and $.getJSON all route
     * through $.ajax, so replacing it covers them too.
     *
     * The returned object is a promise with the jqXHR surface proxied onto it
     * (abort, header access), delegating to whichever attempt is live. Audited
     * first: nothing in this codebase holds a jqXHR from $.ajax/$.get/$.post -
     * every .abort() call is an AbortController belonging to a fetch - so no
     * caller depends on the real jqXHR being returned.
     *
     * success/error/complete, when passed in settings, are stripped from what
     * jQuery sees and fired by us on the FINAL outcome only. Otherwise a
     * recovered call would run the caller's error handler for the 419 first
     * and then its success handler, which is worse than the bug.
     */
    function installJQuery($) {
        if (!$ || !$.ajaxPrefilter || $.__csrfPatched) { return; }
        $.__csrfPatched = true;

        $.ajaxPrefilter(function (options, originalOptions, xhr) {
            if (options.crossDomain) { return; }
            xhr.setRequestHeader('X-CSRF-TOKEN', currentToken());
        });

        var nativeAjax = $.ajax;

        $.ajax = function (url, options) {
            // jQuery accepts ajax(url, options) and ajax(options).
            var settings = typeof url === 'string'
                ? $.extend({}, options || {}, { url: url })
                : $.extend({}, url || {});

            // Held back so they fire once, on the outcome the caller should see.
            var userSuccess = settings.success;
            var userError = settings.error;
            var userComplete = settings.complete;
            delete settings.success;
            delete settings.error;
            delete settings.complete;

            var deferred = $.Deferred();
            var live = null;
            var retried = false;

            function succeed(context, data, textStatus, jqXHR) {
                if (userSuccess) { userSuccess.apply(context, [data, textStatus, jqXHR]); }
                if (userComplete) { userComplete.apply(context, [jqXHR, textStatus]); }
                deferred.resolveWith(context, [data, textStatus, jqXHR]);
            }

            function giveUp(context, jqXHR, textStatus, errorThrown) {
                if (userError) { userError.apply(context, [jqXHR, textStatus, errorThrown]); }
                if (userComplete) { userComplete.apply(context, [jqXHR, textStatus]); }
                deferred.rejectWith(context, [jqXHR, textStatus, errorThrown]);
            }

            function attempt() {
                live = nativeAjax(settings);

                live.done(function (data, textStatus, jqXHR) {
                    succeed(this, data, textStatus, jqXHR);
                });

                live.fail(function (jqXHR, textStatus, errorThrown) {
                    var context = this;
                    if (jqXHR.status !== 419 || retried || settings.crossDomain) {
                        giveUp(context, jqXHR, textStatus, errorThrown);
                        return;
                    }

                    retried = true;
                    refreshToken().then(function (fresh) {
                        if (!fresh) {
                            giveUp(context, jqXHR, textStatus, errorThrown);
                            return;
                        }
                        // The prefilter attaches the refreshed token for us.
                        attempt();
                    });
                });
            }

            attempt();

            var api = deferred.promise();
            ['abort', 'getAllResponseHeaders', 'getResponseHeader', 'setRequestHeader',
             'overrideMimeType', 'statusCode'].forEach(function (name) {
                api[name] = function () {
                    return live && typeof live[name] === 'function'
                        ? live[name].apply(live, arguments)
                        : undefined;
                };
            });

            return api;
        };
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
