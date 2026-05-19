<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RecommendationController;
use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\AdminClientController;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\BookController;
use App\Http\Controllers\AdminBookController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\ShipmentController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\WishlistController;
use App\Http\Controllers\AuthorController;
use App\Http\Controllers\SystemSettingsController;
use App\Http\Controllers\QuoteController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\PublisherController;
use App\Http\Controllers\ReturnRequestController;
use App\Http\Controllers\OrderManageController;
use App\Http\Controllers\AccessoryController;
use App\Http\Controllers\AdminContactController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\CouponController;
use App\Http\Controllers\AdminCategoryController;
use App\Http\Controllers\StockNotificationController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\FollowController;
use App\Http\Controllers\ReadingShelfController;
use App\Http\Controllers\AdminReportsController;
use App\Http\Controllers\AdminSeriesController;
use App\Http\Controllers\AdminBundleController;
use App\Http\Controllers\AdminPublishingHouseController;
use App\Http\Controllers\AdminQuoteController;

/*
| Custom route-model bindings — accept BOTH slug (preferred, for SEO) and
| numeric id (legacy callers still passing $model->id to route() helpers).
| This lets the slug migration roll out without breaking any existing call sites.
*/
Route::bind('book', fn ($v) => is_numeric($v)
    ? \App\Models\Book::findOrFail($v)
    : \App\Models\Book::where('slug', $v)->firstOrFail()
);
Route::bind('author', fn ($v) => is_numeric($v)
    ? \App\Models\Author::findOrFail($v)
    : \App\Models\Author::where('slug', $v)->firstOrFail()
);
Route::bind('category', fn ($v) => is_numeric($v)
    ? \App\Models\Category::findOrFail($v)
    : \App\Models\Category::where('slug', $v)->firstOrFail()
);
Route::bind('publisher', fn ($v) => is_numeric($v)
    ? \App\Models\PublishingHouse::findOrFail($v)
    : \App\Models\PublishingHouse::where('slug', $v)->firstOrFail()
);
Route::bind('series', fn ($v) => is_numeric($v)
    ? \App\Models\Series::findOrFail($v)
    : \App\Models\Series::where('slug', $v)->firstOrFail()
);

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// ==================== SUPER ADMIN ROUTES (auth + isSuperAdmin required) ====================

Route::middleware(['auth', 'isSuperAdmin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/users',                  [AdminUserController::class, 'usersIndex'])->name('users.index');
    Route::post('/users/{id}/promote',    [AdminUserController::class, 'promoteUser'])->name('users.promote');
    Route::post('/users/{id}/demote',     [AdminUserController::class, 'demoteUser'])->name('users.demote');
});

// ==================== ADMIN ROUTES (auth + isAdmin required) ====================

