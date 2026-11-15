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
