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