Route::middleware(['auth', 'isAdmin'])->prefix('admin')->name('admin.')->group(function () {

    // Dashboard
    Route::get('/Dashbord_Admin/dashboard', [AdminDashboardController::class, 'dashboard'])->name('Dashbord_Admin.dashboard');

    // Coupons
    Route::get('/coupons',                        [CouponController::class, 'index'])->name('coupons.index');
    Route::post('/coupons',                       [CouponController::class, 'store'])->name('coupons.store');
    Route::put('/coupons/{coupon}',               [CouponController::class, 'update'])->name('coupons.update');
    Route::delete('/coupons/{coupon}',            [CouponController::class, 'destroy'])->name('coupons.destroy');
    Route::post('/coupons/{coupon}/toggle',       [CouponController::class, 'toggleActive'])->name('coupons.toggle');

    // Client management
    Route::get('/client', [AdminClientController::class, 'index'])->name('client.index');
    Route::post('/client', [AdminClientController::class, 'store'])->name('client.store');
    Route::get('/client/{id}', [AdminClientController::class, 'showclient'])->name('client.show');
    Route::put('/client/{id}', [AdminClientController::class, 'update'])->name('client.update');
    Route::post('/client/{id}/reset-password', [AdminClientController::class, 'resetPasswordAdmin'])->name('client.reset-password');

    // Book ingestion (title+author → API enrichment → review queue → approved Book)
    Route::get('/books/ingest',                          [\App\Http\Controllers\AdminBookIngestionController::class, 'create'])->name('books.ingest.create');
    Route::post('/books/ingest',                         [\App\Http\Controllers\AdminBookIngestionController::class, 'store'])->name('books.ingest.store');
    Route::post('/books/ingest-isbn',                    [\App\Http\Controllers\AdminBookIngestionController::class, 'storeFromIsbn'])->name('books.ingest.isbn');
    Route::get('/books/pending',                         [\App\Http\Controllers\AdminBookIngestionController::class, 'index'])->name('books.pending.index');
    Route::get('/books/pending/{pendingBook}',           [\App\Http\Controllers\AdminBookIngestionController::class, 'show'])->name('books.pending.show');
    Route::post('/books/pending/{pendingBook}/approve',  [\App\Http\Controllers\AdminBookIngestionController::class, 'approve'])->name('books.pending.approve');
    Route::delete('/books/pending/{pendingBook}',        [\App\Http\Controllers\AdminBookIngestionController::class, 'discard'])->name('books.pending.discard');

    // Orders
    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{id}', [OrderController::class, 'show'])->name('orders.show');
    Route::put('/orders/{id}', [OrderController::class, 'update'])->name('orders.update');
    Route::get('/orders/{id}/edit', [OrderController::class, 'edit'])->name('orders.edit');
    Route::post('/orders/{id}', [OrderController::class, 'store'])->name('orders.store');

    // Return requests
    Route::get('/return-requests', [ReturnRequestController::class, 'adminIndex'])->name('return-requests.index');
    Route::get('/return-requests/{id}', [ReturnRequestController::class, 'adminShow'])->name('return-requests.show');
    Route::put('/return-requests/{id}', [ReturnRequestController::class, 'adminUpdate'])->name('return-requests.update');

    // Product management
    Route::get('/Dashbord_Admin/Product', [AdminBookController::class, 'showproduct'])->name('Dashbord_Admin.product');
    Route::post('/Dashbord_Admin/Product/add', [AdminBookController::class, 'addProduct'])->name('product.add');
    Route::get('/products', [AdminBookController::class, 'showproduct'])->name('products.index');

    // Product API (admin-only AJAX) — must be before /products/{id} wildcard
    Route::get('/products/api', [AdminBookController::class, 'getProductsApi'])->name('products.api');
    Route::get('/products/api/stats', [AdminBookController::class, 'getProductsApiStats'])->name('products.api.stats');
    Route::get('/products/api/{id}', [AdminBookController::class, 'getProductById'])->name('products.api.show');
    Route::put('/products/api/{id}', [AdminBookController::class, 'updateProduct'])->name('products.api.update');

    // Search helpers
    Route::get('/search-book', [AdminBookController::class, 'searchBook'])->name('search.book');
    Route::get('/search-authors', [AuthorController::class, 'search'])->name('search.authors');
    Route::get('/search-publishers', [PublisherController::class, 'search'])->name('search.publishers');

    // Shipment management
    Route::get('/Dashbord_Admin/Shipment_Management', [ShipmentController::class, 'index'])->name('Dashbord_Admin.Shipment_Management');
    Route::get('/shipments', [ShipmentController::class, 'searchShipment'])->name('shipments.search');
    Route::get('/shipments/next-reference', [ShipmentController::class, 'getNextShipmentReference'])->name('shipments.next-reference');
    Route::post('/shipments', [ShipmentController::class, 'store'])->name('shipments.store');
    Route::get('/shipments/{shipment}', [ShipmentController::class, 'show'])->name('shipments.show');
    Route::post('/shipments/{shipment}/process', [ShipmentController::class, 'processShipment'])->name('shipments.process');
    Route::get('/shipments/{shipment}/edit', [ShipmentController::class, 'editShipment'])->name('shipments.edit');
    Route::put('/shipments/{shipment}', [ShipmentController::class, 'updateShipment'])->name('shipments.update');
    Route::delete('/shipments/{shipment}/items/{item}', [ShipmentController::class, 'destroyItem'])->name('shipments.items.destroy');
    Route::patch('/shipments/{shipment}/status', [ShipmentController::class, 'updateStatus'])->name('shipments.status');
    Route::delete('/shipments/{shipment}', [ShipmentController::class, 'destroy'])->name('shipments.destroy');

    // Shipment enrichment (admin-only)
    Route::post('/shipments/{shipment}/bulk-enrich', [ShipmentController::class, 'bulkEnrich'])->name('shipments.bulk-enrich');
    Route::post('/shipment-items/{item}/enrich', [ShipmentController::class, 'enrichItem'])->name('shipment-items.enrich');

    // Book enrichment (admin-only)
    Route::post('/books/{book}/enrich', [AdminBookController::class, 'enrichBook'])->name('books.enrich');
    Route::get('/books/{book}/preview-enrich', [AdminBookController::class, 'previewEnrichment'])->name('books.preview-enrich');
    Route::post('/books/{book}/enrich-selected', [AdminBookController::class, 'applySelectedEnrichment'])->name('books.enrich-selected');
    Route::get('/books/pending-enrichment', [AdminBookController::class, 'getPendingEnrichment'])->name('books.pending-enrichment');
    Route::post('/books/bulk-enrich', [AdminBookController::class, 'bulkEnrichBooks'])->name('books.bulk-enrich');

    // Management system
    Route::get('/Dashbord_Admin/ManagementSystem', [ShipmentController::class, 'showmanagement'])->name('Dashbord_Admin.ManagementSystem');

    // Accessories
    Route::get('/Dashbord_Admin/Accessories', [AccessoryController::class, 'adminIndex'])->name('Dashbord_Admin.accessories');
    Route::post('/accessories', [AccessoryController::class, 'adminStore'])->name('accessories.store');
    Route::get('/accessories/{id}', [AccessoryController::class, 'adminShow'])->name('accessories.show');
    Route::put('/accessories/{id}', [AccessoryController::class, 'adminUpdate'])->name('accessories.update');
    Route::delete('/accessories/{id}', [AccessoryController::class, 'adminDestroy'])->name('accessories.destroy');

    // Authors management
    Route::get('/Dashbord_Admin/Authors', [AuthorController::class, 'index'])->name('Dashbord_Admin.authors');
    Route::get('/authors/api', [AuthorController::class, 'getAuthorsApi'])->name('authors.api');
    Route::get('/authors/check-duplicates', [AuthorController::class, 'checkDuplicates'])->name('authors.check-duplicates');
    Route::get('/authors/{id}', [AuthorController::class, 'show'])->name('authors.show');
    Route::put('/authors/{id}', [AuthorController::class, 'update'])->name('authors.update');
    Route::delete('/authors/{id}', [AuthorController::class, 'destroy'])->name('authors.destroy');
    Route::get('/authors/{id}/preview-enrich', [AuthorController::class, 'previewEnrichment'])->name('authors.preview-enrich');
    Route::post('/authors/{id}/apply-enrich', [AuthorController::class, 'applyEnrichment'])->name('authors.apply-enrich');

    // System settings (admin-only)
    Route::get('/settings', [SystemSettingsController::class, 'index'])->name('settings.index');
    Route::post('/settings', [SystemSettingsController::class, 'update'])->name('settings.update');

    // Reports
    Route::get('/reports', [AdminReportsController::class, 'index'])->name('reports.index');
    Route::get('/reports/export', [AdminReportsController::class, 'export'])->name('reports.export');

    // Categories management
    Route::get('/categories',                   [AdminCategoryController::class, 'index'])->name('categories.index');
    Route::post('/categories',                  [AdminCategoryController::class, 'store'])->name('categories.store');
    Route::put('/categories/{category}',        [AdminCategoryController::class, 'update'])->name('categories.update');
    Route::delete('/categories/{category}',     [AdminCategoryController::class, 'destroy'])->name('categories.destroy');

    // Series management
    Route::get('/series',                [AdminSeriesController::class, 'index'])->name('series.index');
    Route::post('/series',               [AdminSeriesController::class, 'store'])->name('series.store');
    Route::put('/series/{series}',       [AdminSeriesController::class, 'update'])->name('series.update');
    Route::delete('/series/{series}',    [AdminSeriesController::class, 'destroy'])->name('series.destroy');
    Route::get('/search-series',         [AdminSeriesController::class, 'search'])->name('search.series');

    // Bundles management
    Route::get('/bundles',                       [AdminBundleController::class, 'index'])->name('bundles.index');
    Route::get('/bundles/{bundle}',              [AdminBundleController::class, 'show'])->name('bundles.show');
    Route::post('/bundles',                      [AdminBundleController::class, 'store'])->name('bundles.store');
    Route::post('/bundles/{bundle}',             [AdminBundleController::class, 'update'])->name('bundles.update');
    Route::delete('/bundles/{bundle}',           [AdminBundleController::class, 'destroy'])->name('bundles.destroy');
    Route::get('/bundles-series/{series}/books', [AdminBundleController::class, 'seriesBooks'])->name('bundles.series-books');

    // Publishing houses management
    Route::get('/Dashbord_Admin/publishing-houses',    [AdminPublishingHouseController::class, 'index'])->name('publishing_houses.index');
    Route::post('/publishing-houses',                  [AdminPublishingHouseController::class, 'store'])->name('publishing_houses.store');
    Route::get('/publishing-houses/api/{id}',          [AdminPublishingHouseController::class, 'show'])->name('publishing_houses.show');
    Route::put('/publishing-houses/api/{id}',          [AdminPublishingHouseController::class, 'update'])->name('publishing_houses.update');
    Route::delete('/publishing-houses/{id}',           [AdminPublishingHouseController::class, 'destroy'])->name('publishing_houses.destroy');

    // Contact messages
    Route::get('/contact-messages', [AdminContactController::class, 'index'])->name('contact-messages.index');
    Route::get('/contact-messages/{id}', [AdminContactController::class, 'show'])->name('contact-messages.show');
    Route::patch('/contact-messages/{id}/toggle-read', [AdminContactController::class, 'toggleRead'])->name('contact-messages.toggle-read');
    Route::delete('/contact-messages/{id}', [AdminContactController::class, 'destroy'])->name('contact-messages.destroy');

    // Reviews moderation
    Route::get('/reviews', [ReviewController::class, 'adminIndex'])->name('reviews.index');
    Route::patch('/reviews/{id}/status', [ReviewController::class, 'updateStatus'])->name('reviews.update-status');
    Route::delete('/reviews/{id}', [ReviewController::class, 'adminDestroy'])->name('reviews.destroy');

    // Quotes moderation
    Route::get('/quotes',                  [AdminQuoteController::class, 'index'])->name('quotes.index');
    Route::get('/quotes/{id}',             [AdminQuoteController::class, 'show'])->name('quotes.show');
    Route::patch('/quotes/{id}/toggle',    [AdminQuoteController::class, 'toggleApproval'])->name('quotes.toggle');
    Route::delete('/quotes/{id}',          [AdminQuoteController::class, 'destroy'])->name('quotes.destroy');

    // Book import review
    Route::get('/import/review', fn() => view('admin.import.review'))->name('import.review');
});

