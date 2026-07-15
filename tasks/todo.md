# booksondemand.ma scrape → catalogue_reference (second reference source)

Plan approved. Full plan: C:\Users\zakar\.claude\plans\magical-prancing-owl.md
Key decisions: reuse `catalogue_reference` with `dedup_key` prefix `bod:{shopify_id}`
(NO schema change — `catalogue:import` drops/recreates the table, a column would
vanish); scraper idempotent via upsert; price stored as "149,00 DH" (comma —
parsePrice strips dots); vendor = author (denylist store names); skip bundle
products; category = smallest non-generic collection; no ISBN available from
this store, enrichment matches by title_normalized.

- [x] `CatalogueLookupService::normalize()` → public static (scraper reuses it)
- [x] New command `bod:scrape` (app/Console/Commands/ScrapeBooksOnDemand.php)
- [x] `CatalogueImportController::list()`: `source` param → dedup_key prefix filter
- [x] Blade: Source dropdown (auto-relax completeness filter when BOD)
- [x] ImportCatalogueReference docblock: re-import wipes bod rows → re-run bod:scrape
- [x] Verify: dry-run, limited scrape, idempotent re-run
- [x] Verify: dashboard filter, enrich-preview title match, full approve cycle
- [x] Full local scrape — DONE locally; VPS deploy still pending user sign-off

## Review
DONE & verified locally (2026-07-15). Full local scrape: **2,268 books** in
catalogue_reference (dedup_key `bod:…`), 600 bundle products skipped.

Bonus found during verification: ~90% of RECENT products' cover filenames are
the ISBN-13 → extracted with checksum validation (622 rows have isbn; older
products use random filenames, nothing more to extract). ISBN group also used
as language fallback (978-0/1→Anglais, 978-2→Français).

Data quality: 2,167 with category (clean genres after denylisting promo
collections; 101 null = promo-only products), 2,260 with description
(sanitized; some source descriptions contained pasted ChatGPT page HTML),
2,248 with author (Shopify `vendor` = author), 855 at completeness ≥7 (UI
auto-relaxes the quality filter when Source=BooksOnDemand).

Verified end-to-end: idempotent re-run (count stable), lookup() matches bod
rows by ISBN and by title_normalized, list() source filter (bod=2268 only /
almouggar excludes them), blade compiles, full approve cycle via
BookImportService (book created, Shopify cover downloaded → 38KB webp,
test book + author cleaned up after).

Local env fix that was needed: WAMP PHP had no CA bundle (cURL error 60) —
installed cacert.pem + set curl.cainfo/openssl.cafile in php.ini and
phpForApache.ini (backups *.bak-claude).

NOT yet committed/deployed. Deploy = 5 code files (ScrapeBooksOnDemand.php,
CatalogueLookupService.php, CatalogueImportController.php,
admin/catalogue-import/index.blade.php, ImportCatalogueReference.php), no
migration, then run `bod:scrape` on the VPS as www-data.
