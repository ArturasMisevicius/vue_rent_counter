<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Validation Rules
    |--------------------------------------------------------------------------
    |
    | Configuration for validation rules used throughout the billing system.
    |
    */

    'validation' => [
        'change_reason_min_length' => env('BILLING_CHANGE_REASON_MIN', 10),
        'change_reason_max_length' => env('BILLING_CHANGE_REASON_MAX', 500),
    ],

    /*
    |--------------------------------------------------------------------------
    | Vilniaus Vandenys Tariffs
    |--------------------------------------------------------------------------
    |
    | Default tariff rates for Vilniaus Vandenys water services.
    | These can be overridden by database tariff configurations.
    |
    */

    'water_tariffs' => [
        'default_supply_rate' => env('WATER_SUPPLY_RATE', 0.97),  // EUR per m³
        'default_sewage_rate' => env('WATER_SEWAGE_RATE', 1.23),  // EUR per m³
        'default_fixed_fee' => env('WATER_FIXED_FEE', 0.85),  // EUR per month
    ],

    /*
    |--------------------------------------------------------------------------
    | Gyvatukas Calculation
    |--------------------------------------------------------------------------
    |
    | Configuration for hot water circulation fee calculations.
    |
    */

    'gyvatukas' => [
        'heating_season_start_month' => 10,  // October
        'heating_season_end_month' => 4,     // April
        'water_specific_heat' => 4.186,      // kJ/(kg·°C)
        'temperature_delta' => 40,           // °C (typical hot water ΔT)
    ],

    /*
    |--------------------------------------------------------------------------
    | Invoice Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for invoice generation and management.
    |
    */

    'invoice' => [
        'default_due_days' => 14,  // Days until invoice is due
        'late_payment_fee_percentage' => 0.05,  // 5% late fee
    ],

    /*
    |--------------------------------------------------------------------------
    | Property Defaults
    |--------------------------------------------------------------------------
    |
    | Default values for property creation in the admin panel.
    |
    */

    'property' => [
        'default_apartment_area' => env('DEFAULT_APARTMENT_AREA', 50),  // m²
        'default_house_area' => env('DEFAULT_HOUSE_AREA', 120),  // m²
        'min_area' => 0,
        'max_area' => 10000,
    ],
];
