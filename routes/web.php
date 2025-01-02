<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\Usercontroller;

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
Route::post('/login', [AuthController::class, 'login'])->name('login');
Route::post('/register', [AuthController::class, 'register'])->name('register');

Route::get('/', function () {
    return view('welcome');
});

Route::get('/Dashbord_Admin/dashboard', function () {
    return view('Dashbord_Admin.dashboard');
})->name('Dashbord_Admin.dashboard');

Route::get('/Dashbord_Admin/Product', function () {
    return view('Dashbord_Admin.product');
})->name('Dashbord_Admin.product');



Route::get('/moredetail', function () {
    return view('moredetail');
})->name('moredetail.page');

Route::get('/index', function () {
    return view('index');
})->name('index.page');

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

Route::get('/', function () {
    return view('index');
});

Auth::routes();

Route::resource('orders', OrderController::class);


Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
Route::post('/adduser', [Usercontroller::class, 'adduser'])->name('adduser');
Route::post('/userlogin', [Usercontroller::class, 'userlogin'])->name('userlogin');
