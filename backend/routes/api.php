<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;

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
    });
    
    // Public OAuth routes (must come after protected routes)
    Route::get('/{provider}', [AuthController::class, 'oauthRedirect'])->name('auth.redirect');
    Route::get('/{provider}/callback', [AuthController::class, 'oauthCallback'])->name('auth.callback');
});

// Protected routes
Route::middleware('auth:api')->group(function () {
    // User routes will be added here
    
    // Shop routes will be added here
    
    // Review routes will be added here
    
    // Ranking routes will be added here
});