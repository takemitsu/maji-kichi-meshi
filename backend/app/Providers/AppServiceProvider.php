<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // intervention/image-laravelは'image'として登録するが、
        // ImageManager::classでも解決できるようにエイリアスを追加
        $this->app->alias('image', \Intervention\Image\ImageManager::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
