<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class RateLimitServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // ユーザーベースの制限を設定
        RateLimiter::for('user-based', function (Request $request) {
            return Limit::perHour(60)->by($request->user()?->id ?: $request->ip());
        });

        // レビュー作成制限
        RateLimiter::for('review-creation', function (Request $request) {
            $user = $request->user();
            $key = $user ? 'user:' . $user->id : 'ip:' . $request->ip();
            return Limit::perHour(5)->by($key);
        });

        // 画像アップロード制限
        RateLimiter::for('image-upload', function (Request $request) {
            $user = $request->user();
            $key = $user ? 'user:' . $user->id : 'ip:' . $request->ip();
            return Limit::perHour(20)->by($key);
        });

        // 店舗作成制限
        RateLimiter::for('shop-creation', function (Request $request) {
            $user = $request->user();
            $key = $user ? 'user:' . $user->id : 'ip:' . $request->ip();
            return Limit::perHour(10)->by($key);
        });

        // ランキング作成制限
        RateLimiter::for('ranking-creation', function (Request $request) {
            $user = $request->user();
            $key = $user ? 'user:' . $user->id : 'ip:' . $request->ip();
            return Limit::perHour(10)->by($key);
        });
    }
}
