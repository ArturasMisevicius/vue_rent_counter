<?php

use App\Providers\AppServiceProvider;
use App\Providers\AuthServiceProvider;
use App\Providers\FeatureServiceProvider;
use App\Providers\Filament\AppPanelProvider;

return [
    AuthServiceProvider::class,
    AppServiceProvider::class,
    FeatureServiceProvider::class,
    AppPanelProvider::class,
];
