<?php

use App\Providers\AppServiceProvider;
use App\Providers\AuthServiceProvider;
use App\Providers\Filament\AdminPanelProvider;

return [
    AuthServiceProvider::class,
    AppServiceProvider::class,
    AdminPanelProvider::class,
];
