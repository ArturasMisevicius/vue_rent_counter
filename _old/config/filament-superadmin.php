<?php

declare(strict_types=1);
use App\Filament\Superadmin\Pages\Dashboard;
use App\Http\Middleware\EnsureUserIsSuperadmin;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

return [
    /*
    |--------------------------------------------------------------------------
    | Superadmin Panel Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the Filament superadmin panel including performance
    | optimizations, security settings, and feature toggles.
    |
    */

    'panel' => [
        'id' => 'superadmin',
        'path' => 'superadmin',
        'primary_color' => 'red',
        'auth_guard' => 'web',
    ],

    'features' => [
        'resource_discovery' => env('SUPERADMIN_RESOURCE_DISCOVERY', false),
        'page_discovery' => env('SUPERADMIN_PAGE_DISCOVERY', false),
        'widget_discovery' => env('SUPERADMIN_WIDGET_DISCOVERY', false),
        'navigation' => env('SUPERADMIN_NAVIGATION', false),
        'global_search' => env('SUPERADMIN_GLOBAL_SEARCH', false),
        'spa_mode' => env('SUPERADMIN_SPA_MODE', false),
        'unsaved_changes_alerts' => env('SUPERADMIN_UNSAVED_CHANGES_ALERTS', false),
        'sidebar_collapsible' => env('SUPERADMIN_SIDEBAR_COLLAPSIBLE', false),
        'top_navigation' => env('SUPERADMIN_TOP_NAVIGATION', false),
    ],

    'performance' => [
        'cache_ttl' => env('SUPERADMIN_CACHE_TTL', 300),
        'enable_caching' => env('SUPERADMIN_ENABLE_CACHING', true),
        'lazy_loading' => env('SUPERADMIN_LAZY_LOADING', true),
    ],

    'widgets' => [
        'default' => [
            AccountWidget::class,
        ],
        'custom' => [
            // \App\Filament\Superadmin\Widgets\SystemOverviewWidget::class,
            // \App\Filament\Superadmin\Widgets\RecentUsersWidget::class,
        ],
    ],

    'pages' => [
        'default' => [
            Dashboard::class,
        ],
    ],

    'navigation_groups' => [
        [
            'name' => 'System',
            'collapsed' => false,
        ],
        [
            'name' => 'Users',
            'collapsed' => false,
        ],
        [
            'name' => 'Monitoring',
            'collapsed' => true,
        ],
    ],

    'middleware' => [
        'core' => [
            EncryptCookies::class,
            AddQueuedCookiesToResponse::class,
            StartSession::class,
            AuthenticateSession::class,
            ShareErrorsFromSession::class,
            VerifyCsrfToken::class,
            SubstituteBindings::class,
        ],
        'filament' => [
            DisableBladeIconComponents::class,
            DispatchServingFilamentEvent::class,
        ],
        'auth' => [
            Authenticate::class,
            EnsureUserIsSuperadmin::class,
        ],
    ],

    'security' => [
        'require_superadmin_role' => true,
        'log_access_attempts' => env('SUPERADMIN_LOG_ACCESS', true),
    ],
];
