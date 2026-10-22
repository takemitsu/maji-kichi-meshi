<?php

use App\Providers\AppServiceProvider;
use App\Providers\Filament\AdminPanelProvider;
use App\Providers\RateLimitServiceProvider;

return [
    AppServiceProvider::class,
    AdminPanelProvider::class,
    RateLimitServiceProvider::class,
];
