<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\Usercontroller;
use App\Http\Controllers\BookController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\ShipmentController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\WishlistController;
use App\Http\Controllers\AuthorController;
use App\Http\Controllers\Shipment;
use App\Http\Controllers\SystemSettingsController;
use App\Http\Controllers\QuoteController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\PublisherController;
use App\Http\Controllers\ReturnRequestController;

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/
// Shipment Management Routes

    
    // Shipment Routes
    
    
    
    
    
    Route::post('/shipments/{shipment}/bulk-enrich', [ShipmentController::class, 'bulkEnrich'])->name('shipments.bulk-enrich');
    Route::post('/shipment-items/{item}/enrich', [ShipmentController::class, 'enrichItem'])->name('shipment-items.enrich');
    
    // Book Management Routes (Enhanced)
    Route::post('/books/{book}/enrich', [Bookcontroller::class, 'enrichBook'])->name('books.enrich');
    Route::get('/books/{book}/preview-enrich', [Bookcontroller::class, 'previewEnrichment'])->name('books.preview-enrich');
    Route::post('/books/{book}/enrich-selected', [Bookcontroller::class, 'applySelectedEnrichment'])->name('books.enrich-selected');
    Route::get('/books/pending-enrichment', [Bookcontroller::class, 'getPendingEnrichment'])->name('books.pending-enrichment');
    Route::post('/books/bulk-enrich', [Bookcontroller::class, 'bulkEnrichBooks'])->name('books.bulk-enrich');
    Route::get('/debug-enrich/{id}', [BookController::class, 'debugEnrich']);
    
    // System Settings Routes
    Route::get('/settings', [SystemSettingsController::class, 'index'])->name('settings.index');
    Route::post('/settings', [SystemSettingsController::class, 'update'])->name('settings.update');
    
    // Inventory Routes
   


// API Routes for AJAX calls




Route::get('products/api', [BookController::class, 'getProductsApi'])->name('products.api');
Route::get('products/api/stats', [BookController::class, 'getProductsApiStats'])->name('products.api.stats');
Route::get('products/api/{id}', [BookController::class, 'getProductById'])->name('products.api.show');
Route::put('products/api/{id}', [BookController::class, 'updateProduct'])->name('products.api.update');



Route::get('/test-api', [BookController::class, 'testApiConnection']);


// Dashboard Routes

//Route::resource('orders', OrderController::class);



