# Reader-import review tool (admin-only)

Import 494 scraped books (reader_DB/exports/*.json) into `books` after one-by-one
admin review. Source data is messy (89% null authors, junk prices, category labels
that are really authors/carousels/merch). Confirmed decisions:
- price => force **40** (editable per item).
- author-name "categories" (أغاثا كريستي، أسامة مسلم، دوستويفسكي…) => fill the AUTHOR field, store the Arabic form.
- merch (mugs, ADD ONES) => Accessories category.
- images => copy + convert to webp via ImageService::processLocalFile.
- carousel/promo/series labels (وصل حديثا، الأكثر مبيعا، FOR-SALE، عشوائيات، سلاسل، منتجاتنا، …) => ignored for categorization.

## Mapping (App\Services\ReaderImportMapper)
- CATEGORY_MAP source=>DB id: كتب انجليزية=>82, تنمية ذاتية=>3, كتب دينية=>2,
  كتب فرنسية=>79, رعب و فانتازيا=>99, mugs=>80, ADD ONES=>80.
- AUTHOR_LABELS: أغاثا كريستي, أسامة مسلم, دوستويفسكي, خولة حميدي, مصطفى محمود,
  حسن أوريد, مؤلفات إيمان نضيفي=>إيمان نضيفي, تحقيقات نوح الألفي=>نوح الألفي.
- IGNORE: وصل حديثا, وصل حديثا 2, الأكثر مبيعا, FOR-SALE, عشوائيات, سلاسل,
  منتجاتنا, مملكة البلاغة, قواعد جارتين, ثلاثية ردني إليك.
- Fallback category by language: arabic=>83 (كتب عربية), english=>82 (كتب إنجليزية), french=>79.
- normalizeLanguage: Arabic=>arabic, English=>english (lowercase).
- suggestAuthor: data author -> else first AUTHOR_LABEL among categories -> else regex "للكاتب(ة)? X" from description.

## Tasks
- [ ] migration: reader_staging_books (external_id unique, name, author, language,
      price, description, local_image, source_categories json, suggested_category_id,
      status [pending|imported|skipped], book_id, reviewed_at, timestamps).
- [ ] Model ReaderStagingBook.
- [ ] ReaderImportMapper service (maps + suggestCategory/suggestAuthor/normalizeLanguage).
- [ ] Command `reader:stage` (--reset, --file=): load JSON -> staging rows with
      suggestions; skip status=rejected; flag missing local images.
- [ ] Admin\ReaderImportController: index (view), list (paginated json + counts),
      image (stream reader_DB file), approve (create Book + image + author + category,
      dedup by title), skip, unskip.
- [ ] routes/web.php (admin group): reader-import.{index,list,image,approve,skip}.
- [ ] view admin/reader-import/index.blade.php: card grid, filters (pending/imported/
      skipped/all), counts+progress, per-card edit title/author/price/description +
      language dropdown + category dropdown(tree, presel suggestion), Approve/Skip.
- [ ] Verify: php -l, migrate, reader:stage seeds 493, approve creates a real book
      (image webp in public/images/books, author+category set), dedup works.

## Notes
- reader_DB/ stays OUT of git (source material + images). Admin-gated routes.
- Book create: set author_id + book_authors(author_type=primary) pivot; syncCategories
  / categories()->attach(is_primary); quantity=stock; status=active; slug auto (HasSlug).

## Review
DONE & verified. Staging table + ReaderStagingBook model + ReaderImportMapper (category/
author/language maps) + `reader:stage` command + ReaderImportController (index/list/image/
approve/skip/unskip) + admin/reader-import blade + admin routes (auth+isAdmin) + Sidebar link.
`reader:stage --reset` seeded 493 (1 rejected skipped, 3 missing images flagged). Suggestions:
author coverage 53→176 (labels + "للكاتب X" regex), all 493 categorized, mug→Accessories,
Agatha→author filled. End-to-end approve verified (rolled back): Book created with auto slug,
category_id+pivot primary, qty=stock, price=40, image converted to webp + thumb + large.
Book::create wrapped in withoutSyncingToSearch so import doesn't hard-depend on Meilisearch
(run `scout:import "App\Models\Book"` after a batch to index). reader_DB/ added to .gitignore.

UPDATE: admin can now edit quantity per card (editable الكمية field, defaults to scraped
stock, saved as book quantity on approve; validated required integer min 0). Category
dropdown now renders as a tree like the product filter: bold top-level parents + children
indented with ── (categoryOptions returns ordered {id,name,parent}). Verified ordering
([P] Accessories, [P] English Books, ── Adult …). NOT committed (WIP).

UPDATE 2 (detail modal): per-book "تحرير التفاصيل" modal added — ISBN, publisher, page_num,
replace cover (upload → ImageService::processLocalFile, or "جلب من API"), Google-Books enrich
(preview & pick fields via APIService → enrich-preview endpoint, no writes), SEO rewrite (new
shared App\Services\DescriptionRewriteService, extracted from books:rewrite-descriptions cmd),
and multi-category checkboxes + primary star (Book::syncCategories). Staging table gained isbn,
page_num, publisher, category_ids(json), primary_category_id, custom_image, description_rewritten,
original_description (migration 2026_06_29_000001). Card uses an in-memory STATE model; enrich/
rewrite endpoints accept the admin's current (unsaved) modal values. approve() persists all new
fields; rewritten books get original_description + rewrite_status='rewritten' via forceFill
(not in Book::$fillable) so the nightly cron skips them. VERIFIED end-to-end (rolled back):
multi-cat (primary=2, 2 cats, pivot is_primary), isbn/page_num/publisher/qty, webp image,
rewrite branch. Routes + list payload + blade compile OK. NOT committed (WIP).
