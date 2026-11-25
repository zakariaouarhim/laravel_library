/**
 * The per-file POST helpers that sit on top of csrf.js: wishlistPost (card.js),
 * cartPost (cart.js) and checkoutPost (checkout.js).
 *
 * Each delegates to window.csrfPostJson and keeps a short fallback for the case
 * where csrf.js is missing from a page, so a view that forgets the include
 * degrades instead of breaking. These check both paths, and the details that
 * matter for money: the replay must resend the SAME body (a coupon re-applied
 * with an empty body would silently apply nothing), and endpoints that take no
 * body must still receive none.
 */
const { makeEnv } = require('./helpers/env');
const { suite, settle } = require('./helpers/assert');

module.exports = async function run() {
    const { check, results } = suite('csrf page helpers');

    // checkout.js touches jQuery at load time, so give the window a real one.
    const load = () => makeEnv({ withJQuery: true, scripts: ['card.js', 'cart.js', 'checkout.js'] });

    // --- the costly case: a coupon applied on a page left open too long ---
    {
        const env = load();
        const r = await env.win.checkoutPost('/checkout/apply-coupon', { code: 'X', subtotal: 100 });
        check(r.status === 200, 'coupon apply recovers from a stale token', 'status=' + r.status);
        const posts = env.posts('/checkout/apply-coupon');
        check(posts.length === 2, 'one replay', 'posts=' + posts.length);
        check((posts[1] || {}).body === JSON.stringify({ code: 'X', subtotal: 100 }),
            'replay resends the SAME body', (posts[1] || {}).body);
        check((posts[0] || {}).accept === 'application/json', 'Accept json, so a 419 is not an HTML page');
    }

    // --- bodyless endpoints ---
    {
        const env = load();
        await env.win.checkoutPost('/checkout/remove-coupon');
        check(env.posts('/checkout/remove-coupon')[0].body === undefined,
            'bodyless POST sends no body', String(env.posts('/checkout/remove-coupon')[0].body));
    }

    // --- cart quantity and removal ---
    {
        const env = load();
        env.state.serverToken = 'OLD-TOKEN';
        const r = await env.win.cartPost('/cart/update-quantity', { id: 7, quantity: 3 });
        check(r.status === 200 && env.posts('/cart/update-quantity').length === 1,
            'cart update: healthy = one request', 'posts=' + env.posts('/cart/update-quantity').length);
        check(env.posts('/cart/update-quantity')[0].method === 'POST', 'method is POST');
    }
    {
        const env = load();
        const r = await env.win.cartPost('/remove-from-cart', { id: 7 });
        check(r.status === 200, 'cart removal recovers from a stale token', 'status=' + r.status);
    }

    // --- wishlist, the one actually seen failing in the production log ---
    {
        const env = load();
        const r = await env.win.wishlistPost('/wishlist-session/add/901');
        check(r.status === 200, 'guest wishlist add recovers', 'status=' + r.status);
        check(env.posts('/wishlist-session/add/901')[0].body === undefined,
            'wishlist POST stays bodyless');
    }

    // --- csrf.js absent: each helper must degrade, not crash ---
    // A fresh env per helper on purpose: the first recovery rewrites the meta
    // tag, so a shared env would leave the later helpers reading an
    // already-refreshed token and prove nothing about the fallback.
    for (const name of ['cartPost', 'checkoutPost', 'wishlistPost']) {
        const env = load();
        delete env.win.csrfPostJson;
        delete env.win.csrfFetch;

        const r = await env.win[name]('/x', { a: 1 });
        const first = env.call(0);
        // The first attempt carries the page token with the right headers, and
        // it still recovers: the fetch wrapper is global, so losing the named
        // helpers does not lose the retry.
        check(first.token === 'OLD-TOKEN' && first.accept === 'application/json' && r.status === 200,
            name + ' degrades to the page token, still recovers',
            'token=' + first.token + ' status=' + r.status);
    }

    // --- no meta tag at all must not throw, as the old code did ---
    {
        const env = load();
        delete env.win.csrfPostJson;
        env.meta().remove();
        let threw = false;
        try { await env.win.cartPost('/x', { a: 1 }); } catch (e) { threw = true; }
        check(!threw, 'missing meta tag does not throw');
    }

    await settle();
    return results;
};
