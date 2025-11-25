<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Gyvatukas Calculation Constants
    |--------------------------------------------------------------------------
    |
    | Configuration for hot water circulation fee (gyvatukas) calculations.
    | These values are based on Lithuanian building standards.
    |
    */

    // Specific heat capacity of water (kWh/m³·°C)
    // Acceptable range: 0.5 - 2.0
    'water_specific_heat' => env('GYVATUKAS_WATER_SPECIFIC_HEAT', 1.163),

    // Temperature difference for hot water heating (°C)
    // Standard assumption: heating from 10°C to 55°C = 45°C difference
    // Acceptable range: 20.0 - 80.0
    'temperature_delta' => env('GYVATUKAS_TEMPERATURE_DELTA', 45.0),

    // Heating season months (October through April)
    'heating_season_start_month' => env('GYVATUKAS_HEATING_START', 10),
    'heating_season_end_month' => env('GYVATUKAS_HEATING_END', 4),

    // Summer calculation period (May through September)
    'summer_start_month' => env('GYVATUKAS_SUMMER_START', 5),
    'summer_end_month' => env('GYVATUKAS_SUMMER_END', 9),

    /*
    |--------------------------------------------------------------------------
    | Security Settings
    |--------------------------------------------------------------------------
    |
    | Security configuration for gyvatukas calculations.
    |
    */

    // Rate limiting
    'rate_limit' => [
        // Per-user rate limit (calculations per minute)
        'per_user' => env('GYVATUKAS_RATE_LIMIT_USER', 10),
        
        // Per-tenant rate limit (calculations per minute)
        'per_tenant' => env('GYVATUKAS_RATE_LIMIT_TENANT', 100),
        
        // Rate limit window in seconds
        'window' => env('GYVATUKAS_RATE_LIMIT_WINDOW', 60),
    ],

    // Audit settings
    'audit' => [
        // Enable audit trail
        'enabled' => env('GYVATUKAS_AUDIT_ENABLED', true),
        
        // Retention period in days (0 = keep forever)
        'retention_days' => env('GYVATUKAS_AUDIT_RETENTION_DAYS', 365),
    ],

    // Logging settings
    'logging' => [
        // Hash building IDs in logs for privacy
        'hash_building_ids' => env('GYVATUKAS_HASH_BUILDING_IDS', true),
        
        // Log performance metrics
        'log_performance' => env('GYVATUKAS_LOG_PERFORMANCE', true),
    ],

    // Validation settings
    'validation' => [
        // Minimum year for billing month
        'min_year' => env('GYVATUKAS_MIN_YEAR', 2020),
        
        // Require properties for calculation
        'require_properties' => env('GYVATUKAS_REQUIRE_PROPERTIES', true),
    ],
];