// ==================== CART & CHECKOUT ====================

Route::post('/add-to-cart/{id}', [CartController::class, 'addToCart'])->name('add.to.cart');
Route::get('/get-cart', [CartController::class, 'getCart'])->name('get.cart');
Route::post('/remove-from-cart', [CartController::class, 'removeFromCart'])->name('cart.remove');
Route::post('/remove-from-cart/{id}', [CartController::class, 'removeFromCart']);
Route::post('/cart/update-quantity', [CartController::class, 'updateQuantity'])->name('cart.update-quantity');

Route::post('/checkout/store-cart', [CartController::class, 'storeForCheckout'])->name('checkout.store-cart');

Route::get('/cart', [CartController::class, 'showCart'])->name('cart.page');
Route::get('/checkout', [CartController::class, 'showCheckout'])->name('checkout.page');
Route::post('/checkout/submit', [CheckoutController::class, 'submit'])->name('checkout.submit')->middleware('throttle:10,1');
Route::post('/checkout/apply-coupon',  [CheckoutController::class, 'applyCoupon'])->name('checkout.apply-coupon');
Route::post('/checkout/remove-coupon', [CheckoutController::class, 'removeCoupon'])->name('checkout.remove-coupon');
Route::post('/checkout/trackmyorder', [CheckoutController::class, 'trackmyorder'])->name('trackmyorder')->middleware('throttle:10,1');

