<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Subscription Plan Limits
    |--------------------------------------------------------------------------
    |
    | These values define the limits for each subscription plan type.
    | Admins can create properties and tenants up to these limits.
    |
    */

    'max_properties_basic' => env('MAX_PROPERTIES_BASIC', 10),
    'max_properties_professional' => env('MAX_PROPERTIES_PROFESSIONAL', 50),
    'max_properties_enterprise' => env('MAX_PROPERTIES_ENTERPRISE', 999999),

    'max_tenants_basic' => env('MAX_TENANTS_BASIC', 50),
    'max_tenants_professional' => env('MAX_TENANTS_PROFESSIONAL', 200),
    'max_tenants_enterprise' => env('MAX_TENANTS_ENTERPRISE', 999999),

    /*
    |--------------------------------------------------------------------------
    | Subscription Grace Period
    |--------------------------------------------------------------------------
    |
    | Number of days after expiry where the subscription is still considered
    | valid but with limited functionality (read-only mode).
    |
    */

    'grace_period_days' => env('SUBSCRIPTION_GRACE_PERIOD_DAYS', 7),

    /*
    |--------------------------------------------------------------------------
    | Subscription Expiry Warning
    |--------------------------------------------------------------------------
    |
    | Number of days before expiry to start showing renewal reminders.
    |
    */

    'expiry_warning_days' => env('SUBSCRIPTION_EXPIRY_WARNING_DAYS', 14),

    /*
    |--------------------------------------------------------------------------
    | Subscription Cache TTL
    |--------------------------------------------------------------------------
    |
    | Cache time-to-live in seconds for subscription lookups.
    | Default: 300 seconds (5 minutes)
    |
    | Security: Lower values provide fresher data but increase database load.
    | Higher values improve performance but may delay subscription updates.
    |
    */

    'cache_ttl' => env('SUBSCRIPTION_CACHE_TTL', 300),

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Rate limits for subscription check operations to prevent DoS attacks.
    |
    | authenticated: Requests per minute for authenticated users
    | unauthenticated: Requests per minute for IP-based (unauthenticated)
    |
    */

    'rate_limit' => [
        'authenticated' => env('SUBSCRIPTION_RATE_LIMIT_AUTHENTICATED', 60),
        'unauthenticated' => env('SUBSCRIPTION_RATE_LIMIT_UNAUTHENTICATED', 10),
    ],
];
