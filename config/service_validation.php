<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Service Validation Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the ServiceValidationEngine and related validation
    | services. These settings control validation behavior, security limits,
    | and performance parameters.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Default Validation Limits
    |--------------------------------------------------------------------------
    |
    | Default limits for consumption validation when service-specific
    | limits are not configured.
    |
    */

    'default_min_consumption' => env('VALIDATION_MIN_CONSUMPTION', 0),
    'default_max_consumption' => env('VALIDATION_MAX_CONSUMPTION', 10000),
    'rate_change_frequency_days' => env('VALIDATION_RATE_CHANGE_FREQUENCY', 30),

    /*
    |--------------------------------------------------------------------------
    | Seasonal Adjustments
    |--------------------------------------------------------------------------
    |
    | Configuration for seasonal validation adjustments based on utility type.
    |
    */

    'seasonal_adjustments' => [
        'heating' => [
            'summer_max_threshold' => 50,
            'winter_min_threshold' => 100,
        ],
        'water' => [
            'summer_range' => ['min' => 80, 'max' => 150],
            'winter_range' => ['min' => 60, 'max' => 120],
        ],
        'electricity' => [
            'summer_range' => ['min' => 200, 'max' => 800],
            'winter_range' => ['min' => 150, 'max' => 600],
        ],
        'default' => [
            'variance_threshold' => 0.3,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Configuration
    |--------------------------------------------------------------------------
    |
    | Settings that control performance and resource usage.
    |
    */

    'performance' => [
        'batch_validation_size' => env('VALIDATION_BATCH_SIZE', 100),
        'cache_ttl_seconds' => env('VALIDATION_CACHE_TTL', 3600),
        'historical_months' => env('VALIDATION_HISTORICAL_MONTHS', 12),
        'chunk_size' => env('VALIDATION_CHUNK_SIZE', 50),
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Configuration
    |--------------------------------------------------------------------------
    |
    | Security-related validation settings.
    |
    */

    'security' => [
        'max_array_depth' => 3,
        'max_array_size' => 1000,
        'max_string_length' => 255,
        'max_rate_value' => 999999.99,
        'min_rate_value' => 0,
        'max_date_future_years' => 10,
        'max_date_past_years' => 50,
        'max_time_slots' => 50,
        'max_tiers' => 50,
    ],

    /*
    |--------------------------------------------------------------------------
    | True-up Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for estimated reading true-up calculations.
    |
    */

    'true_up_threshold' => env('VALIDATION_TRUE_UP_THRESHOLD', 5.0),

    /*
    |--------------------------------------------------------------------------
    | Validation Rules
    |--------------------------------------------------------------------------
    |
    | Default validation rules for different utility types.
    |
    */

    'validation_rules' => [
        'electricity' => [
            'min_consumption' => 0,
            'max_consumption' => 50000, // kWh
            'variance_threshold' => 0.5,
        ],
        'water_cold' => [
            'min_consumption' => 0,
            'max_consumption' => 1000, // m³
            'variance_threshold' => 0.4,
        ],
        'water_hot' => [
            'min_consumption' => 0,
            'max_consumption' => 500, // m³
            'variance_threshold' => 0.4,
        ],
        'heating' => [
            'min_consumption' => 0,
            'max_consumption' => 100000, // kWh or m³
            'variance_threshold' => 0.6,
        ],
        'gas' => [
            'min_consumption' => 0,
            'max_consumption' => 10000, // m³
            'variance_threshold' => 0.5,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Error Messages
    |--------------------------------------------------------------------------
    |
    | Custom error messages for validation failures.
    |
    */

    'error_messages' => [
        'unauthorized_access' => 'You do not have permission to access this resource.',
        'rate_schedule_invalid' => 'The provided rate schedule contains invalid data.',
        'consumption_out_of_range' => 'Consumption value is outside acceptable range.',
        'date_out_of_range' => 'Date is outside acceptable range.',
        'structure_too_complex' => 'Data structure is too complex.',
        'array_too_large' => 'Data array is too large.',
        'system_error' => 'A system error occurred during validation.',
    ],

];