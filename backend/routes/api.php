<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ImageController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\RankingController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\ShopController;
use App\Http\Controllers\Api\StatsController;
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

// Authentication routes (API only, no OAuth)
Route::prefix('auth')->group(function () {
    // Protected routes (must come first to avoid route conflicts)
    Route::middleware('auth:api')->group(function () {
        Route::get('/me', [AuthController::class, 'me'])->name('auth.me');
        Route::put('/me', [AuthController::class, 'updateProfile'])->name('auth.update-profile');
        Route::post('/logout', [AuthController::class, 'logout'])->name('auth.logout');
        Route::get('/token-info', [AuthController::class, 'tokenInfo'])->name('auth.token-info');
    });
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

// Image serving (public access with moderation check)
Route::get('/images/{size}/{filename}', [ImageController::class, 'serve'])
    ->middleware('throttle:image-serving');

// Protected routes (authentication required)
Route::middleware('auth:api')->group(function () {
    // Shop management (authenticated users can create/update shops)
    Route::post('/shops', [ShopController::class, 'store'])
        ->middleware('throttle:shop-creation');
    Route::put('/shops/{shop}', [ShopController::class, 'update'])
        ->middleware('throttle:general-update');
    Route::delete('/shops/{shop}', [ShopController::class, 'destroy'])
        ->middleware('throttle:delete-operation');

    // Shop image management
    Route::post('/shops/{shop}/images', [ShopController::class, 'uploadImages'])
        ->middleware('throttle:image-upload');
    Route::delete('/shops/{shop}/images/{image}', [ShopController::class, 'deleteImage'])
        ->middleware('throttle:delete-operation');
    Route::put('/shops/{shop}/images/reorder', [ShopController::class, 'reorderImages'])
        ->middleware('throttle:general-update');

    // Category management (admin only - will add middleware later)
    Route::post('/categories', [CategoryController::class, 'store'])
        ->middleware('throttle:shop-creation');
    Route::put('/categories/{category}', [CategoryController::class, 'update'])
        ->middleware('throttle:general-update');
    Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])
        ->middleware('throttle:delete-operation');

    // Review management
    Route::post('/reviews', [ReviewController::class, 'store'])
        ->middleware('throttle:review-creation');
    Route::put('/reviews/{review}', [ReviewController::class, 'update'])
        ->middleware('throttle:general-update');
    Route::delete('/reviews/{review}', [ReviewController::class, 'destroy'])
        ->middleware('throttle:delete-operation');
    Route::get('/my-reviews', [ReviewController::class, 'myReviews'])
        ->middleware('throttle:read-operation');

    // Review image management
    Route::post('/reviews/{review}/images', [ReviewController::class, 'uploadImages'])
        ->middleware('throttle:image-upload');
    Route::delete('/reviews/{review}/images/{image}', [ReviewController::class, 'deleteImage'])
        ->middleware('throttle:delete-operation');

    // Ranking management
    Route::post('/rankings', [RankingController::class, 'store'])
        ->middleware('throttle:ranking-creation');
    Route::put('/rankings/{ranking}', [RankingController::class, 'update'])
        ->middleware('throttle:general-update');
    Route::delete('/rankings/{ranking}', [RankingController::class, 'destroy'])
        ->middleware('throttle:delete-operation');
    Route::get('/my-rankings', [RankingController::class, 'myRankings'])
        ->middleware('throttle:read-operation');

    // Statistics
    Route::get('/stats/dashboard', [StatsController::class, 'dashboard'])
        ->middleware('throttle:read-operation');

    // Profile management
    Route::get('/profile', [ProfileController::class, 'show'])
        ->middleware('throttle:read-operation');
    Route::put('/profile', [ProfileController::class, 'update'])
        ->middleware('throttle:general-update');
    Route::post('/profile/image', [ProfileController::class, 'uploadProfileImage'])
        ->middleware('throttle:image-upload');
    Route::delete('/profile/image', [ProfileController::class, 'deleteProfileImage'])
        ->middleware('throttle:delete-operation');
    Route::get('/profile/image-url', [ProfileController::class, 'getProfileImageUrl'])
        ->middleware('throttle:read-operation');
});
