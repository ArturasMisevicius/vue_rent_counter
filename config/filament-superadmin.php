<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Superadmin Panel Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration options specific to the superadmin panel to prevent
    | performance issues and timeouts.
    |
    */

    'performance' => [
        /*
        |--------------------------------------------------------------------------
        | Widget Caching
        |--------------------------------------------------------------------------
        |
        | Cache duration for widgets in seconds to prevent heavy database queries
        | from causing timeouts during navigation rendering.
        |
        */
        'widget_cache_duration' => env('SUPERADMIN_WIDGET_CACHE', 300), // 5 minutes

        /*
        |--------------------------------------------------------------------------
        | Navigation Caching
        |--------------------------------------------------------------------------
        |
        | Cache duration for navigation items to prevent repeated authorization
        | checks during sidebar rendering.
        |
        */
        'navigation_cache_duration' => env('SUPERADMIN_NAV_CACHE', 300), // 5 minutes

        /*
        |--------------------------------------------------------------------------
        | Query Limits
        |--------------------------------------------------------------------------
        |
        | Default limits for queries to prevent memory issues and timeouts.
        |
        */
        'recent_users_limit' => env('SUPERADMIN_RECENT_USERS_LIMIT', 20),
        'max_search_results' => env('SUPERADMIN_MAX_SEARCH_RESULTS', 50),
    ],

    'features' => [
        /*
        |--------------------------------------------------------------------------
        | Feature Toggles
        |--------------------------------------------------------------------------
        |
        | Enable or disable features that might impact performance.
        |
        */
        'global_search' => env('SUPERADMIN_GLOBAL_SEARCH', false),
        'spa_mode' => env('SUPERADMIN_SPA_MODE', false),
        'unsaved_changes_alerts' => env('SUPERADMIN_UNSAVED_ALERTS', false),
    ],
];