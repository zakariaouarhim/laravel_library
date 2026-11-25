/**
 * The central fetch wrapper in public/js/csrf.js.
 *
 * The bug it exists for: a page bakes csrf_token() into a <meta> tag, the
 * session rolls over while the page stays open, the remember-me cookie
 * re-authenticates silently, and every POST is then rejected with 419 while
 * GETs keep working. The wrapper refreshes the token and replays once.
 *
 * The replay is only safe because VerifyCsrfToken rejects before the controller
 * runs, so the first attempt had no effect. Everything below either proves the
 * recovery or proves a guard that keeps the wrapper from touching traffic it
 * must not touch.
 */
const { makeEnv } = require('./helpers/env');
const { suite, settle } = require('./helpers/assert');

module.exports = async function run() {
    const { check, results } = suite('csrf fetch wrapper');

    // --- the core case: an unmodified fetch() call with a stale token ---
    {
        const env = makeEnv();
        const r = await env.win.fetch('/admin/x/rewrite', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': 'OLD-TOKEN', 'Content-Type': 'application/json' },
            body: '{"a":1}',
        });
        check(r.status === 200, 'untouched fetch() recovers from a stale token', 'status=' + r.status);
        check(env.state.calls.length === 3, 'three requests: try, refresh, replay', 'calls=' + env.state.calls.length);
        const replay = env.call(2);
        check(replay.token === 'NEW-TOKEN', 'replay carries the refreshed token');
        check(replay.body === '{"a":1}', 'replay resends the SAME body');
        check(env.meta().content === 'NEW-TOKEN', 'meta tag updated for later calls');
        check(env.tokenInput().value === 'NEW-TOKEN', 'hidden _token inputs kept in step');
    }

    // --- a healthy request must pay nothing ---
    {
        const env = makeEnv();
        env.state.serverToken = 'OLD-TOKEN';
        const r = await env.win.fetch('/ok', { method: 'POST', headers: { 'X-CSRF-TOKEN': 'OLD-TOKEN' } });
        check(r.status === 200 && env.state.calls.length === 1,
            'healthy POST: one request, no refresh', 'calls=' + env.state.calls.length);
    }

    // --- every other failure passes straight through ---
    for (const code of [422, 500, 404, 401]) {
        const env = makeEnv();
        env.state.status = code;
        const r = await env.win.fetch('/x', { method: 'POST', headers: {} });
        check(r.status === code && env.state.calls.length === 1,
            code + ' passes through untouched', 'calls=' + env.state.calls.length);
    }

    // --- guards ---
    {
        const env = makeEnv();
        const r = await env.win.fetch('https://example.com/thing', { method: 'POST', headers: {} });
        check(r.status === 419 && env.state.calls.length === 1,
            'cross-origin 419 is not retried', 'calls=' + env.state.calls.length);
    }
    {
        // A Request carries immutable headers and a body that may already be
        // consumed, so rebuilding one is not worth the risk.
        const env = makeEnv();
        await env.win.fetch(new Request('https://alfokara.com/x', { method: 'POST' }));
        check(env.state.calls.length === 1, 'Request object passed straight through', 'calls=' + env.state.calls.length);
    }
    {
        // A stream cannot be sent twice, so it must not be replayed.
        const env = makeEnv();
        const stream = new ReadableStream({ start(c) { c.close(); } });
        const r = await env.win.fetch('/x', { method: 'POST', body: stream, headers: {}, duplex: 'half' });
        check(r.status === 419 && env.state.calls.length === 1,
            'stream body is not replayed (unreplayable)', 'calls=' + env.state.calls.length);
    }

    // --- bodies that carry the token as a field ---
    {
        const env = makeEnv();
        const fd = new env.win.FormData();
        fd.append('_token', 'OLD-TOKEN');
        fd.append('file', 'x');
        const r = await env.win.fetch('/upload', { method: 'POST', body: fd, headers: {} });
        check(r.status === 200, 'FormData POST recovers', 'status=' + r.status);
        check(fd.get('_token') === 'NEW-TOKEN', '_token field inside FormData re-synced', fd.get('_token'));
    }
    {
        const env = makeEnv();
        const params = new env.win.URLSearchParams();
        params.append('_token', 'OLD-TOKEN');
        await env.win.fetch('/form', { method: 'POST', body: params, headers: {} });
        check(params.get('_token') === 'NEW-TOKEN', '_token inside URLSearchParams re-synced', params.get('_token'));
    }

    // --- the three header shapes fetch() accepts ---
    {
        const env = makeEnv();
        await env.win.fetch('/x', { method: 'POST', headers: new env.win.Headers({ 'X-CSRF-TOKEN': 'OLD-TOKEN' }) });
        check(env.call(2).token === 'NEW-TOKEN', 'Headers instance handled', 'got=' + env.call(2).token);
    }
    {
        const env = makeEnv();
        await env.win.fetch('/x', { method: 'POST', headers: [['X-CSRF-TOKEN', 'OLD-TOKEN']] });
        check(env.call(2).token === 'NEW-TOKEN', 'array-of-pairs headers handled', 'got=' + env.call(2).token);
    }
    {
        const env = makeEnv();
        await env.win.fetch('/x', { method: 'POST', headers: { 'x-csrf-token': 'OLD-TOKEN' } });
        const keys = Object.keys(env.call(2).headers || {}).filter((k) => k.toLowerCase() === 'x-csrf-token');
        check(env.call(2).token === 'NEW-TOKEN' && keys.length === 1,
            'lowercase header replaced, not duplicated', 'keys=' + keys.length);
    }

    // --- genuinely logged out ---
    {
        const env = makeEnv();
        env.state.tokenEndpointWorks = false;
        const r = await env.win.fetch('/x', { method: 'POST', headers: {} });
        const body = await r.json();
        check(r.status === 419, 'logged out: still 419', 'status=' + r.status);
        check(body.success === false && body.message.indexOf('انتهت الجلسة') !== -1,
            'logged out: actionable Arabic message', body.message);
        check(body.message.indexOf('CSRF') === -1, 'raw "CSRF token mismatch" never reaches the user');
    }

    // --- at most one replay, even if the fresh token is also rejected ---
    {
        const env = makeEnv();
        env.state.refreshGives = 'STILL-WRONG';
        const r = await env.win.fetch('/x', { method: 'POST', headers: {} });
        check(env.posts('/x').length === 2, 'gives up after one replay (no loop)', 'posts=' + env.posts('/x').length);
        check(r.status === 419, 'and reports 419 when it gives up', 'status=' + r.status);
    }

    // --- the named helpers must not stack a second retry on the wrapper ---
    {
        const env = makeEnv();
        const r = await env.win.csrfPostJson('/x', { a: 1 });
        check(r.status === 200 && env.posts('/x').length === 2,
            'csrfPostJson: ONE retry in total, not two', 'posts=' + env.posts('/x').length);
    }
    {
        const env = makeEnv();
        await env.win.csrfPostJson('/x', { a: 1 });
        const first = env.posts('/x')[0];
        check(first.accept === 'application/json',
            'csrfPostJson sends Accept: application/json', 'so a 419 is JSON, not an HTML page');
        check(first.body === JSON.stringify({ a: 1 }), 'csrfPostJson serialises the body');
    }
    {
        const env = makeEnv();
        await env.win.csrfPostJson('/x');
        check(env.posts('/x')[0].body === undefined, 'csrfPostJson sends no body when none given');
    }

    // --- loading the file twice must not install anything twice ---
    {
        const env = makeEnv();
        env.loadAgain();
        await env.win.fetch('/x', { method: 'POST', headers: {} });
        check(env.posts('/x').length === 2, 'loaded twice: still exactly one replay', 'posts=' + env.posts('/x').length);
    }

    await settle();
    return results;
};
