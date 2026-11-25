/**
 * The $.ajax replacement in public/js/csrf.js, against REAL jQuery 3.6.0 - the
 * version the admin pages load from the CDN.
 *
 * Only the network is faked, through jQuery's own ajaxTransport extension
 * point, so prefilters, $.Deferred, $.extend and the callback/promise
 * machinery are all genuine. A hand-written $ stub would only test assumptions
 * about jQuery rather than jQuery itself.
 *
 * Why $.ajax is replaced rather than retried from an ajaxError handler: such a
 * handler only sees `settings`, so it can only re-fire calls whose callbacks
 * were passed IN settings. authors.js is written entirely as
 * $.get(...).done(...) / $.post(...).fail(...), which keep their handlers on
 * the returned promise.
 */
const { makeEnv } = require('./helpers/env');
const { suite, settle } = require('./helpers/assert');

/** jQuery's transport hook: complete(status, statusText, responses, headers). */
function fakeTransport(env) {
    env.$.ajaxTransport('+*', function (options) {
        return {
            send: function (headers, complete) {
                const token = headers['X-CSRF-TOKEN'];
                env.state.calls.push({ url: options.url, token: token, via: 'ajax' });
                setTimeout(function () {
                    if (!env.state.tokenEndpointWorks && options.url.indexOf('/csrf-token') === 0) {
                        complete(401, 'error', {}, '');
                    } else if (token !== env.state.serverToken) {
                        complete(419, 'error', { json: { message: 'CSRF token mismatch.' } }, '');
                    } else {
                        complete(200, 'success', { json: { ok: true, url: options.url } }, '');
                    }
                }, 0);
            },
            abort: function () { env.state.aborted = true; },
        };
    });
}

function ajaxCalls(env) {
    return env.state.calls.filter((c) => c.via === 'ajax');
}

/** Safe indexing: a regression should fail a check, not throw on undefined. */
function ajaxCall(env, i) {
    return ajaxCalls(env)[i] || {};
}

module.exports = async function run() {
    const { check, results } = suite('csrf jQuery $.ajax');

    const load = (late) => {
        const env = makeEnv(late ? { jqueryLate: true } : { withJQuery: true });
        fakeTransport(env);
        return env;
    };

    // --- the authors.js pattern: $.get(...).done() with a stale token ---
    {
        const env = load();
        let data = null, failed = false;
        await new Promise((resolve) => {
            env.$.get('/admin/authors/5')
                .done(function (response) { data = response; resolve(); })
                .fail(function () { failed = true; resolve(); });
        });
        check(!failed && data && data.ok === true,
            '$.get().done() recovers from a stale token', failed ? 'fail() fired' : 'done() received data');
        check(ajaxCalls(env).length === 2, 'sent twice: original plus one replay', 'sent=' + ajaxCalls(env).length);
        check(ajaxCall(env, 0).token === 'OLD-TOKEN' && ajaxCall(env, 1).token === 'NEW-TOKEN',
            'replay carried the refreshed token',
            'first=' + ajaxCall(env, 0).token + ' second=' + ajaxCall(env, 1).token);
        check(env.meta().content === 'NEW-TOKEN', 'meta tag updated');
        check(env.tokenInput().value === 'NEW-TOKEN', 'hidden _token input updated');
    }

    // --- $.post too ---
    {
        const env = load();
        let ok = false;
        await new Promise((resolve) => {
            env.$.post('/admin/authors/5/names', { name: 'x' })
                .done(function () { ok = true; resolve(); })
                .fail(resolve);
        });
        check(ok, '$.post().done() recovers', 'sent=' + ajaxCalls(env).length);
    }

    // --- healthy call pays nothing ---
    {
        const env = load();
        env.state.serverToken = 'OLD-TOKEN';
        let ok = false;
        await new Promise((resolve) => {
            env.$.get('/admin/authors/api').done(function () { ok = true; resolve(); }).fail(resolve);
        });
        check(ok && ajaxCalls(env).length === 1, 'healthy call: one request, no refresh', 'sent=' + ajaxCalls(env).length);
    }

    // --- genuinely logged out: fail() once, never a silent hang ---
    {
        const env = load();
        env.state.tokenEndpointWorks = false;
        let failCount = 0, doneCount = 0, status = null;
        await new Promise((resolve) => {
            env.$.get('/admin/authors/5')
                .done(function () { doneCount++; resolve(); })
                .fail(function (xhr) { failCount++; status = xhr.status; resolve(); });
        });
        await settle();
        check(status === 419, 'fail() receives the real 419 jqXHR', 'status=' + status);
        check(failCount === 1 && doneCount === 0, 'logged out: fail() once, done() never',
            'fail=' + failCount + ' done=' + doneCount);
    }

    // --- settings-style callbacks (ManagementSystem.js) ---
    {
        const env = load();
        let success = 0, error = 0, complete = 0;
        await new Promise((resolve) => {
            env.$.ajax({
                url: '/admin/products/api/1',
                type: 'POST',
                success: function () { success++; },
                error: function () { error++; },
                complete: function () { complete++; resolve(); },
            });
        });
        await settle();
        // Without withholding these, a recovered call would run the caller's
        // error handler for the 419 and then its success handler.
        check(success === 1 && error === 0, 'success fires once, error never fires for a recovered 419',
            'success=' + success + ' error=' + error);
        check(complete === 1, 'complete fires exactly once', 'complete=' + complete);
    }

    // --- at most one replay ---
    {
        const env = load();
        env.state.refreshGives = 'STILL-WRONG';
        let failed = false;
        await new Promise((resolve) => {
            env.$.get('/x').done(resolve).fail(function () { failed = true; resolve(); });
        });
        await settle();
        check(failed && ajaxCalls(env).length === 2, 'gives up after one replay (no loop)',
            'sent=' + ajaxCalls(env).length);
    }

    // --- the returned object must still look like a jqXHR ---
    {
        const env = load();
        const xhr = env.$.get('/admin/authors/api');
        check(typeof xhr.done === 'function' && typeof xhr.fail === 'function' && typeof xhr.then === 'function',
            'returned object keeps the promise interface');
        check(typeof xhr.abort === 'function' && typeof xhr.getResponseHeader === 'function',
            'returned object proxies the jqXHR surface');
        let threw = false;
        try { xhr.abort(); } catch (e) { threw = true; }
        check(!threw, 'abort() on the returned object does not throw');
        await settle();
    }

    // --- PRODUCTION ORDER: csrf.js is at line 16 of the blade, jQuery at 449,
    //     both deferred - so the hook installs from the DOMContentLoaded
    //     fallback, not the immediate path the cases above use.
    {
        const env = load(true);
        check(env.$.__csrfPatched === true, 'late jQuery: hook installs via DOMContentLoaded');
        let data = null;
        await new Promise((resolve) => {
            env.$.get('/admin/authors/9').done(function (r) { data = r; resolve(); }).fail(resolve);
        });
        check(data && data.ok === true && ajaxCalls(env).length === 2,
            'late jQuery: .done() still recovers', 'sent=' + ajaxCalls(env).length);
    }

    // --- loading csrf.js twice must not wrap $.ajax twice ---
    {
        const env = load();
        env.loadAgain();
        await new Promise((resolve) => { env.$.get('/admin/authors/api').done(resolve).fail(resolve); });
        await settle();
        check(ajaxCalls(env).length === 2, 'loaded twice: still exactly one replay, not three',
            'sent=' + ajaxCalls(env).length);
    }

    await settle();
    return results;
};
