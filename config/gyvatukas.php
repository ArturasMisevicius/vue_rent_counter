<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Gyvatukas Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Lithuanian utility circulation energy (gyvatukas)
    | calculations. These settings control the behavior of the
    | GyvatukasCalculator service.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Summer Period Configuration
    |--------------------------------------------------------------------------
    */
    'summer_start_month' => env('GYVATUKAS_SUMMER_START_MONTH', 5), // May
    'summer_end_month' => env('GYVATUKAS_SUMMER_END_MONTH', 9), // September
    'summer_months' => [5, 6, 7, 8, 9], // May through September

    /*
    |--------------------------------------------------------------------------
    | Winter Adjustment Factors
    |--------------------------------------------------------------------------
    */
    'peak_winter_months' => [12, 1, 2], // December, January, February
    'shoulder_months' => [10, 11, 3, 4], // October, November, March, April
    'peak_winter_adjustment' => 1.3, // 30% increase for peak winter
    'shoulder_adjustment' => 1.15, // 15% increase for shoulder months
    'default_winter_adjustment' => 1.2, // 20% increase for other heating season months

    /*
    |--------------------------------------------------------------------------
    | Calculation Parameters
    |--------------------------------------------------------------------------
    */
    'default_circulation_rate' => env('GYVATUKAS_DEFAULT_RATE', 15.0), // kWh per apartment per month
    'min_circulation_energy' => 0.0, // Minimum circulation energy (prevents negative values)

    /*
    |--------------------------------------------------------------------------
    | Building Size Factors
    |--------------------------------------------------------------------------
    */
    'large_building_threshold' => 50, // Apartments
    'small_building_threshold' => 10, // Apartments
    'large_building_efficiency_factor' => 0.95, // 5% efficiency gain
    'small_building_penalty_factor' => 1.1, // 10% penalty

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    */
    'cache_ttl' => env('GYVATUKAS_CACHE_TTL', 86400), // 24 hours in seconds
    'cache_prefix' => 'gyvatukas',

    /*
    |--------------------------------------------------------------------------
    | Summer Average Configuration
    |--------------------------------------------------------------------------
    */
    'summer_average_validity_months' => 12, // How long summer averages remain valid

    /*
    |--------------------------------------------------------------------------
    | Validation Configuration
    |--------------------------------------------------------------------------
    */
    'validation' => [
        'min_year' => 2020, // Minimum year for calculations
        'max_apartments' => 1000, // Maximum apartments per building
        'min_apartments' => 1, // Minimum apartments per building
    ],
];