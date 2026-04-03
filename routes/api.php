<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

/*
|--------------------------------------------------------------------------
| Book Import API (for n8n workflow)
|--------------------------------------------------------------------------
*/
Route::prefix('import')->withoutMiddleware('throttle:api')->group(function () {
    Route::post('/book', [\App\Http\Controllers\Api\BookImportController::class, 'store']);
    Route::get('/staged', [\App\Http\Controllers\Api\BookImportController::class, 'staged']);
    Route::post('/book/image', [\App\Http\Controllers\Api\BookImportController::class, 'serveImage']);
});
