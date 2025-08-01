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
use App\Http\Controllers\Shipment;
use App\Http\Controllers\SystemSettingsController;
use App\Http\Controllers\QuoteController;
use App\Http\Controllers\CheckoutController;

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
    Route::get('/shipments', [ShipmentController::class, 'index'])->name('shipments.index');
    Route::get('/shipments/create', [ShipmentController::class, 'create'])->name('shipments.create');
    Route::post('/shipments', [ShipmentController::class, 'store'])->name('shipments.store');
    Route::get('/shipments/{shipment}', [ShipmentController::class, 'show'])->name('shipments.show');
    Route::post('/shipments/{shipment}/process', [ShipmentController::class, 'processShipment'])->name('shipments.process');
    Route::post('/shipments/{shipment}/bulk-enrich', [ShipmentController::class, 'bulkEnrich'])->name('shipments.bulk-enrich');
    Route::post('/shipment-items/{item}/enrich', [ShipmentController::class, 'enrichItem'])->name('shipment-items.enrich');
    
    // Book Management Routes (Enhanced)
    Route::post('/books/{book}/enrich', [Bookcontroller::class, 'enrichBook'])->name('books.enrich');
    Route::get('/books/pending-enrichment', [Bookcontroller::class, 'getPendingEnrichment'])->name('books.pending-enrichment');
    Route::post('/books/bulk-enrich', [Bookcontroller::class, 'bulkEnrichBooks'])->name('books.bulk-enrich');
    Route::get('/debug-enrich/{id}', [BookController::class, 'debugEnrich']);
    
    // System Settings Routes
    Route::get('/settings', [SystemSettingsController::class, 'index'])->name('settings.index');
    Route::post('/settings', [SystemSettingsController::class, 'update'])->name('settings.update');
    
    // Inventory Routes
   


// API Routes for AJAX calls

Route::get('/Dashbord_Admin/Shipment_Management', [ShipmentController::class, 'index'])->name('Dashbord_Admin.Shipment_Management');
Route::get('/Dashbord_Admin/ManagementSystem', [ShipmentController::class, 'showmanagement'])->name('Dashbord_Admin.ManagementSystem');

Route::get('products/api', [BookController::class, 'getProductsApi'])->name('products.api');
Route::get('products/api/{id}', [BookController::class, 'getProductById'])->name('products.api.show');

Route::get('products/api/{id}', [BookController::class, 'getProductById'])->name('products.api.show');
Route::put('products/api/{id}', [BookController::class, 'updateProduct'])->name('products.api.update');

Route::put('products/{id}', [BookController::class, 'updateProduct'])->name('pro.update');

Route::get('/test-api', [BookController::class, 'testApiConnection']);


// Dashboard Routes
Route::get('/Dashbord_Admin/dashboard', function () {
    return view('Dashbord_Admin.dashboard');
})->name('Dashbord_Admin.dashboard');
Route::resource('orders', OrderController::class);
Route::get('/Dashbord_Admin/Product', [BookController::class, 'showproduct'])->name('Dashbord_Admin.product');

Route::get('/Dashbord_Admin/Product/data', [BookController::class, 'getProducts']);
Route::get('/Dashbord_Admin/Product/{id}', [BookController::class, 'getProductById']); 
Route::put('/Dashbord_Admin/Product/{id}', [BookController::class, 'updateProduct']);
Route::post('/Dashbord_Admin/Product/add', [BookController::class, 'addProduct'])->name('product.add');

Route::get('/Dashbord_Admin/Product', [BookController::class, 'showproduct'])->name('Dashbord_Admin.product');
Route::resource('client', Usercontroller::class);

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

Route::get('/checkout', [CartController::class, 'showCheckout'])->name('checkout.page');



Route::post('/checkout/submit', [CheckoutController::class, 'submit'])->name('checkout.submit');


Route::get('/success/{id}', [CheckoutController::class, 'success'])->name('success');


Route::post('/remove-from-cart/{id}', [CartController::class, 'removeFromCart'])
    ->name('cart.remove');

Route::post('/cart/update-quantity', [CartController::class, 'updateQuantity'])
    ->name('cart.update-quantity');


/////////////

Route::get('/moredetail/{id}', [BookController::class, 'show'])->name('moredetail.page');

Route::get('/login2', function () {
    return view('login2');
})->name('login2.page');
Route::get('/logout', [Usercontroller::class, 'logout'])->name('logout');

////////review//////////
Route::post('/reviews', [ReviewController::class, 'store'])->name('reviews.store')->middleware('auth');



Route::get('/index2', function () {
    return view('index2');
})->name('index2.page');

Route::get('/index3', function () {
    return view('index3');
})->name('index3.page');



/////////////////////////wishlist
Route::middleware('auth')->group(function () {
    Route::post('/wishlist/add/{bookId}', [WishlistController::class, 'add'])->name('wishlist.add');
    Route::post('/wishlist/remove/{bookId}', [WishlistController::class, 'remove'])->name('wishlist.remove');
    Route::get('/wishlist', [WishlistController::class, 'index'])->name('wishlist.index');
});
Route::post('/recommendations/hide/{bookId}', [WishlistController::class, 'hideRecommendation'])->name('recommendations.hide');



/////////////////////////header routes
Route::get('/account', [UserController::class, 'account'])->name('account.page');

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

// Authentication Routes
Auth::routes();

// Resource and Custom Routes

Route::post('/adduser', [Usercontroller::class, 'adduser'])->name('adduser');
Route::post('/userlogin', [Usercontroller::class, 'userlogin'])->name('userlogin');
Route::get('/books', [BookController::class, 'index']);



Route::get('/settings', [SystemSettingsController::class, 'index'])->name('settings.index');
Route::post('/settings', [SystemSettingsController::class, 'update'])->name('settings.update');