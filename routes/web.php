<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\Usercontroller;
use App\Http\Controllers\BookController;
use App\Http\Controllers\CartController;
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


Route::get('/moredetail/{id}', [BookController::class, 'show'])->name('moredetail.page');

Route::get('/login2', function () {
    return view('login2');
})->name('login2.page');

Route::get('/index2', function () {
    return view('index2');
})->name('index2.page');

Route::get('/index3', function () {
    return view('index3');
})->name('index3.page');

Route::get('/checkout', function () {
    return view('checkout');
})->name('checkout.page');

// Root and Index Routes
Route::get('/', [BookController::class, 'index']);
Route::get('/index', [BookController::class, 'index'])->name('index.page');
Route::post('/add-to-cart/{id}', [CartController::class, 'addToCart'])->name('add.to.cart');

// Authentication Routes
Auth::routes();

// Resource and Custom Routes

Route::post('/adduser', [Usercontroller::class, 'adduser'])->name('adduser');
Route::post('/userlogin', [Usercontroller::class, 'userlogin'])->name('userlogin');
Route::get('/books', [BookController::class, 'index']);