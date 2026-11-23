# Search latency fix (2026-11-16)

- [x] 1.1 Slim autocomplete JSON (160,670 B → 2,101 B; cost_price leak closed; 3 queries)
- [x] 1.2 Defer search_queries insert (verified row written after response via artisan serve)
- [x] 1.3 header.js: AbortController, trim, client cache (Map, cap 30), spinner only on fetch
- [x] 1.4 throttle:60,1 on /search-books + /search-results
- [x] 2.1 withCount/withAvg reviews on results + related books (stars verified rendering)
- [x] 2.2 Meili attributesToRetrieve=[id] for the 500-id fetch
- [x] 2.3 relevance ordering (FIELD(id,...)) when no sort param — verified Harry Potter first
- [x] 2.4 single COUNT (paginate), cached sidebar categories, popularCategories only when empty, RAND() → bounded id pool + shuffle
- [x] 2.5 header mega-menu with('children'), cache key header_nav_categories_v2
- [x] 3.1 all 5 legacy route closures → LegacyRedirectController; route:cache verified OK; CLAUDE.md deploy steps now use config/route/view:cache
- [x] 3.2 Cloudflare guide given to user (DNS change is theirs to make; verify later with cf-ray header)
- [x] Deploy + verify VPS (results page local: 14 queries, was ~80)

## Review (2026-11-16, search latency)
Deployed commit 60177aa. Verified on alfokara.com:
- autocomplete: 2,108 B payload (was 160,670 B), 0.11s server-side, no cost_price leak
- search-results: 200, 0.19s server-side, relevance-ordered, stars render
- legacy /author/{id} + /moredetail-v2/{id} 301s work via LegacyRedirectController under route:cache
- deferred search_queries insert confirmed under php-fpm (row written post-response)
- config/route/view caches now BUILT in production — deploys must re-cache (CLAUDE.md updated)
Remaining latency from Morocco (~1-2s cold TTFB) is network distance → Cloudflare
onboarding is the user's next step; re-measure after nameserver switch.

# Author name variants (string aliases, no author row) — extension

