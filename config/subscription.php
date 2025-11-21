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
];
