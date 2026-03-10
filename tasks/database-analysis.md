# Database Schema Analysis Report

**Project:** Library Fokara (Laravel Bookstore)
**Date:** 2026-03-09
**Tables:** 38 (including legacy/framework tables)

---

## 1. Schema Overview

### Core Domain Tables (15)
| Table | Purpose | Rows (est.) | FKs | Indexes |
|-------|---------|-------------|-----|---------|
| `user` | Primary users | Low-Medium | 0 | email (unique) |
| `books` | Product catalog | Medium | 2 (author_id, publishing_house_id) | 8+ |
| `categories` | Hierarchical categories | Low | 0 | - |
| `authors` | Book authors | Low | 0 | - |
| `publishing_houses` | Publishers | Low | 0 | - |
| `book_authors` | Pivot: books-authors | Medium | 2 (book_id, author_id) | author_type |
| `orders` | Customer orders | High-growth | 0 | status, management_token |
| `order_details` | Order line items | High-growth | 0 | order_id, book_id |
| `checkout_details` | Shipping/payment info | High-growth | 0 | order_id |
| `cart` / `cart_items` | Shopping cart | Medium | 0 | cart_id, book_id |
| `coupons` | Discount codes | Low | 0 | code (unique) |
| `shipments` | Incoming stock | Low | 0 | - |
| `shipment_items` | Shipment line items | Low-Medium | 2 (author_id, publishing_house_id) | - |
| `return_requests` | Customer returns | Low-Medium | 0 | - |

### Social/Community Tables (9)
| Table | Purpose | FKs | Indexes |
|-------|---------|-----|---------|
| `reviews` | Book reviews | 1 (user_id) | book_id, user_id, status |
| `review_likes` | Review likes pivot | 0 | review_id, user_id (unique pair) |
| `quotes` | Book quotes | 0 | book_id, (user_id, created_at), is_approved |
| `quote_likes` | Quote likes pivot | 1 (user_id) | (quote_id, user_id) unique |
| `wishlists` | Wishlist pivot | 0 | (user_id, book_id) unique |
| `hidden_recommendations` | Hidden recs pivot | 0 | (user_id, book_id) unique |
| `follows` | Polymorphic follows | 0 | (user_id, followable_id, followable_type) unique |
| `reading_shelves` | Reading status | 2 (user_id, book_id) | (user_id, status) |
| `reading_goals` | Yearly goals | 1 (user_id) | - |

### System Tables (7)
`contact_messages`, `stock_notifications`, `user_notifications`, `order_status_history`, `inventory_logs`, `system_settings`, `api_caches`

### Framework Tables (7)
`users` (legacy), `password_resets`, `password_reset_tokens`, `personal_access_tokens`, `failed_jobs`, `jobs`, `migrations`

---

## 2. Detected Issues

### CRITICAL: Missing Foreign Keys (16 tables)

**Only 10 FK constraints exist in the entire database.** The following tables have columns that SHOULD have foreign keys but DON'T:

| Table | Column | Should Reference | Risk |
|-------|--------|-----------------|------|
| `orders` | `user_id` | `user.id` | Orphaned orders if user deleted |
| `order_details` | `order_id` | `orders.id` | Orphaned line items |
| `order_details` | `book_id` | `books.id` | References to deleted books |
| `cart` | `user_id` | `user.id` | Orphaned carts |
| `cart_items` | `cart_id` | `cart.id` | Orphaned cart items |
| `cart_items` | `book_id` | `books.id` | References to deleted books |
| `checkout_details` | `order_id` | `orders.id` | Orphaned checkout data |
| `wishlists` | `user_id` | `user.id` | Orphaned wishlists |
| `wishlists` | `book_id` | `books.id` | Orphaned wishlists |
| `hidden_recommendations` | `user_id` | `user.id` | Orphaned records |
| `hidden_recommendations` | `book_id` | `books.id` | Orphaned records |
| `return_requests` | `order_id` | `orders.id` | Orphaned returns |
| `return_requests` | `user_id` | `user.id` | Orphaned returns |
| `review_likes` | `review_id` | `reviews.id` | Orphaned likes |
| `review_likes` | `user_id` | `user.id` | Orphaned likes |
| `quotes` | `book_id` | `books.id` | Orphaned quotes |
| `quotes` | `user_id` | `user.id` | Orphaned quotes |
| `categories` | `parent_id` | `categories.id` | Broken hierarchy |
| `stock_notifications` | `book_id` | `books.id` | Orphaned notifications |
| `stock_notifications` | `user_id` | `user.id` | Orphaned notifications |
| `user_notifications` | `user_id` | `user.id` | Orphaned notifications |
| `follows` | `user_id` | `user.id` | Orphaned follows |
| `order_status_history` | `order_id` | `orders.id` | Orphaned history |

