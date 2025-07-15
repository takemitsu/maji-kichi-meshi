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
        // レビュー作成制限
        RateLimiter::for('review-creation', function (Request $request) {
            $user = $request->user();
            $key = $user ? 'user:' . $user->id : 'ip:' . $request->ip();

            // 開発環境では制限を緩和
            if (app()->environment('local')) {
                return Limit::perMinute(10)->by($key);
            }

            return Limit::perHour(5)->by($key);
        });

        // 画像アップロード制限
        RateLimiter::for('image-upload', function (Request $request) {
            $user = $request->user();
            $key = $user ? 'user:' . $user->id : 'ip:' . $request->ip();

            // 開発環境では制限を緩和
            if (app()->environment('local')) {
                return Limit::perMinute(30)->by($key);
            }

            return Limit::perHour(20)->by($key);
        });

        // 店舗作成制限
        RateLimiter::for('shop-creation', function (Request $request) {
            $user = $request->user();
            $key = $user ? 'user:' . $user->id : 'ip:' . $request->ip();

            // 開発環境では制限を緩和
            if (app()->environment('local')) {
                return Limit::perMinute(20)->by($key);
            }

            return Limit::perHour(10)->by($key);
        });

        // ランキング作成制限
        RateLimiter::for('ranking-creation', function (Request $request) {
            $user = $request->user();
            $key = $user ? 'user:' . $user->id : 'ip:' . $request->ip();

            // 開発環境では制限を緩和
            if (app()->environment('local')) {
                return Limit::perMinute(20)->by($key);
            }

            return Limit::perHour(10)->by($key);
        });

        // 汎用更新操作制限
        RateLimiter::for('general-update', function (Request $request) {
            $user = $request->user();
            $key = $user ? 'user:' . $user->id : 'ip:' . $request->ip();

            // 開発環境では制限を緩和
            if (app()->environment('local')) {
                return Limit::perMinute(30)->by($key);
            }

            return Limit::perHour(20)->by($key);
        });

        // 削除操作制限
        RateLimiter::for('delete-operation', function (Request $request) {
            $user = $request->user();
            $key = $user ? 'user:' . $user->id : 'ip:' . $request->ip();

            // 開発環境では制限を緩和
            if (app()->environment('local')) {
                return Limit::perMinute(20)->by($key);
            }

            return Limit::perHour(30)->by($key);
        });

        // 画像配信制限
        RateLimiter::for('image-serving', function (Request $request) {
            // 開発環境では制限を緩和
            if (app()->environment('local')) {
                return Limit::perMinute(200)->by($request->ip());
            }

            return Limit::perMinute(60)->by($request->ip());
        });

        // 読み取り操作制限
        RateLimiter::for('read-operation', function (Request $request) {
            $user = $request->user();
            $key = $user ? 'user:' . $user->id : 'ip:' . $request->ip();

            // 開発環境では制限を緩和
            if (app()->environment('local')) {
                return Limit::perMinute(200)->by($key);
            }

            return Limit::perHour(100)->by($key);
        });
    }
}