Route::get('/success/{id}', [CheckoutController::class, 'success'])->name('success');

// Order management via token (no auth required — token acts as the credential)
Route::get('/order/manage', [OrderManageController::class, 'show'])->name('order.manage');
Route::post('/order/manage/cancel', [OrderManageController::class, 'cancel'])->name('order.manage.cancel');
Route::post('/order/manage/return', [OrderManageController::class, 'returnRequest'])->name('order.manage.return');

// ==================== BOOK PAGES ====================

// Detail-page routes use a custom binding (defined below) that accepts both
// the slug (preferred, for SEO) and the numeric id (transition safety net for
// older internal callers that still pass $model->id). URL generation always
// produces slug URLs because HasSlug::getRouteKey() returns the slug.
Route::get('/كتاب/{book}', [BookController::class, 'showV2'])->name('moredetail2.page');
// Legacy ID URL — 301 to the slug URL so Google-indexed links don't break.
Route::get('/moredetail-v2/{id}', function ($id) {
    $book = \App\Models\Book::findOrFail($id);
    return redirect()->route('moredetail2.page', $book, 301);
})->where('id', '[0-9]+');

// ==================== REVIEWS ====================

Route::post('/reviews', [ReviewController::class, 'store'])->name('reviews.store')->middleware(['auth', 'throttle:10,1']);
Route::put('/reviews/{review}', [ReviewController::class, 'update'])->name('reviews.update')->middleware(['auth', 'throttle:10,1']);
Route::delete('/reviews/{review}', [ReviewController::class, 'destroy'])->name('reviews.destroy')->middleware(['auth', 'throttle:10,1']);
Route::post('/reviews/{review}/helpful', [ReviewController::class, 'toggleHelpful'])->name('reviews.helpful')->middleware(['auth', 'throttle:30,1']);