**Root Cause:** Many migrations reference `users` (Laravel's default plural convention) but the actual table is named `user` (singular). The FK creation likely failed silently during migration, leaving the columns without constraints.

**Tables WITH working FKs (only these):**
- `books` → authors, publishing_houses
- `book_authors` → books, authors
- `reading_shelves` → user, books
- `reading_goals` → user
- `reviews` → user
- `quote_likes` → user
- `shipment_items` → authors, publishing_houses

---

### HIGH: Missing Migration Files (7 tables)

These tables exist in the database but have NO creation migration:

| Table | Referenced By |
|-------|--------------|
| `authors` | books.author_id, book_authors.author_id, shipment_items.author_id |
| `publishing_houses` | books.publishing_house_id, shipment_items.publishing_house_id |
| `book_authors` | Author/Book many-to-many pivot |
| `shipments` | shipment_items.shipment_id |
| `shipment_items` | Only has update migration |
| `inventory_logs` | InventoryLog model |
| `system_settings` | SystemSetting model |

These were likely created outside the migration system (manually via SQL or a squashed migration that was deleted). This means `php artisan migrate:fresh` would fail.

---

### HIGH: Dual User Tables

Two user tables exist:
- `users` — Laravel's default (from `2014_10_12_000000_create_users_table.php`)
- `user` — The actual table used by the application

The `users` table appears completely unused. The `UserModel` class explicitly uses `$table = 'user'` (singular). This causes:
1. Confusion about which table is canonical
2. Migration FKs that reference `users` fail because the real data is in `user`

---

### MEDIUM: Naming Convention Violations

| Issue | Examples |
|-------|---------|
| **Singular table name** | `user`, `cart` should be `users`, `carts` (Laravel convention) |
| **PascalCase/Mixed columns** | `books.Page_Num`, `books.Langue`, `books.Publishing_House`, `books.ISBN`, `books.Quantity` |
| **Inconsistent prefix** | `categorie_image`, `categorie_icon` (French spelling vs English `category`) |
| **Model class naming** | `Book_Review` instead of `Review`, `UserModel` instead of `User` |
| **Non-standard pivot name** | `hidden_recommendations` (should be `book_user_hidden` or similar), `wishlists` (should be `book_user` or `wishlist_items`) |
| **Inconsistent FK references** | Some migrations use `->constrained()`, others use `->references('id')->on('table')` |

---

### MEDIUM: Normalization Issues

#### 2a. Denormalized `books.author` (string) vs `books.author_id` (FK)
The `books` table has BOTH:
- `author` — a raw string column storing the author name
- `author_id` — a FK to the `authors` table

This is a transitional artifact. The string column creates data inconsistency risk (name stored in two places).

#### 2b. `books.Publishing_House` (string) vs `books.publishing_house_id` (FK)
Same problem — both a string column and a FK exist for the publishing house.

#### 2c. `checkout_details.cart_items` (JSON blob)
Stores cart items as a JSON column alongside the normalized `order_details` table. This duplicates data and can drift out of sync.

#### 2d. Denormalized `likes_count` columns
Both `reviews.likes_count` and `quotes.likes_count` store cached counts that must be manually synchronized with the `review_likes` / `quote_likes` tables. Risk of count drift.

#### 2e. `checkout_details` duplicates `orders` data
`checkout_details.status` duplicates `orders.status`. Two status fields for the same order can go out of sync.

---

### MEDIUM: Missing SoftDeletes

**Zero tables use SoftDeletes.** For a bookstore application, these tables should strongly consider soft deletes:

| Table | Why |
|-------|-----|
| `books` | Deleting a book orphans order_details, reviews, quotes, wishlists, cart_items |
| `orders` | Legal/accounting requirement to retain order history |
| `user` | Deleting a user cascades to orders, reviews, quotes, cart |
| `authors` | Books reference authors; deleting breaks book pages |
| `reviews` | User-generated content often needs moderation, not deletion |

Without soft deletes AND without proper FK cascades, deleting a record leaves orphaned data everywhere.

---

## 3. Suggested Improvements

### Priority 1: Fix Foreign Key Constraints

Create a migration to add all missing FKs. Choose appropriate cascade rules:

```php
// Suggested cascade rules:
Schema::table('orders', function (Blueprint $table) {
    $table->foreign('user_id')->references('id')->on('user')->onDelete('cascade');
});

Schema::table('order_details', function (Blueprint $table) {
    $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
    $table->foreign('book_id')->references('id')->on('books')->onDelete('restrict'); // Don't delete books with orders
});

Schema::table('checkout_details', function (Blueprint $table) {
    $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
});

Schema::table('cart', function (Blueprint $table) {
    $table->foreign('user_id')->references('id')->on('user')->onDelete('cascade');
});

Schema::table('cart_items', function (Blueprint $table) {
    $table->foreign('cart_id')->references('id')->on('cart')->onDelete('cascade');
    $table->foreign('book_id')->references('id')->on('books')->onDelete('cascade');
});

// Pivot tables: cascade on both sides
// wishlists, hidden_recommendations, review_likes, follows: cascade on delete

// return_requests: cascade order, set null user (guest support)
// categories.parent_id: set null on delete (don't cascade-delete children)
// quotes: cascade user, restrict book (keep quotes for existing books)
```

**Before adding FKs:** Run an orphan check to find any existing orphaned records that would block FK creation.

### Priority 2: Create Missing Migrations

Write creation migrations for the 7 tables that lack them, matching the actual database schema. This ensures `migrate:fresh` works.

### Priority 3: Resolve Dual User Table

Options (pick one):
- **Option A (recommended):** Rename `user` to `users` via migration, update `UserModel.$table`, and fix all FK references. This follows Laravel convention.
- **Option B:** Drop the empty `users` table, keep `user` as-is. Unconventional but requires fewer changes.

### Priority 4: Clean Up Denormalized Author/Publisher Strings

Once `author_id` and `publishing_house_id` are reliably populated:
1. Verify all books have valid `author_id` / `publishing_house_id` values
2. Drop the `books.author` string column
3. Drop the `books.Publishing_House` string column
4. Use `$book->primaryAuthor->name` and `$book->publishingHouse->name` everywhere

### Priority 5: Add SoftDeletes to Critical Tables

Add `SoftDeletes` trait + `deleted_at` column to: `books`, `orders`, `user`, `authors`, `publishing_houses`.

---

## 4. Performance Optimizations

### Missing Indexes

| Table | Column(s) | Query Pattern | Priority |
|-------|-----------|---------------|----------|
| `orders` | `created_at` | Monthly/daily sales reports, date range filters | HIGH |
| `orders` | `(user_id, status)` | "My orders" page filtered by status | HIGH |
| `order_details` | `(book_id, order_id)` | "Also bought" recommendations, sales reports | HIGH |
| `checkout_details` | `(order_id)` | FK verified present but confirm composite with city for reports | MEDIUM |
| `categories` | `parent_id` | Hierarchical queries | MEDIUM |
| `return_requests` | `(order_id, status)` | Admin return management | MEDIUM |
| `stock_notifications` | `(book_id, notified_at)` | "Notify when in stock" processing | LOW |
| `contact_messages` | `is_read` | Unread message count | LOW |
| `coupons` | `(is_active, expires_at)` | Coupon validation | LOW |

### Existing Index Review

The `books` table already has good coverage (8+ indexes). However:
- The `(category_id, publishing_house_id)` composite may be redundant if they're not queried together
- `books.status` and `books.type` indexes are good for scoped queries

### Query Optimization Notes

1. **"Also bought" feature** (`Bookcontroller.php`) runs two separate queries on `order_details` — consider a single query with a subquery or caching the result (currently cached 30min which is good)

2. **Admin reports** (`AdminReportsController.php`) uses heavy `DB::raw` aggregations with `MONTH(created_at)` — these can't use standard indexes. Consider:
   - Adding a `year_month` virtual/generated column with an index
   - Or use date range WHERE clauses instead of MONTH() extraction

3. **Popular books** already cached (30 min TTL) — good practice

4. **Wishlist queries** use `DB::table('wishlists')` raw queries instead of the Eloquent relationship — convert to `$user->wishlist()` for consistency

---

## 5. Optional Architectural Improvements

### 5a. Order-Checkout Consolidation

`checkout_details` and `orders` have overlapping concerns. Consider merging shipping/billing columns into `orders` directly:
- Move `full_name`, `phone`, `address`, `city`, `notes` into `orders`
- Keep `order_details` for line items
- Remove `checkout_details.status` (duplicate of `orders.status`)
- Remove `checkout_details.cart_items` JSON (already normalized in `order_details`)

This eliminates the JOIN needed for every order display and the status sync problem.

### 5b. Coupon-Order Relationship

Currently no link between which coupon was used on which order. Consider adding:
- `orders.coupon_id` (FK to coupons) — tracks which coupon was applied
- This enables reporting: "revenue impact per coupon", "coupon abuse detection"

### 5c. Book Price History

`books.price` is a single value with no history. When prices change, historical order data shows the old price in `order_details.price` but you can't query "what was the price on date X." Consider:
- An `price_history` table if price tracking is needed
- Or accept that `order_details.price` captures the snapshot at purchase time (probably sufficient)

### 5d. `discount` Column Type

`books.discount` is `decimal(8)` — missing precision digits. Should be `decimal(8,2)` to match `price`. If it's a percentage, consider `decimal(5,2)` with a max constraint.

### 5e. Reading Shelf + Review Integration

A user marking a book as "read" on their shelf could auto-prompt for a review. The `reading_shelves.status = 'read'` and `reviews` tables could be linked:
- Add `reading_shelf_id` to reviews (optional, just for UX tracking)

### 5f. Full-Text Search

The `Book` model uses Laravel Scout (Searchable trait). Verify the search driver (Algolia/Meilisearch/database) is properly configured for production. If using database driver, add `FULLTEXT` index on `books.title` and `books.description`.

---

## Summary of Action Items

| # | Action | Priority | Effort |
|---|--------|----------|--------|
| 1 | Add missing FK constraints (23 missing FKs across 16 tables) | CRITICAL | Medium |
| 2 | Run orphan data check before FK creation | CRITICAL | Low |
| 3 | Create migration files for 7 tables missing them | HIGH | Medium |
| 4 | Resolve `user` vs `users` table naming | HIGH | Medium |
| 5 | Add missing performance indexes (9 identified) | HIGH | Low |
| 6 | Add SoftDeletes to books, orders, user, authors | MEDIUM | Low |
| 7 | Remove denormalized `books.author` / `books.Publishing_House` strings | MEDIUM | Medium |
| 8 | Fix column naming (Page_Num, Langue, Publishing_House, Quantity) | MEDIUM | Medium |
| 9 | Remove `checkout_details.cart_items` JSON duplication | LOW | Low |
| 10 | Remove `checkout_details.status` duplication | LOW | Low |
| 11 | Add `orders.coupon_id` for coupon tracking | LOW | Low |
| 12 | Fix `books.discount` decimal precision | LOW | Low |
