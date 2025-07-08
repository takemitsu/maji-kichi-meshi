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
    Route::get('/{provider}', [AuthController::class, 'oauthRedirect'])->name('auth.redirect');
    Route::get('/{provider}/callback', [AuthController::class, 'oauthCallback'])->name('auth.callback');
    Route::post('/logout', [AuthController::class, 'logout'])->name('auth.logout');
    Route::post('/refresh', [AuthController::class, 'refresh'])->name('auth.refresh');
    Route::get('/me', [AuthController::class, 'me'])->name('auth.me');
});

// Protected routes
Route::middleware('auth:api')->group(function () {
    // User routes will be added here
    
    // Shop routes will be added here
    
    // Review routes will be added here
    
    // Ranking routes will be added here
});