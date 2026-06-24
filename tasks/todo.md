# Offer: series/bundle as units (count by book count)

## Decisions (confirmed)
- Series/bundle shown to customer as ONE unit card; picking it is all-or-nothing and
  counts by its book count (3-book series = 3 toward N).
- Reaching N: total selected book count must be >= N (may exceed). Price stays fixed.

## Design
- Offer gains `series_ids` + `bundle_ids` (JSON). Admin series/bundle tabs now add UNITS
  (not loose member books). Categories/title/price-rule still add loose books.
- Loose display set = (offer_book ∪ price-rule) − excluded − unit-member-books.
- eligibleBookIds (cart validation) = loose ids ∪ unit-member-book-ids.
- Customer page: unit cards (count badge) above the paginated loose grid. Selection is a
  Set of book ids; unit card toggles ALL its member ids. Counter = book count. Add enabled
  at >= N. Submit sends the expanded id set (units already expanded to members).
- Cart addOfferGroup: accept count >= N (was == N); distribute fixed price across all.

## Tasks
- [ ] migration: offers.series_ids, offers.bundle_ids (json nullable).
- [ ] Offer: fillable+cast; resolveUnits() (label/image/book_ids/count per series+bundle),
      unitMemberBookIds(); eligibleBooksQuery() excludes unit members; eligibleBookIds() adds them.
- [ ] Store/UpdateOfferRequest: series_ids[]/bundle_ids[] arrays of ints.
- [ ] AdminOfferController: store/update save series_ids/bundle_ids; index passes series/bundles
      withCount (books/items) for unit labels.
- [ ] offer-form: series/bundle panel items carry data-count; a units chip area + hidden
      series_ids[]/bundle_ids[].
- [ ] offers.blade JS: unitPicker (chips + hidden inputs); cat-pick branches: series/bundle
      => addUnit (no checklist); edit prefill units; reset clears.
- [ ] OfferController show/books: pass $units; CartService addOfferGroup >= N + validate union.
- [ ] offers/show: render unit cards; selection by book count; add enabled >= N; submit expanded ids.
- [ ] Verify: lint, migrate, view:cache; unit counts toward N; cart group expands unit to books,
      price sums to fixed; eligibility accepts unit members.

## Review
Done. offers.series_ids/bundle_ids (json). Offer.resolveUnits() (memoized) builds unit
descriptors (label/image/book_ids/count); unitMemberBookIds(); eligibleBooksQuery() excludes
unit members (no double display); eligibleBookIds() = loose ∪ unit members (cart accepts them).
Admin: series/bundle tabs now ADD UNITS (cat-pick branches to unitPicker chips -> series_ids[]/
bundle_ids[]); counts shown via withCount. Customer: unit cards in #offerUnits (outside the
paginated #offerGrid so search/load-more don't wipe them); selection Set of book ids, unit card
toggles all members; counter = book count; add enabled at >= N (was == N); submit sends expanded
ids. CartService.addOfferGroup now requires >= N. VERIFIED: series(3) unit -> count 3, excluded
from loose grid, members eligible; unit+7 loose = 10 -> addOfferGroup ok, group=10 books,
alloc sum 350.00. migrate/lint/view ok. (offers WIP, not committed)

---

## TODO before public launch of Offers — REMOVE the admin-only gate
The whole offers feature is currently locked to admins (admin/super_admin) so it can be
tested on the VPS without customers seeing it. When ready to go public, undo BOTH:
1. routes/web.php — remove the two `Route::middleware(['auth','isAdmin'])->group(...)`
   wrappers around: (a) `offers.index` / `offer.show` / `offer.books`, and
   (b) `cart.offer.add` / `cart.offer.remove`. Leave the routes themselves intact.
2. resources/views/header.blade.php — remove the two
   `@if(auth()->check() && in_array(auth()->user()->role, ['admin','super_admin']))`
   wrappers around the "العروض" link (desktop nav + mobile nav).
Then `php artisan route:clear && php artisan view:clear`.