// ==================== WISHLIST ====================

// Authenticated wishlist (DB-backed)
Route::middleware('auth')->group(function () {
    Route::post('/wishlist/add/{bookId}', [WishlistController::class, 'add'])->name('wishlist.add');
    Route::post('/wishlist/remove/{bookId}', [WishlistController::class, 'remove'])->name('wishlist.remove');
    Route::post('/wishlist/sync', [WishlistController::class, 'syncGuestWishlist'])->name('wishlist.sync');
});

// Guest wishlist (session-backed)
Route::post('/wishlist-session/add/{bookId}', [WishlistController::class, 'addToSession'])->name('wishlist-session.add');
Route::post('/wishlist-session/remove/{bookId}', [WishlistController::class, 'removeFromSession'])->name('wishlist-session.remove');

Route::get('/wishlist', [WishlistController::class, 'index'])->name('wishlist.index');

// Recommendations
Route::post('/recommendations/hide/{bookId}', [WishlistController::class, 'hideRecommendation'])->name('recommendations.hide');

// ==================== ACCOUNT / USER ====================

Route::get('/account', [ProfileController::class, 'account'])->middleware('auth')->name('account.page');
Route::post('/account/avatar', [ProfileController::class, 'uploadAvatar'])->middleware('auth')->name('avatar.upload');
Route::get('/my-orders', [OrderController::class, 'myOrders'])->middleware('auth')->name('my-orders.index');
Route::post('/orders/{id}/cancel', [OrderController::class, 'cancelOrder'])->middleware('auth')->name('orders.cancel');
Route::get('/return-requests', [ReturnRequestController::class, 'index'])->middleware('auth')->name('return-requests.index');
Route::post('/return-requests', [ReturnRequestController::class, 'store'])->middleware('auth')->name('return-requests.store');
Route::get('/recommendations', [RecommendationController::class, 'recommendations'])->middleware('auth')->name('recommendations.index');
Route::post('reading-goal', [ProfileController::class, 'updateReadingGoal'])->middleware('auth')->name('ReadingGoal');

// ==================== READING SHELVES ====================
Route::middleware('auth')->group(function () {
    Route::post('/shelf/{bookId}', [ReadingShelfController::class, 'store'])->name('shelf.store');
    Route::delete('/shelf/{bookId}', [ReadingShelfController::class, 'remove'])->name('shelf.remove');
    Route::get('/shelf', [ReadingShelfController::class, 'index'])->name('shelf.index');
});

// ==================== CATEGORIES / BROWSE ====================

Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
Route::get('/تصنيف/{category}', [BookController::class, 'byCategory'])->name('by-category');
// Legacy /categories/{id} → 301
Route::get('/categories/{id}', function ($id) {
    $category = \App\Models\Category::findOrFail($id);
    return redirect()->route('by-category', $category, 301);
})->where('id', '[0-9]+');

Route::get('/accessories', [AccessoryController::class, 'index'])->name('accessories.index');

Route::get('/authors', [AuthorController::class, 'publicIndex'])->name('authors.index');
Route::get('/مؤلف/{author}', [AuthorController::class, 'publicShow'])->name('author.show');
// Legacy /author/{id} → 301
Route::get('/author/{id}', function ($id) {
    $author = \App\Models\Author::findOrFail($id);
    return redirect()->route('author.show', $author, 301);
})->where('id', '[0-9]+');

