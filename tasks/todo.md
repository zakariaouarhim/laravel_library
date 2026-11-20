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