//Route::get('/Dashbord_Admin/Product/{id}', [BookController::class, 'getProductById']); 










Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {
    //Dashboard
        Route::get('/Dashbord_Admin/dashboard', [Usercontroller::class, 'dashboard'])->name('Dashbord_Admin.dashboard');

        Route::get('/client/{id}', [Usercontroller::class, 'showclient'])->name('client.show');
        Route::put('/client/{id}', [Usercontroller::class, 'update'])->name('client.update');
        Route::post('/client/{id}/reset-password', [Usercontroller::class, 'resetPassword'])->name('client.reset-password'); 
    //client 
    Route::resource('client', Usercontroller::class);    
    // order blade:
        Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
        Route::get('/orders/{id}', [OrderController::class, 'show'])->name('orders.show');
        Route::put('/orders/{id}', [OrderController::class, 'update'])->name('orders.update');
        Route::get('/orders/{id}/edit', [OrderController::class, 'edit'])->name('orders.edit');
        Route::post('/orders/{id}', [OrderController::class, 'store'])->name('orders.store');
    //product blade
        //route
        Route::get('/Dashbord_Admin/Product', [BookController::class, 'showproduct'])->name('Dashbord_Admin.product');
        //add product
        Route::post('/Dashbord_Admin/Product/add', [BookController::class, 'addProduct'])->name('product.add');
        //search
        Route::get('/products', [BookController::class, 'showproduct'])->name('products.index');
        // Show product (JSON)
        Route::get('/products/{id}', [BookController::class, 'viewProduct']);
        // Update product
        Route::put('/products/{id}', [BookController::class, 'updateProduct']);
        //Route::post('/admin/products/{id}', [ProductController::class, 'update']);
        // Delete product
        //Route::delete('/admin/products/{id}', [ProductController::class, 'destroy']);
    //shipment management blade
        //search
        Route::get('/search-book', [BookController::class, 'searchBook']);
        Route::get('/search-authors', [AuthorController::class, 'search']);
        Route::get('/search-publishers', [PublisherController::class, 'search']);
        Route::get('/shipments', [ShipmentController::class, 'searchShipment'])->name('shipments.search');
        //blade shipment management
        Route::get('/Dashbord_Admin/Shipment_Management', [ShipmentController::class, 'index'])->name('Dashbord_Admin.Shipment_Management');
        //get next shipment reference
        Route::get('/shipments/next-reference', [ShipmentController::class, 'getNextShipmentReference'])->name('shipments.next-reference');
        //store
        Route::post('/shipments', [ShipmentController::class, 'store'])->name('shipments.store');
        //show
        Route::get('/shipments/{shipment}', [ShipmentController::class, 'show'])->name('shipments.show');
        //process
        Route::post('/shipments/{shipment}/process', [ShipmentController::class, 'processShipment'])->name('shipments.process');
        //edit shipment
        Route::get('/shipments/{shipment}/edit', [ShipmentController::class, 'editShipment']);
        Route::put('/shipments/{shipment}', [ShipmentController::class, 'updateShipment'])->name('shipments.update');
        Route::delete('/shipments/{shipment}/items/{item}', [ShipmentController::class, 'destroyItem']);
        //status
        Route::patch('/shipments/{shipment}/status', [ShipmentController::class, 'updateStatus'])->name('shipments.status');
        //delete shipment 
        Route::delete('/shipments/{shipment}', [ShipmentController::class, 'destroy'])->name('shipments.destroy');
    //management system
        Route::get('/Dashbord_Admin/ManagementSystem', [ShipmentController::class, 'showmanagement'])->name('Dashbord_Admin.ManagementSystem');    
});

// Other Routes
// 
Route::post('/add-to-cart/{id}', [CartController::class, 'addToCart'])->name('add.to.cart');
Route::get('/get-cart', [CartController::class, 'getCart'])->name('get.cart');
Route::post('/remove-from-cart', [CartController::class, 'removeFromCart'])->name('cart.remove');
/////////////
Route::post('/checkout/store-cart', function (Request $request) {
    session()->put('checkout_cart', json_decode($request->cart_data, true));
    return redirect()->route('checkout.page');
})->name('checkout.store-cart'); // Different name

Route::get('/cart', [CartController::class, 'showCart'])->name('cart.page');
Route::get('/checkout', [CartController::class, 'showCheckout'])->name('checkout.page');



Route::post('/checkout/submit', [CheckoutController::class, 'submit'])->name('checkout.submit');
Route::post('/checkout/trackmyorder', [CheckoutController::class, 'trackmyorder'])
     ->name('trackmyorder');


Route::get('/success/{id}', [CheckoutController::class, 'success'])->name('success');


Route::post('/remove-from-cart/{id}', [CartController::class, 'removeFromCart'])
    ->name('cart.remove');

Route::post('/cart/update-quantity', [CartController::class, 'updateQuantity'])
    ->name('cart.update-quantity');


/////////////

Route::get('/moredetail/{id}', [BookController::class, 'show'])->name('moredetail.page');



////////review//////////
Route::post('/reviews', [ReviewController::class, 'store'])->name('reviews.store')->middleware('auth');



Route::get('/index2', function () {
    return view('index2');
})->name('index2.page');

Route::get('/index3', function () {
    return view('index3');
})->name('index3.page');



// Wishlist routes for authenticated users
Route::post('/wishlist/add/{bookId}', [WishlistController::class, 'add'])->name('wishlist.add');
Route::post('/wishlist/remove/{bookId}', [WishlistController::class, 'remove'])->name('wishlist.remove');
Route::get('/wishlist', [WishlistController::class, 'index'])->middleware('auth')->name('wishlist.index');