Route::get('/publishers', [PublisherController::class, 'publicIndex'])->name('publishers.index');
Route::get('/ناشر/{publisher}', [PublisherController::class, 'publicShow'])->name('publisher.show');
// Legacy /publisher/{id} → 301
Route::get('/publisher/{id}', function ($id) {
    $publisher = \App\Models\PublishingHouse::findOrFail($id);
    return redirect()->route('publisher.show', $publisher, 301);
})->where('id', '[0-9]+');

Route::get('/سلسلة/{series}', [AdminSeriesController::class, 'publicShow'])->name('series.show');
// Legacy /series/{id} → 301
Route::get('/series/{id}', function ($id) {
    $series = \App\Models\Series::findOrFail($id);
    return redirect()->route('series.show', $series, 301);
})->where('id', '[0-9]+');

// ==================== NOTIFICATIONS & FOLLOWS ====================

Route::middleware('auth')->group(function () {
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount'])->name('notifications.unread-count');
    Route::get('/notifications/recent',       [NotificationController::class, 'recent'])->name('notifications.recent');
    Route::post('/notifications/{id}/read',   [NotificationController::class, 'markRead'])->name('notifications.read');
    Route::post('/notifications/read-all',    [NotificationController::class, 'markAllRead'])->name('notifications.read-all');
    Route::post('/follow/{type}/{id}',        [FollowController::class, 'toggle'])->name('follow.toggle');
});

// ==================== PAGES ====================

Route::get('/about', [PageController::class, 'about'])->name('about.page');
Route::get('/contact', [PageController::class, 'contact'])->name('contact.page');
Route::post('/contact', [PageController::class, 'storeContact'])->name('contact.store')->middleware('throttle:5,1');
Route::get('/privacy', [PageController::class, 'privacy'])->name('privacy.page');
Route::get('/terms', [PageController::class, 'terms'])->name('terms.page');
Route::get('/sitemap', [PageController::class, 'sitemap'])->name('sitemap');
Route::get('/sitemap.xml', [PageController::class, 'sitemap']);
Route::post('/notify-stock/{bookId}', [StockNotificationController::class, 'store'])->name('stock.notify')->middleware('throttle:5,1');

// ==================== QUOTES ====================

Route::middleware('auth')->group(function () {
    Route::post('/quotes', [QuoteController::class, 'store'])->name('quotes.store')->middleware('throttle:10,1');
    Route::post('/quotes/{quote}/toggle-like', [QuoteController::class, 'toggleLike'])->name('quotes.toggle-like')->middleware('throttle:30,1');
    Route::delete('/quotes/{quote}', [QuoteController::class, 'destroy'])->name('quotes.destroy')->middleware('throttle:10,1');
    Route::get('/my-quotes', [QuoteController::class, 'getUserQuotes'])->name('quotes.my-quotes');
});

// ==================== INDEX / SEARCH ====================

Route::get('/', [BookController::class, 'index']);
Route::get('/index', [BookController::class, 'index'])->name('index.page');
Route::get('/books', [BookController::class, 'index']);
Route::get('/search-books', [BookController::class, 'searchproductBooks'])->name('search.books');
Route::get('/search-results', [BookController::class, 'searchResults'])->name('search.results');

// ==================== AUTH ROUTES ====================

Auth::routes();

Route::get('/login2', [AuthController::class, 'showLogin2'])->name('login2.page');

Route::post('/userlogin', [AuthController::class, 'userlogin'])->name('userlogin')->middleware('throttle:5,1');
Route::post('/adduser', [AuthController::class, 'adduser'])->name('adduser')->middleware('throttle:3,1');

// Logout via POST to prevent CSRF-based forced logout
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
// GET /logout: redirect to home (no logout — prevents CSRF forced-logout attacks)
Route::get('/logout', [AuthController::class, 'logoutRedirect'])->name('logout.redirect');

// Password reset
Route::get('/forgot-password', [PasswordResetController::class, 'showForgotPasswordForm'])
    ->name('password.request')
    ->middleware('guest');

Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLink'])
    ->name('password.email')
    ->middleware(['guest', 'throttle:3,1']);

Route::get('/reset-password/{token}/{email}', [PasswordResetController::class, 'showResetPasswordForm'])
    ->name('password.reset')
    ->middleware('guest');

Route::post('/reset-password', [PasswordResetController::class, 'resetPassword'])
    ->name('password.update')
    ->middleware('guest');
