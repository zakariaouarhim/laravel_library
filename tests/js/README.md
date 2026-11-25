# JS tests

Guards `public/js/csrf.js` and the page scripts that use it.

```
npm run test:js
```

Exits non-zero if any check fails.

## Why these exist

A session rolling over under an open page made every POST fail with 419
("CSRF token mismatch") while GETs kept working, because the token is baked
into a `<meta>` tag at render time and the remember-me cookie re-authenticates
silently. It hit the admin review screens and a customer's wishlist click in
production. `csrf.js` now refreshes the token and replays the request once.

The replay is only safe because `VerifyCsrfToken` rejects before the controller
runs, so the first attempt had no effect. Most checks here defend that
reasoning and the guards around it.

## Layout

| file | covers |
|---|---|
| `csrf-fetch.test.js` | the central `fetch` wrapper: recovery, the guards, header shapes, body types |
| `csrf-helpers.test.js` | `wishlistPost` / `cartPost` / `checkoutPost` and their no-csrf.js fallback |
| `csrf-jquery.test.js` | the `$.ajax` replacement, against real jQuery 3.6.0 |
| `helpers/env.js` | jsdom page + fake network, shared by all suites |

## Two things worth knowing before editing

**Everything runs in jsdom and only the network is faked.** Earlier versions
used hand-written `document`/`fetch` stubs and three separate "failures" turned
out to be gaps in the stubs: a missing `window.location`, a missing
`document.addEventListener`, and a test that reassigned `window.fetch` *after*
csrf.js had wrapped it, destroying the thing under test. A real DOM removes
that class of false signal.

**`state.refreshGives` exists for a reason.** To prove the code gives up after
one replay, `/csrf-token` has to hand back a token the server still rejects.
Setting `state.serverToken` alone does not work - the endpoint would return
that same value and the replay would legitimately succeed.

The suites were checked by mutation: disabling the fetch retry fails
`csrf-fetch` and `csrf-helpers`, and disabling the jQuery replay fails
`csrf-jquery`.
