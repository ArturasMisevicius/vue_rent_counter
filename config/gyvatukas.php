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
    'water_specific_heat' => env('GYVATUKAS_WATER_SPECIFIC_HEAT', 1.163),

    // Temperature difference for hot water heating (°C)
    // Standard assumption: heating from 10°C to 55°C = 45°C difference
    'temperature_delta' => env('GYVATUKAS_TEMPERATURE_DELTA', 45.0),

    // Heating season months (October through April)
    'heating_season_start_month' => env('GYVATUKAS_HEATING_START', 10),
    'heating_season_end_month' => env('GYVATUKAS_HEATING_END', 4),

    // Summer calculation period (May through September)
    'summer_start_month' => env('GYVATUKAS_SUMMER_START', 5),
    'summer_end_month' => env('GYVATUKAS_SUMMER_END', 9),
];
