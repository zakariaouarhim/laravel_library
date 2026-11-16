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
- [ ] 3.2 Cloudflare guide for user + verification
- [ ] Deploy + verify VPS (results page local: 14 queries, was ~80)

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
