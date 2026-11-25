/**
 * Test environment for public/js/csrf.js and the page scripts that use it.
 *
 * Everything runs in jsdom with a real document, and ONLY the network is faked.
 * That is deliberate: the first versions of these tests used hand-rolled
 * document/fetch stubs, and three separate failures turned out to be gaps in
 * the stubs rather than bugs in the code - a missing window.location, a missing
 * document.addEventListener, and a test that reassigned window.fetch after
 * csrf.js had wrapped it, destroying the very thing under test. A real DOM
 * removes that whole class of false signal.
 */
const fs = require('fs');
const path = require('path');
const vm = require('vm');
const { JSDOM } = require('jsdom');

const PUBLIC_JS = path.join(__dirname, '..', '..', '..', 'public', 'js');

function readJs(name) {
    return fs.readFileSync(path.join(PUBLIC_JS, name), 'utf8');
}

/**
 * @param {object} opts
 *   scripts        - public/js files to evaluate after csrf.js, in order
 *   withJQuery     - expose jQuery on the window (needed by checkout.js)
 *   jqueryLate     - mirror production on the admin pages: csrf.js executes
 *                    BEFORE jQuery, so the hook must install from its
 *                    DOMContentLoaded fallback
 *   url            - page URL, for same-origin checks
 */
function makeEnv(opts) {
    const options = opts || {};
    const dom = new JSDOM(
        '<!DOCTYPE html><html><head>' +
        '<meta name="csrf-token" content="OLD-TOKEN">' +
        '</head><body>' +
        '<form><input type="hidden" name="_token" value="OLD-TOKEN"></form>' +
        '</body></html>',
        {
            url: options.url || 'https://alfokara.com/admin/page',
            runScripts: 'outside-only',
        }
    );

    const win = dom.window;

    // jsdom ships no fetch, and these are the globals csrf.js builds on.
    win.Response = Response;
    win.Headers = Headers;
    win.FormData = FormData;
    win.URLSearchParams = URLSearchParams;
    win.Blob = Blob;
    win.AbortController = AbortController;

    const state = {
        calls: [],              // every request, in order
        serverToken: 'NEW-TOKEN',   // what the server will accept
        refreshGives: null,     // what /csrf-token hands back, when different.
                                // Needed to prove "gives up after one replay":
                                // if the endpoint returns the accepted token,
                                // the replay legitimately succeeds.
        tokenEndpointWorks: true,
        status: null,           // force a status, to test pass-through
    };

    function header(headers, name) {
        if (!headers) { return null; }
        if (headers instanceof Headers) { return headers.get(name); }
        if (Array.isArray(headers)) {
            const hit = headers.find((p) => String(p[0]).toLowerCase() === name.toLowerCase());
            return hit ? hit[1] : null;
        }
        for (const key in headers) {
            if (key.toLowerCase() === name.toLowerCase()) { return headers[key]; }
        }
        return null;
    }

    const json = (body, status) => new Response(JSON.stringify(body), {
        status: status,
        headers: { 'Content-Type': 'application/json' },
    });

    win.fetch = function (input, init) {
        const url = String(input && input.url ? input.url : input);
        const opts = init || {};
        const token = header(opts.headers, 'X-CSRF-TOKEN');
        state.calls.push({
            url: url,
            token: token,
            body: opts.body,
            method: opts.method,
            accept: header(opts.headers, 'Accept'),
            headers: opts.headers,
            via: 'fetch',
        });

        if (url.indexOf('/csrf-token') === 0) {
            return Promise.resolve(state.tokenEndpointWorks
                ? json({ token: state.refreshGives || state.serverToken }, 200)
                : new Response('', { status: 401 }));
        }
        if (state.status) {
            return Promise.resolve(json({}, state.status));
        }
        if (url.indexOf('http') === 0 && url.indexOf('https://alfokara.com') !== 0) {
            return Promise.resolve(json({ message: 'CSRF token mismatch.' }, 419));
        }
        if (token !== state.serverToken) {
            return Promise.resolve(json({ message: 'CSRF token mismatch.' }, 419));
        }
        return Promise.resolve(json({ success: true, url: url }, 200));
    };

    let $ = null;
    if (options.withJQuery || options.jqueryLate) {
        $ = require('jquery')(win);
        if (!options.jqueryLate) { win.jQuery = win.$ = $; }
    }

    // cart.js reads these.
    win.routes = { updateCartQuantity: '/cart/update-quantity', removeFromCart: '/remove-from-cart' };

    const context = dom.getInternalVMContext();
    vm.runInContext(readJs('csrf.js'), context, { filename: 'csrf.js' });

    if (options.jqueryLate) {
        win.jQuery = win.$ = $;
        win.document.dispatchEvent(new win.Event('DOMContentLoaded', { bubbles: true }));
    }

    (options.scripts || []).forEach(function (name) {
        vm.runInContext(readJs(name), context, { filename: name });
    });

    return {
        win: win,
        $: $,
        state: state,
        dom: dom,
        context: context,
        meta: () => win.document.querySelector('meta[name="csrf-token"]'),
        tokenInput: () => win.document.querySelector('input[name="_token"]'),
        // Safe accessors: when a regression means a request was never made,
        // a check should FAIL with a readable message, not throw on undefined.
        call: (i) => state.calls[i] || {},
        posts: (url) => state.calls.filter((c) => c.url === url),
        requests: (via) => state.calls.filter((c) => c.via === (via || 'fetch')),
        loadAgain: () => vm.runInContext(readJs('csrf.js'), context, { filename: 'csrf-again.js' }),
    };
}

module.exports = { makeEnv, readJs };
