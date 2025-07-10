<?php

use App\Http\Controllers\Admin\TwoFactorController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'app' => 'マジキチメシ API',
        'version' => '1.0.0',
        'status' => 'running',
        'message' => 'This is an API-only application. Please use the API endpoints.',
        'api_documentation' => [
            'base_url' => request()->getSchemeAndHttpHost() . '/api',
            'authentication' => 'OAuth 2.0 via /api/auth/{provider}',
            'supported_providers' => ['google', 'github', 'line', 'twitter'],
            'endpoints' => [
                'shops' => '/api/shops',
                'categories' => '/api/categories', 
                'reviews' => '/api/reviews',
                'rankings' => '/api/rankings'
            ]
        ],
        'admin_panel' => request()->getSchemeAndHttpHost() . '/admin'
    ], 200, [], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
});

// Laravel 11 auth middleware用のloginルート定義
// API専用プロジェクトだが、auth middlewareのデフォルト動作でroute('login')が参照されるため定義
Route::get('/login', function () {
    return response()->json([
        'message' => 'This is an API-only application. Please use OAuth authentication via /api/auth/{provider}',
        'oauth_providers' => ['google', 'github', 'line', 'twitter']
    ], 401);
})->name('login');

// Admin Two-Factor Authentication Routes
Route::middleware(['auth'])->prefix('admin/two-factor')->name('admin.two-factor.')->group(function () {
    Route::get('/setup', [TwoFactorController::class, 'setup'])->name('setup');
    Route::post('/confirm', [TwoFactorController::class, 'confirm'])->name('confirm');
    Route::get('/challenge', [TwoFactorController::class, 'challenge'])->name('challenge');
    Route::post('/verify', [TwoFactorController::class, 'verify'])->name('verify');
    Route::get('/recovery-challenge', [TwoFactorController::class, 'recoveryChallenge'])->name('recovery-challenge');
    Route::post('/verify-recovery', [TwoFactorController::class, 'verifyRecovery'])->name('verify-recovery');
    Route::get('/manage', [TwoFactorController::class, 'manage'])->name('manage');
    Route::post('/regenerate-recovery-codes', [TwoFactorController::class, 'regenerateRecoveryCodes'])->name('regenerate-recovery-codes');
    Route::post('/disable', [TwoFactorController::class, 'disable'])->name('disable');
});
