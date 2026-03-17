<?php

use App\Providers\AppServiceProvider;
use App\Providers\AuthServiceProvider;
use App\Providers\Filament\AppPanelProvider;

return [
    AuthServiceProvider::class,
    AppServiceProvider::class,
    AppPanelProvider::class,
];
