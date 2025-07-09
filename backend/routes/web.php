<?php

use App\Http\Controllers\Admin\TwoFactorController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

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
