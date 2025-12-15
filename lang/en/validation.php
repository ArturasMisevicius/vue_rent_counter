<?php

declare(strict_types=1);

return [
    'errors_occurred' => 'Errors Occurred',
    'exists' => 'Exists',
    
    // Service Validation Engine messages
    'unauthorized_rate_change' => 'Unauthorized to validate rate changes for this service',
    'rate_schedule_empty' => 'Rate schedule cannot be empty',
    'validator_unavailable' => 'Rate change validator not available',
    'unauthorized_meter_reading' => 'Unauthorized to validate this meter reading',
    'system_error' => 'Validation system error: :error',

    // Consumption validation
    'consumption_below_minimum' => 'Consumption :consumption :unit is below minimum limit of :minimum :unit.',
    'consumption_exceeds_maximum' => 'Consumption :consumption :unit exceeds maximum limit of :maximum :unit.',
    'insufficient_historical_data' => 'Insufficient historical data to validate consumption patterns.',
    'consumption_extreme_variance' => 'Consumption :consumption :unit deviates by :variance% from average :average :unit.',
    'consumption_high_variance' => 'Consumption :consumption :unit varies by :variance% from average :average :unit.',
    'check_for_leaks_or_issues' => 'Check for leaks or unusual usage.',
    'verify_meter_functionality' => 'Verify meter functionality and the entered reading.',
    'zero_or_negative_consumption' => 'Zero or negative consumption detected.',
    'consumption_unreasonably_high' => 'Consumption :consumption :unit is unreasonably high (threshold: :threshold :unit).',
    'electricity_consumption_very_high' => 'Electricity consumption is very high.',
    'electricity_consumption_very_low' => 'Electricity consumption is very low.',
    'water_consumption_very_high' => 'Water consumption is very high.',
    'water_consumption_very_low' => 'Water consumption is very low.',
    'heating_consumption_high_in_summer' => 'Heating consumption is high during summer period.',
    'heating_consumption_low_in_winter' => 'Heating consumption is low during winter period.',

    // Seasonal validation
    'heating_consumption_high_summer' => 'Heating consumption :consumption :unit is above summer threshold :threshold :unit.',
    'check_heating_system_summer' => 'Check the heating system settings for the summer period.',
    'heating_consumption_low_winter' => 'Heating consumption :consumption :unit is below winter threshold :threshold :unit.',
    'check_heating_efficiency_winter' => 'Check heating efficiency for the winter period.',
    'heating_consumption_peak_winter' => 'Heating consumption :consumption :unit exceeds expected maximum :expected_max :unit for peak winter.',
    'heating_consumption_high_shoulder' => 'Heating consumption :consumption :unit exceeds expected maximum :expected_max :unit for shoulder season.',
    'electricity_consumption_low_season' => 'Electricity consumption :consumption :unit is below expected minimum :minimum :unit for :season season.',
    'electricity_consumption_high_season' => 'Electricity consumption :consumption :unit exceeds expected maximum :maximum :unit for :season season.',
    'check_heating_efficiency' => 'Check heating efficiency and insulation.',
    'check_cooling_efficiency' => 'Check cooling efficiency and AC usage.',
    'electricity_heating_season_high' => 'Electricity consumption :consumption :unit exceeds expected maximum :expected_max :unit for heating season.',
    'water_consumption_low_season' => 'Water consumption :consumption :unit is below expected minimum :minimum :unit for :season season.',
    'water_consumption_high_season' => 'Water consumption :consumption :unit exceeds expected maximum :maximum :unit for :season season.',
    'check_for_water_leaks' => 'Check for water leaks.',
    'water_seasonal_variance_high' => 'Water consumption :consumption :unit varies by :variance% from average :average :unit.',
    'consumption_above_seasonal_expectation' => 'Consumption :consumption :unit exceeds expected seasonal maximum :expected_max :unit.',
    'seasonal_variance_detected' => 'Seasonal variance detected: :consumption :unit vs average :average :unit (:variance%).',
];