- [x] 1. Migration: `author_names` table (author_id FK cascade, name, unique per author)
- [x] 2. `AuthorName` model + `Author::nameVariants()`; union variants into `allNames()`
- [x] 3. `Book::makeSearchableUsing` eager-loads variants (toSearchableArray already uses allNames)
- [x] 4. `publicIndex` q-search matches variants (own + aliases'); `publicShow` "also known as" uses allNames
- [x] 5. Admin: add/delete variant endpoints (re-index group books) + UI in edit-author modal
- [x] 6. Verify locally: variant search in Meili, listing search, author page display

## Review (2026-11-15, variants extension)
Verified locally with variant "البير كامي" on the Camus group: Meili search returns the
group's books top-ranked; /authors?q= surfaces the canonical row; author page lists the
variant under "يُعرف أيضاً باسم". Variants are added/removed live in the edit-author
modal and each change re-indexes the group's books via the queue.
Not yet deployed (needs: migrate --force + view/route/config clear + queue:restart;
no scout:import needed — docs only change when variants are added).

# Canonical Author Linking (multilingual author names)

Plan: C:\Users\zakar\.claude\plans\i-have-an-ess-greedy-tome.md (approved 2026-11-15)

- [x] 1. Migration: `authors.canonical_author_id` nullable self-FK, nullOnDelete
- [x] 2. `Author` model: canonical/aliases relations, isAlias, aliasBooks, scopeCanonicalOnly, groupRoot, groupIds, allNames
- [x] 3. `Book::toSearchableArray()` + `makeSearchableUsing`; scout config searchableAttributes
- [x] 4. `AuthorController::publicShow` — 301 alias→canonical, group-wide book queries; author.blade.php alias names; legacy /author/{id} redirect to root
- [x] 5. `AuthorController::publicIndex` — canonicalOnly, alias-name search, combined counts; authors.blade.php count display
- [x] 6. Admin: linkCanonical/unlinkCanonical routes + methods, destroy guard, getAuthorsApi badges, link/unlink UI (modal + JS)
- [x] 7. Local verify: migrate, scout re-index, search both scripts, author page merge, listing

## Review (2026-11-15)

Implemented the Goodreads model: one canonical `authors` row per real author, other-script
rows linked via `canonical_author_id` (always flat, flattened on link, cycle-guarded).
Books keep their own `author_id` so each displays its author name in its own script.

Verified locally (test pair: "ALBERT CAMUS" id 106 ← alias "ألبير كامو" id 504, kept in dev DB):
- Meilisearch search "كامو" returns The Stranger / THE PLAGUE / L'étranger ✔
- `/مؤلف/البير-كامو` → 301 → `/مؤلف/albert-camus`; merged page shows alias name + books ✔
- `/authors?q=كامو` surfaces the canonical Latin row; alias hidden from listing ✔
- legacy `/author/504` 301s straight to canonical slug ✔
- admin API returns `canonical` + `aliases_count`; authors.js passes `node --check` ✔

VPS deploy needs (beyond git pull): `migrate --force`, `scout:sync-index-settings`,
`scout:import "App\Models\Book"`, `queue:restart` — then link duplicate pairs in
admin → المؤلفون (ربط button). All as www-data per CLAUDE.md.

Known pre-existing gaps (unchanged): renaming an author doesn't re-index its books;
Arabic letter filter doesn't fold alif variants.

# Aseer Al Kotob scraper (2026-11-16)

Found: aseeralkotb.com has an open JSON:API (no Cloudflare challenge on /api/*,
robots.txt allows all). Single-book endpoint GET /api/v1/books/{id} returns
title, description, ISBN, cover, pages, year, authors, publisher, categories.
Ids are dense 1..~72,824 (404 on gaps). Listing endpoints don't paginate, so
the scraper walks ids. Prices are USD (not our retail) → stored null.

- [x] 1. `ak:scrape` command (id walk, chunked upserts, resume via max ak: id, --dry-run/--limit/--from/--to/--delay)
- [x] 2. Controller: allow `source=ak` + dedup_key filter (and exclude from almouggar)
- [x] 3. Blade: add Aseer Al Kotob to the source dropdown
- [x] 4. Smoke test: dry-run, then real 20-row run; verify dashboard shows them

## Review (2026-11-18, Aseer Al Kotob scrape)
Full run finished: 69,488 ak rows (ids 1..72,826; ~3,300 ids missing/broken on their side).
All have a description; 62,273 have a checksum-valid ISBN; 59,529 pass the default
quality>=7 filter; 56,737 Arabic; only 131 ISBNs already in our store.
Fixes made during the run:
- skip isolated server errors (id 4827 always 500s); abort only after 10 in a row
- CSP img-src += cdn.aseeralkotb.com (covers were blocked by our own CSP)
- normalizeCoverUrl() upgrades 250x375 thumbnails to 1000x1500 at read time
- memory: Ignition kept 200 queries w/ bindings (~45 MB) → unsetEventDispatcher();
  Laravel 8 Http::pool() per batch leaked + wedged in a curl spin → one Guzzle client,
  settle() promises, low-speed abort. Flat 28 MB for the whole run afterwards.
Not yet committed/deployed; VPS DB has no ak rows yet.

### Deployed 2026-11-18 (commit 40da15d)
Code: pull + config/route/view:cache on the VPS; `ak:scrape` + `lm:scrape` registered.
Data: 69,488 ak rows transferred via a staging table (mysqldump of `ak_transfer`,
md5-verified upload, INSERT…SELECT with explicit columns so local ids never
travelled, ON DUPLICATE KEY UPDATE on the unique dedup_key → re-runnable).
VPS catalogue_reference: 84,050 → 153,538. Verified on the VPS: all 69,488 have a
description, 59,529 pass quality>=7, 56,737 Arabic, 24/24 covers on page 1 use
1000x1500, and 0 suggested primary categories pointed at a non-existent id
(suggester resolves by name, so the local/VPS id mismatch is a non-issue).
Live CSP img-src confirmed to include cdn.aseeralkotb.com. Staging tables and
dumps dropped on both sides.
NOT deployed (still local WIP): low-stock panel (AdminBookController@updateStock,
dashboard.blade.php, products.update-stock route) and the Counter feature.

## Fix 2026-11-19 — أسير الكتب covers never downloaded (commit 9e2f88c)
Two independent faults, both found only when the user approved a real book:
1. `ImageService::downloadFromUrl()` fetched with `@file_get_contents()`; PHP's
   http wrapper sends the path verbatim, so these Arabic-named cover URLs
   failed. Now fetched via the HTTP client with the path percent-encoded when
   it holds non-ASCII; ASCII URLs pass through byte-identical (7 other callers
   unaffected — verified Google-Books-style and pre-encoded URLs unchanged).
2. On the VPS `public/images/books/large` was root:root, so www-data could not
   write it — main + thumb appeared, large silently didn't. chowned to
   www-data, along with 547 root-owned cover files in images/books.
Verified on the VPS: 3 sample books each produce main+thumb+large. Repaired the
one already-imported book (#1379) — its cover now serves 200 at alfokara.com.
Open follow-up: approve() still reports success when the cover fails; it should
warn the admin instead of silently creating an image-less book.

# Fold API book search into catalogue-import (2026-11-19)

- [x] 1. `CatalogueDraftService`: merge resolved sources -> one draft; assemble an
      `api:` catalogue_reference row (stable dedup_key, honest completeness,
      `langue` token, author de-crufting, junk-ISBN rejection)
- [x] 2. Endpoints: `find` (store + unfiltered catalogue, free), `api-search`
      (resolveSources*, read-only), `adopt` (persist + return a grid payload)
- [x] 3. UI: "find this book" panel from the empty state + toolbar; 3 steps;
      non-blocking; injects the card and opens the modal on adopt
- [x] 4. `api` source filter + quality auto-relax (added `ak`, which was missed)
- [x] 5. Author binding (hidden author_id, honoured when the name agrees) — both
      import screens; new authors enriched AFTER commit, not inside it
- [x] 6. Cross-source agreement (agree/conflict/unverified) in EnrichPreviewService,
      rendered in both enrich panels; description/image left uncompared
- [x] 7. `catalogue:import` preserves `api:` rows across its drop/recreate
- [x] 8. `catalogue:prune-api-drafts` + nightly schedule (replaces staging:prune-images)
- [x] 9. Retired the pending_books flow: controller, 3 views, 7 routes, 2 sidebar
      links, PendingBook, PruneStagingImages, staging files; BookIngestionService
      trimmed to the read-only lookup (resolveSources* + shared helpers kept)
- [x] 10. `books:ingest` CSV repointed at `api:` rows; real --dry-run now
- [x] 11. `pending:convert-to-catalogue` (idempotent, --dry-run); migration runs it
      before dropping the table
- [x] 12. Deployed; converted 19 rows locally and 5 on the VPS
- [ ] 13. VPS: run the gated `migrate --force` to drop the now-unused pending_books
      table (data already converted; backup at /root/backups/pending_books_*.sql)

## Review (2026-11-19)
Commits ec473de (feature + improvements) and e6b6140 (retirement). Verified:
both import screens render with no dead links; enrich resolves through the
trimmed service with agreement tags; approving a converted row creates the book
with real cover variants (400x622/150x233), review row, categories and author;
author binding reuses an existing author and correctly ignores a stale binding;
old /admin/books/ingest and /books/pending now 404; homepage 200; no new errors
in the VPS log. Catalogue on the VPS: 153,538 -> 153,543.

Two data-quality bugs found and fixed while testing:
- Open Library returns 200 + a 43-byte 1x1 GIF when it has no cover, so books
  silently got a 1-pixel cover. Remote covers under 50px a side are now treated
  as no cover, which makes the admin warning fire. 8 of 17 converted rows point
  at Open Library covers.
- Placeholder ISBNs ("0000000000") pass both ISBN checksums; since the ISBN is
  the row's dedup_key, unrelated books would have merged. Now rejected.

Still open: the A/B fork. catalogue-import and reader-import share ~400
byte-identical JS lines with no shared partial, and every change here widens
that gap. Extracting a shared partial + one JS asset is the next cleanup.

---

# Products CSV export (2026-11-20)

Goal: get the catalogue out of the app in a form another platform can import,
with cover images that actually render there.

- [x] 1. `BookExportService` streaming the file the way the orders report does
      (closure for `response()->stream()`, UTF-8 BOM, `chunkById(500)`).
- [x] 2. Narrow `select()` so `cost_price` / `profit_margin` / `api_*` /
      `rewrite_*` are never hydrated into a payload that leaves the server.
- [x] 3. Absolute cover URL on our own domain, guarded against the empty-image
      case rather than taken from `$book->thumbnail` / `$book->large_image`.
- [x] 4. `productFilterQuery()` shared by `showproduct()` and the export, so the
      file always matches the rows on screen.
- [x] 5. `admin.products.export` route above the `/products/{id}` wildcard.
- [x] 6. "تصدير CSV" button beside the reset button in the products toolbar.

## Review (2026-11-20)

Commit aa368d3. Deployed; `route:cache` clean, homepage 200 in 0.22s, only one
error in today's VPS log and it is from my own `tinker` call, not the export.

Verified on production (1,122 records, 20 columns, every row the same width):
all 1,122 image URLs and product URLs are `https://alfokara.com/...`; five cover
URLs taken straight out of the file were fetched **with a foreign `Referer`** and
all returned 200 with the right content type; two product URLs returned 200 HTML;
no internal column in the header; final-price arithmetic correct on every row.
Streaming 1,122 rows peaks at 34 MB.

Locally: 633 records; every image URL resolves to a file that exists on disk;
unauthenticated request 302s to login, admin request 200s with the right
`Content-Disposition`; the refactor of `showproduct()` was proven behaviourally
identical across 10 filter cases, including `search="0"` and a nonexistent
category id; export row count matches the listing count on all 7 filter cases.

Two things worth knowing, not defects:
- Covers are mostly WebP. Google Merchant accepts WebP; Facebook/Instagram
  catalogues historically do not. A JPEG variant pipeline would be a separate job.
- The exported cover is the 400px main image, because that is the only variant
  that reliably exists (VPS: 1,135 main, 642 thumbs, **53** large).
  `php artisan books:reprocess-covers --from-base` backfills the 800px variant,
  after which the image column could switch to `$book->large_image`.

Deliberately not done: CSV formula-injection escaping. Zero cells in either
environment start with `=`/`+`/`-`/`@`, and prefixing an apostrophe would corrupt
titles for the receiving platform. Worth revisiting only if the file is ever
handed to an untrusted recipient.

---

# Series picker in the import review modals (2026-11-23)

The review modal had no series field, so every volume had to be approved and
then edited from the products page just to set its series. `volume_number`
orders a series on the series page, in the "other volumes" list on a book page,
and in the bundle builder — production already has 73 series and 313 books in
one, so the main import path was silently dropping a feature in heavy use.

- [x] 1. `BookImportService::create()` passes `series_id` / `volume_number`
      into `Book::create()`. They were missing from that whitelist, so the
      columns would have been dropped even if the browser sent them. One edit,
      both screens.
- [x] 2. Both `approve()` actions validate them, with `max:65535` on the volume
      because the column is an unsigned smallint.
- [x] 3. Series dropdown + رقم الجزء input in both modals, copying
      `product.blade.php:321-339`. No inline creation (a typo would create a
      duplicate series); a link to إدارة السلاسل instead, `target="_blank"`
      because the modal is an unsaved working copy.
- [x] 4. The volume input stays disabled until a series is picked — a volume
      with no series is only read by series-scoped queries, so it is dead data.
      This is one deliberate improvement over the product modal.

## Review (2026-11-23)

Commit 4b8d5fa, 83 insertions and no deletions across 5 files. Deployed;
`route:cache` clean, homepage 200 in 0.17s, no root-owned files under storage/.

Verified end-to-end locally on **both** screens: a series + volume 3 survives
approve, `isPartOfSeries()` is true, the relation resolves and the book appears
in `Series->books()`; approving without a series still stores nulls and leaves
cover, author, categories and the review row untouched; and an out-of-range
volume, a volume of 0, a non-integer volume and an unknown `series_id` each
return **422 and create nothing**. Also confirmed `BookObserver` busts
`arabic_series_home` / `english_series_home`, so a book approved into a series
reaches the homepage carousels with no manual cache clear. All test rows were
removed afterwards (books back to 633, staging back to 493 pending).

On production both screens render all 73 series, and the two invalid-input
probes return 422 with the book count unchanged at 1,131.

Noted, not changed: `StoreBookProductRequest:42` and `UpdateBookRequest:44`
validate `volume_number` with `min:1` and **no max**, so the products page can
still turn an oversized volume into a 500. Same class of bug as the one fixed
here.

Unrelated finding from the VPS log while verifying: **405 logged errors from
spam bots posting a long promo payload into the registration `name` field**
(SQLSTATE 22001, `user.name` too long), on and off since 2026-11-15 and 232 of
them today. Registration has no length rule on `name`, so a bot gets a 500
instead of a 422 and the log fills up. Worth a separate fix.

Still open: the A/B fork. Everything above had to be written twice because
`catalogue-import` and `reader-import` share ~400 byte-identical JS lines with
no shared partial.

---

# Registration spam bots (2026-11-23)

## Correction to the note above

The entry under the series work said "Registration has no length rule on
`name`". Wrong. It had `max:255`. The real cause is
`AppServiceProvider.php:37` -> `Schema::defaultStringLength(191)`, so the column
is `varchar(191)`: a 192-255 char name passed validation and then threw
SQLSTATE 22001 on INSERT. `AuthController` caught it and logged
"Error in user creation" — all 405 of those entries. **Verify column widths by
measuring the live table, never by reading the migration.**

Worse, those log lines were only the *failures*. Payloads short enough to fit
191 chars succeeded, so **two spam accounts were live in production** (#24 from
April, #72 from 22 Nov; promo links as display names, disposable email domains,
zero orders).

Attack shape: 4,452 POSTs to `/adduser`, 314 IPs (two bulk addresses = 88%, the
rest mostly Tor exits), one spoofed UA, empty Referer, 1,862 on 22 Nov and 2,584
on 23 Nov. `throttle:3,1` was already on the route and the bots paced just under
it — so **no IP-based limit could have fixed this**, which is why every measure
added is source-agnostic.

- [x] 1. `AntiSpam` middleware: honeypot + minimum submit time (encrypted
      timestamp, unforgeable without `APP_KEY`). On `/adduser` and `/contact`.
      No per-request logging — a `spam_blocked:<date>` cache counter instead,
      since filling the log is half the problem and production is `LOG_LEVEL=error`.
- [x] 2. `not_regex` on the display name rejecting `<`, `>`, `://`, `http`, `www.`.
- [x] 3. Turnstile, shipped dormant; opt-in per route (`antispam:turnstile`) so a
      form without the widget cannot break when keys appear. Fails open on a
      Cloudflare timeout **or 5xx**. CSP updated.
- [x] 4. Lengths aligned to the real columns on register, contact and checkout
      (`city` had no rule at all; `password` capped at bcrypt's real 72 bytes).
- [x] 5. `JSON_HEX_TAG` in `json-ld.blade.php` — `SchemaBuilder:46` puts a review
      author's name inside a `<script>` block.
- [x] 6. Both spam accounts soft-deleted on production (recoverable).

## Review (2026-11-23)

Commit 545dc54. Deployed; homepage 200 in 0.27s, login2/contact 200, all six
JSON-LD blocks still parse with zero raw angle brackets.

The calibration test that matters: the name rule was run against **all 66 real
production users** and rejects **exactly the two spam accounts, nobody else**.

Local, through the real HTTP kernel: a normal signup still works, including a
190-char Arabic name, apostrophes and accents; honeypot, instant submits, and
missing / forged / stale / future-dated tokens are all blocked; a 4-second-old
token passes; all four captured spam payloads 422 on `name`; the length cases
422 with **zero** new "Error in user creation" entries. Turnstile verified both
dormant and active using Cloudflare's documented always-pass / always-fail test
secrets, and the payload sent to Cloudflare was captured to confirm it carries
only `secret`, `response`, `remoteip` — never the visitor's name or email.

On production after deploy: a real signup succeeds inside a rolled-back
transaction (no row left behind), every bot payload is blocked, and in the first
minutes **1 live bot attempt arrived, the counter incremented, and 0 new
"Error in user creation" lines appeared** (previously ~200/day).

Two test-harness traps worth remembering, neither a product bug:
- The session Store and auth guards are container singletons, so they survive
  between `kernel->handle()` calls in one process. A successful signup calls
  `Auth::login()`, and `ThrottleRequests` keys on the user id when one exists —
  so the harness tripped `throttle:3,1` on itself. Flush the session and
  `forgetGuards()` between requests.
- `Http::fake()` with a closure that throws **segfaults** this PHP build. To test
  the fail-open path, swap the `Http\Client\Factory` container binding for a
  stub instead.

## Reported, not fixed — public write-surface audit (23 endpoints)

- **`POST /checkout/apply-coupon`** — no validation, no throttle, and a distinct
  message per failure reason: an unthrottled oracle for enumerating coupon codes
  and their minimum-order amounts. The most exploitable item found.
- **`POST /login`** still exists beside `/userlogin` and keys its 5/min limit on
  *email + IP*, so one address can spray passwords past `/userlogin`'s per-IP cap.
- **`POST /checkout/store-cart`** — unvalidated, unthrottled, writes arbitrary
  JSON to a new session file per request. The value is never read anywhere: dead
  code and pure disk-growth abuse.
- `/remove-from-cart` and `/wishlist-session/*` with a non-numeric id raise
  `TypeError`, which `catch (\Exception)` misses -> uncaught 500.
- `CheckoutController:68-71` flashes the raw exception message to the visitor, so
  a `QueryException` would print SQL and bound values regardless of `APP_DEBUG`.
- `storage/logs/laravel.log` is **24 MB** and `LOG_CHANNEL=stack` never rotates
  it. `LOG_CHANNEL=daily` is a one-line `.env` change.
- ~17 admin form requests carry the same `max:255`-vs-`varchar(191)` mismatch.


---

## 2026-11-23 — Turnstile switched on

Keys were added to the production `.env` by the owner. Two things followed.

**Config cache.** Production runs cached, so the keys were inert until
`config:cache` was rebuilt — the widget stayed absent for ~5h after the keys
landed. Re-cached as part of the deploy below.

**Explicit rendering (commit 70e6201).** `#register-form` is `display:none`
until its tab is clicked, so the implicit `cf-turnstile` class would have built
the widget inside a hidden container — undocumented by Cloudflare. A widget that
produced no token would make `AntiSpam` reject *every genuine signup*, a failure
the honeypot and timing checks would not have caused. Now rendered via
`turnstile.render()` on first reveal, deferred one tick because `login.js` is
itself deferred and its tab handler runs after the inline listener.

### Verified
* Markup (local, against Cloudflare's test site key): widget container present,
  no implicit class, explicit render params, sitekey injected, secret absent.
* Server-side against the real siteverify endpoint with Cloudflare's test
  secrets: valid token → signup succeeds and the user row is created;
  invalid token, missing token, and filled honeypot → all blocked, nothing
  written. Rolled back; local user count unchanged.
* Production secret validated without a browser: siteverify returned
  `invalid-input-response`, not `invalid-input-secret` — the key is correct.
* Live page carries the widget; users 64 visible / 66 with trashed.
* Last `Error in user creation`: 16:54:34, before the 16:55 deploy. Zero since,
  against a `spam_blocked` count of 610 for the day.

### Not verifiable from here
The widget rendering in a real browser (no browser tool in session). Owner to
confirm: open /login2 → "إنشاء حساب" tab → widget appears → signup completes.
