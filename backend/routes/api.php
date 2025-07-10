<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\RankingController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\ShopController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Authentication routes
Route::prefix('auth')->group(function () {
    // Protected routes (must come first to avoid route conflicts)
    Route::middleware('auth:api')->group(function () {
        Route::get('/me', [AuthController::class, 'me'])->name('auth.me');
        Route::post('/logout', [AuthController::class, 'logout'])->name('auth.logout');
        Route::get('/token-info', [AuthController::class, 'tokenInfo'])->name('auth.token-info');
    });

    // Public OAuth routes (must come after protected routes)
    Route::get('/{provider}', [AuthController::class, 'oauthRedirect'])->name('auth.redirect');
    Route::get('/{provider}/callback', [AuthController::class, 'oauthCallback'])->name('auth.callback');
});

// Public routes (no authentication required)
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/categories/{category}', [CategoryController::class, 'show']);
Route::get('/shops', [ShopController::class, 'index']);
Route::get('/shops/{shop}', [ShopController::class, 'show']);
Route::get('/reviews', [ReviewController::class, 'index']);
Route::get('/reviews/{review}', [ReviewController::class, 'show']);
Route::get('/rankings', [RankingController::class, 'index']);
Route::get('/rankings/{ranking}', [RankingController::class, 'show']);
Route::get('/public-rankings', [RankingController::class, 'publicRankings']);

// Protected routes (authentication required)
Route::middleware('auth:api')->group(function () {
    // Shop management (authenticated users can create/update shops)
    Route::post('/shops', [ShopController::class, 'store'])
        ->middleware('throttle:shop-creation');
    Route::put('/shops/{shop}', [ShopController::class, 'update'])
        ->middleware('throttle:20,60,user');
    Route::delete('/shops/{shop}', [ShopController::class, 'destroy'])
        ->middleware('throttle:5,60,user');

    // Shop image management
    Route::post('/shops/{shop}/images', [ShopController::class, 'uploadImages'])
        ->middleware('throttle:15,60,user');  // 1時間に15回まで
    Route::delete('/shops/{shop}/images/{image}', [ShopController::class, 'deleteImage'])
        ->middleware('throttle:30,60,user');
    Route::put('/shops/{shop}/images/reorder', [ShopController::class, 'reorderImages'])
        ->middleware('throttle:10,60,user');

    // Category management (admin only - will add middleware later)
    Route::post('/categories', [CategoryController::class, 'store'])
        ->middleware('throttle:5,60,user');
    Route::put('/categories/{category}', [CategoryController::class, 'update'])
        ->middleware('throttle:10,60,user');
    Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])
        ->middleware('throttle:5,60,user');

    // Review management
    Route::post('/reviews', [ReviewController::class, 'store'])
        ->middleware('throttle:review-creation');  // 1時間に5回まで
    Route::put('/reviews/{review}', [ReviewController::class, 'update'])
        ->middleware('throttle:10,60,user');
    Route::delete('/reviews/{review}', [ReviewController::class, 'destroy'])
        ->middleware('throttle:10,60,user');
    Route::get('/my-reviews', [ReviewController::class, 'myReviews'])
        ->middleware('throttle:100,60,user');  // 読み取りは緩め

    // Review image management
    Route::post('/reviews/{review}/images', [ReviewController::class, 'uploadImages'])
        ->middleware('throttle:image-upload');  // 1時間に20回まで
    Route::delete('/reviews/{review}/images/{image}', [ReviewController::class, 'deleteImage'])
        ->middleware('throttle:30,60,user');

    // Ranking management
    Route::post('/rankings', [RankingController::class, 'store'])
        ->middleware('throttle:ranking-creation');
    Route::put('/rankings/{ranking}', [RankingController::class, 'update'])
        ->middleware('throttle:20,60,user');
    Route::delete('/rankings/{ranking}', [RankingController::class, 'destroy'])
        ->middleware('throttle:10,60,user');
    Route::get('/my-rankings', [RankingController::class, 'myRankings'])
        ->middleware('throttle:100,60,user');  // 読み取りは緩め
});
