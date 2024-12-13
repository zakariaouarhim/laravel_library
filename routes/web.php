<?php

use Illuminate\Support\Facades\Route;

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

Route::get('/', function () {
    return view('welcome');
});
Route::get('/', function () {
    return view('index3');
});
Route::get('/', function () {
    return view('index2');
});
Route::get('/moredetail', function () {
    return view('moredetail');
})->name('moredetail.page');
Route::get('/index', function () {
    return view('index');
})->name('index.page');
Route::get('/main', function () {
    return view('main');
})->name('main.page');

Route::get('/', function () {
    return view('index');
});
