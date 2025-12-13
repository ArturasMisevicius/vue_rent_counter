<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Service Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines are used by the service validation engine
    | for error messages, warnings, and recommendations.
    |
    */

    // General validation messages
    'unauthorized_meter_reading' => 'You are not authorized to validate this meter reading.',
    'unauthorized_rate_change' => 'You are not authorized to validate rate changes for this service configuration.',
    'unauthorized_batch_operation' => 'You are not authorized to perform batch validation operations.',
    'unauthorized_service_configuration' => 'You are not authorized to access this service configuration.',
    'system_error' => 'A system error occurred during validation.',
    'service_configuration_not_found' => 'The specified service configuration was not found.',
    'some_readings_not_found' => 'Some of the specified meter readings were not found.',
    'rate_schedule_empty' => 'Rate schedule cannot be empty.',
    'validator_unavailable' => 'The required validator is not available.',

    // Consumption validation messages
    'consumption_below_minimum' => 'Consumption of :consumption :unit is below the minimum allowed (:minimum :unit).',
    'consumption_exceeds_maximum' => 'Consumption of :consumption :unit exceeds the maximum allowed (:maximum :unit).',
    'consumption_high_variance' => 'Consumption of :consumption :unit varies significantly from historical average (:average :unit) by :variance%.',
    'consumption_extreme_variance' => 'Consumption of :consumption :unit shows extreme variance from historical average (:average :unit) by :variance%.',
    'zero_or_negative_consumption' => 'Zero or negative consumption detected. Please verify the meter reading.',
    'consumption_unreasonably_high' => 'Consumption of :consumption :unit is unreasonably high (threshold: :threshold :unit).',
    'insufficient_historical_data' => 'Insufficient historical data for pattern analysis.',

    // Service-specific consumption messages
    'electricity_consumption_very_high' => 'Electricity consumption appears very high for typical residential use.',
    'electricity_consumption_very_low' => 'Electricity consumption appears very low for typical residential use.',
    'water_consumption_very_high' => 'Water consumption appears very high for typical residential use.',
    'water_consumption_very_low' => 'Water consumption appears very low for typical residential use.',
    'heating_consumption_high_in_summer' => 'Heating consumption is unusually high for summer period.',
    'heating_consumption_low_in_winter' => 'Heating consumption is unusually low for winter period.',

    // Seasonal validation messages
    'heating_consumption_high_summer' => 'Heating consumption of :consumption :unit exceeds summer threshold (:threshold :unit).',
    'heating_consumption_low_winter' => 'Heating consumption of :consumption :unit is below winter minimum (:threshold :unit).',
    'heating_consumption_peak_winter' => 'Heating consumption of :consumption :unit exceeds expected peak winter maximum (:expected_max :unit).',
    'heating_consumption_high_shoulder' => 'Heating consumption of :consumption :unit is high for shoulder season (expected max: :expected_max :unit).',
    'electricity_consumption_low_season' => 'Electricity consumption of :consumption :unit is below expected :season minimum (:minimum :unit).',
    'electricity_consumption_high_season' => 'Electricity consumption of :consumption :unit exceeds expected :season maximum (:maximum :unit).',
    'electricity_heating_season_high' => 'Electricity consumption of :consumption :unit is high for heating season (expected max: :expected_max :unit).',
    'water_consumption_low_season' => 'Water consumption of :consumption :unit is below expected :season minimum (:minimum :unit).',
    'water_consumption_high_season' => 'Water consumption of :consumption :unit exceeds expected :season maximum (:maximum :unit).',
    'water_seasonal_variance_high' => 'Water consumption of :consumption :unit shows high seasonal variance from average (:average :unit) by :variance%.',
    'consumption_above_seasonal_expectation' => 'Consumption of :consumption :unit exceeds seasonal expectation (:expected_max :unit).',
    'seasonal_variance_detected' => 'Seasonal variance detected: :consumption :unit vs average :average :unit (:variance% difference).',

    // Data quality validation messages
    'no_previous_reading_for_sequence' => 'No previous reading available for sequence validation.',
    'possible_meter_rollover' => 'Possible meter rollover detected: current :current :unit, previous :previous :unit.',
    'reading_sequence_invalid' => 'Invalid reading sequence: current :current :unit is less than previous :previous :unit.',
    'reading_date_sequence_invalid' => 'Invalid date sequence: current date :current_date is not after previous date :previous_date.',
    'statistical_anomaly_detected' => 'Statistical anomaly detected: consumption :consumption :unit (Z-score: :z_score, threshold: :threshold, mean: :mean :unit).',
    'insufficient_data_for_anomaly_detection' => 'Insufficient historical data for statistical anomaly detection.',
    'duplicate_reading_detected' => 'Duplicate reading detected: :value :unit on :date (within :window_hours hour window).',
    'missing_required_reading_field' => 'Missing required reading field: :field.',
    'reading_value_mismatch' => 'Reading value mismatch: stored :stored_value :unit vs calculated :calculated_value :unit.',
    'missing_audit_entered_by' => 'Missing audit information: entered_by field is required.',
    'validated_reading_missing_validator' => 'Validated reading is missing validator information.',
    'photo_required_for_input_method' => 'Photo is required for input method: :input_method.',

    // Recommendations
    'check_for_leaks_or_issues' => 'Consider checking for leaks or equipment issues if high consumption continues.',
    'verify_meter_functionality' => 'Verify meter functionality if low consumption persists.',
    'check_heating_system_summer' => 'Check heating system configuration for summer operation.',
    'check_heating_efficiency_winter' => 'Check heating system efficiency for winter operation.',
    'check_heating_efficiency' => 'Check heating system efficiency and insulation.',
    'check_cooling_efficiency' => 'Check cooling system efficiency and insulation.',
    'check_for_water_leaks' => 'Check for water leaks in the system.',
    'investigate_consumption_anomaly' => 'Investigate the cause of unusual consumption patterns.',
    'verify_reading_uniqueness' => 'Verify that this reading is unique and not a duplicate entry.',
    'verify_meter_rollover' => 'Verify if this is a legitimate meter rollover or reading error.',

    // Request validation messages
    'reading_ids_required' => 'Reading IDs are required for batch validation.',
    'reading_ids_must_be_array' => 'Reading IDs must be provided as an array.',
    'reading_ids_minimum_one' => 'At least one reading ID must be provided.',
    'reading_ids_maximum_exceeded' => 'Maximum batch size of :max readings exceeded.',
    'reading_id_must_be_integer' => 'Each reading ID must be an integer.',
    'reading_id_not_found' => 'Reading ID not found.',
    'duplicate_reading_ids_not_allowed' => 'Duplicate reading IDs are not allowed in batch requests.',
    'batch_size_exceeds_optimal' => 'Batch size (:current) exceeds optimal size (:optimal) and may impact performance.',

    // System error messages
    'batch_system_error' => 'A system error occurred during batch validation.',
    'rate_change_system_error' => 'A system error occurred during rate change validation.',
    'rules_system_error' => 'A system error occurred while retrieving validation rules.',
    'metrics_system_error' => 'A system error occurred while retrieving validation metrics.',
    'estimated_reading_system_error' => 'A system error occurred during estimated reading validation.',

    // Attributes for form validation
    'attributes' => [
        'service_configuration' => 'service configuration',
        'validation_options' => 'validation options',
        'reading_ids' => 'reading IDs',
        'reading_id' => 'reading ID',
    ],
];