// Wishlist routes for guests (session-based)
Route::post('/wishlist-session/add/{bookId}', [WishlistController::class, 'addToSession'])->name('wishlist-session.add');
Route::post('/wishlist-session/remove/{bookId}', [WishlistController::class, 'removeFromSession'])->name('wishlist-session.remove');

// Sync guest wishlist after login
Route::post('/wishlist/sync', [WishlistController::class, 'syncGuestWishlist'])->middleware('auth')->name('wishlist.sync');

// Recommendations
Route::post('/recommendations/hide/{bookId}', [WishlistController::class, 'hideRecommendation'])->name('recommendations.hide');


/////////////////////////header routes
Route::get('/account', [UserController::class, 'account'])->name('account.page');
Route::get('/my-orders', [OrderController::class, 'myOrders'])->middleware('auth')->name('my-orders.index');
Route::post('/orders/{id}/cancel', [OrderController::class, 'cancelOrder'])->middleware('auth')->name('orders.cancel');
Route::get('/return-requests', [ReturnRequestController::class, 'index'])->middleware('auth')->name('return-requests.index');
Route::post('/return-requests', [ReturnRequestController::class, 'store'])->middleware('auth')->name('return-requests.store');

///////////category////////////
Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
Route::get('/categories/{category}', [BookController::class, 'byCategory'])->name('by-category');

// Quote routes
Route::middleware('auth')->group(function () {
    // Store new quote
    Route::post('/quotes', [QuoteController::class, 'store'])->name('quotes.store');
    
    // Toggle like/unlike quote
    Route::post('/quotes/{quote}/toggle-like', [QuoteController::class, 'toggleLike'])->name('quotes.toggle-like');
    
    // Delete quote (owner only)
    Route::delete('/quotes/{quote}', [QuoteController::class, 'destroy'])->name('quotes.destroy');
    
    // Get user's quotes
    Route::get('/my-quotes', [QuoteController::class, 'getUserQuotes'])->name('quotes.my-quotes');
});
//reading goal
Route::post('reading-goal', [Usercontroller::class, 'updateReadingGoal'])
    ->name('ReadingGoal');
// Root and Index Routes
Route::get('/', [BookController::class, 'index']);
Route::get('/index', [BookController::class, 'index'])->name('index.page');
Route::post('/add-to-cart/{id}', [CartController::class, 'addToCart'])->name('add.to.cart');
// search route
Route::get('/search-books', [BookController::class, 'searchBooks'])->name('search.books');
Route::get('/search-results', [BookController::class, 'searchResults'])->name('search.results');
Route::get('/search-books', [BookController::class, 'searchBooksAjax'])->name('search.books.ajax');

// Authentication Routes
Auth::routes();
Route::get('/books', [BookController::class, 'index']);
// Resource and Custom Routes
// ==================== AUTH ROUTES ====================

// Login page
Route::get('/login2', function () {
    return view('login2');
})->name('login2.page');

// User login
Route::post('/userlogin', [Usercontroller::class, 'userlogin'])->name('userlogin');

// User registration
Route::post('/adduser', [Usercontroller::class, 'adduser'])->name('adduser');

// User logout
Route::get('/logout', [Usercontroller::class, 'logout'])->name('logout');

// ==================== PASSWORD RESET ROUTES ====================

// Show forgot password form
Route::get('/forgot-password', [Usercontroller::class, 'showForgotPasswordForm'])
    ->name('password.request')
    ->middleware('guest');

// Send password reset link
Route::post('/forgot-password', [Usercontroller::class, 'sendResetLink'])
    ->name('password.email')
    ->middleware('guest');

// Show reset password form
Route::get('/reset-password/{token}/{email}', [Usercontroller::class, 'showResetPasswordForm'])
    ->name('password.reset')
    ->middleware('guest');

// Process password reset
Route::post('/reset-password', [Usercontroller::class, 'resetPassword'])
    ->name('password.update')
    ->middleware('guest');


Route::get('/settings', [SystemSettingsController::class, 'index'])->name('settings.index');
Route::post('/settings', [SystemSettingsController::class, 'update'])->name('settings.update');