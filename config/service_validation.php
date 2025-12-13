<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Service Validation Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the ServiceValidationEngine that validates utility
    | service configurations and meter readings with support for seasonal
    | adjustments, consumption limits, and business rules.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Default Consumption Limits
    |--------------------------------------------------------------------------
    |
    | Default minimum and maximum consumption values used when service-specific
    | limits are not configured. These serve as fallback values.
    |
    */
    'default_min_consumption' => env('SERVICE_VALIDATION_MIN_CONSUMPTION', 0),
    'default_max_consumption' => env('SERVICE_VALIDATION_MAX_CONSUMPTION', 10000),

    /*
    |--------------------------------------------------------------------------
    | Rate Change Restrictions
    |--------------------------------------------------------------------------
    |
    | Configuration for rate change frequency limits and validation rules.
    | Builds on existing tariff active date functionality.
    |
    */
    'rate_change_frequency_days' => env('SERVICE_VALIDATION_RATE_CHANGE_FREQUENCY', 30),
    'rate_change_advance_notice_days' => env('SERVICE_VALIDATION_ADVANCE_NOTICE', 7),
    'allow_retroactive_changes' => env('SERVICE_VALIDATION_ALLOW_RETROACTIVE', false),

    /*
    |--------------------------------------------------------------------------
    | Seasonal Adjustments
    |--------------------------------------------------------------------------
    |
    | Seasonal validation rules building on gyvatukas summer/winter logic.
    | Different utility types have different seasonal patterns.
    |
    */
    'seasonal_adjustments' => [
        'heating' => [
            'summer_max_threshold' => 50, // Maximum heating consumption in summer (kWh)
            'winter_min_threshold' => 100, // Minimum heating consumption in winter (kWh)
            'peak_winter_multiplier' => 1.5, // Expected increase in peak winter months
            'shoulder_season_multiplier' => 1.2, // Expected increase in shoulder months
        ],
        'water' => [
            'summer_range' => [
                'min' => 80, // Minimum water consumption in summer (liters)
                'max' => 150, // Maximum water consumption in summer (liters)
            ],
            'winter_range' => [
                'min' => 60, // Minimum water consumption in winter (liters)
                'max' => 120, // Maximum water consumption in winter (liters)
            ],
            'seasonal_variance_threshold' => 0.3, // 30% variance allowed between seasons
        ],
        'electricity' => [
            'summer_range' => [
                'min' => 100, // Minimum electricity consumption in summer (kWh)
                'max' => 300, // Maximum electricity consumption in summer (kWh)
            ],
            'winter_range' => [
                'min' => 150, // Minimum electricity consumption in winter (kWh)
                'max' => 400, // Maximum electricity consumption in winter (kWh)
            ],
            'heating_season_multiplier' => 1.3, // Expected increase during heating season
        ],
        'default' => [
            'variance_threshold' => 0.3, // 30% variance threshold for unknown utility types
            'seasonal_adjustment_factor' => 1.1, // 10% adjustment for seasonal changes
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Data Quality Validation
    |--------------------------------------------------------------------------
    |
    | Configuration for data quality checks leveraging existing meter reading
    | audit trail and validation patterns.
    |
    */
    'data_quality' => [
        'consumption_variance_threshold' => 0.5, // 50% variance from historical average
        'anomaly_detection_threshold' => 2.0, // Standard deviations for outlier detection
        'max_consumption_multiplier' => 5.0, // Maximum consumption vs historical average
        'min_consumption_threshold' => 0.01, // Minimum consumption to avoid zero readings
        'duplicate_detection_window_hours' => 24, // Window for duplicate reading detection
        'reading_sequence_validation' => true, // Enable reading sequence validation
        'audit_trail_validation' => true, // Enable audit trail consistency checks
    ],

    /*
    |--------------------------------------------------------------------------
    | Input Method Validation
    |--------------------------------------------------------------------------
    |
    | Validation rules specific to different meter reading input methods.
    | Extends existing InputMethod enum validation logic.
    |
    */
    'input_method_validation' => [
        'photo_ocr' => [
            'require_photo_path' => true,
            'require_manual_validation' => true,
            'confidence_threshold' => 0.8, // OCR confidence threshold
        ],
        'estimated' => [
            'require_review' => true,
            'max_consecutive_estimates' => 3, // Maximum consecutive estimated readings
            'variance_limit' => 0.2, // 20% variance limit for estimates
        ],
        'api_integration' => [
            'require_source_identification' => true,
            'validate_api_credentials' => true,
            'rate_limit_validation' => true,
        ],
        'csv_import' => [
            'require_batch_validation' => true,
            'max_batch_size' => 1000, // Maximum readings per batch
            'duplicate_handling' => 'reject', // 'reject', 'skip', or 'update'
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Business Rules Validation
    |--------------------------------------------------------------------------
    |
    | Configuration for service-specific business rules and constraints.
    | These can be overridden at the utility service level.
    |
    */
    'business_rules' => [
        'reading_frequency' => [
            'default_required_days' => 30, // Default reading frequency requirement
            'max_gap_days' => 90, // Maximum allowed gap between readings
            'frequency_tolerance_days' => 5, // Tolerance for frequency requirements
        ],
        'consumption_patterns' => [
            'enable_pattern_analysis' => true,
            'pattern_analysis_months' => 12, // Months of history for pattern analysis
            'pattern_deviation_threshold' => 0.4, // 40% deviation from pattern
        ],
        'validation_workflow' => [
            'auto_approve_threshold' => 0.1, // Auto-approve if variance < 10%
            'require_manager_approval_threshold' => 1.0, // Require manager approval if variance > 100%
            'escalation_threshold' => 2.0, // Escalate if variance > 200%
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance and Caching
    |--------------------------------------------------------------------------
    |
    | Configuration for validation performance optimization and caching.
    |
    */
    'performance' => [
        'cache_ttl_seconds' => 3600, // 1 hour cache TTL for validation rules
        'batch_validation_size' => 100, // Optimal batch size for bulk validation
        'enable_validation_caching' => true, // Enable caching of validation results
        'cache_historical_data' => true, // Cache historical consumption data
        'historical_data_cache_ttl' => 86400, // 24 hours for historical data cache
        
        // PERFORMANCE OPTIMIZATIONS
        'enable_bulk_preloading' => env('VALIDATION_BULK_PRELOADING', true), // Enable optimized bulk data loading
        'enable_query_optimization' => env('VALIDATION_QUERY_OPTIMIZATION', true), // Enable query optimizations
        'enable_memoization' => env('VALIDATION_MEMOIZATION', true), // Enable computation memoization
        'chunk_size' => env('VALIDATION_CHUNK_SIZE', 50), // Memory-efficient processing chunk size
        'max_batch_size' => env('VALIDATION_MAX_BATCH_SIZE', 500), // Maximum batch size for safety
        
        // MONITORING AND ALERTING
        'enable_performance_monitoring' => env('VALIDATION_PERFORMANCE_MONITORING', true),
        'performance_alert_thresholds' => [
            'slow_operation_ms' => 1000, // Alert if operation takes >1 second
            'high_memory_mb' => 100, // Alert if memory usage >100MB
            'excessive_queries' => 50, // Alert if >50 queries per operation
            'low_throughput_per_sec' => 10, // Alert if <10 readings per second
        ],
        
        // CACHE WARMING
        'enable_cache_warming' => env('VALIDATION_CACHE_WARMING', true),
        'cache_warm_on_startup' => env('VALIDATION_CACHE_WARM_STARTUP', false),
        'cache_warm_batch_size' => 20, // Service configurations to warm per batch
    ],

    /*
    |--------------------------------------------------------------------------
    | Notification and Alerting
    |--------------------------------------------------------------------------
    |
    | Configuration for validation alerts and notifications.
    |
    */
    'notifications' => [
        'enable_validation_alerts' => env('SERVICE_VALIDATION_ALERTS', true),
        'alert_on_validation_failure' => true,
        'alert_on_anomaly_detection' => true,
        'alert_on_rate_change_violation' => true,
        'notification_channels' => ['mail', 'database'], // Available: mail, sms, slack, database
        'alert_thresholds' => [
            'error_rate_threshold' => 0.1, // Alert if error rate > 10%
            'anomaly_rate_threshold' => 0.05, // Alert if anomaly rate > 5%
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Integration Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for integration with existing systems and services.
    |
    */
    'integration' => [
        'gyvatukas_integration' => [
            'enable_seasonal_validation' => true,
            'use_gyvatukas_summer_winter_logic' => true,
            'inherit_building_factors' => true,
        ],
        'tariff_integration' => [
            'validate_against_active_tariffs' => true,
            'check_tariff_effective_dates' => true,
            'enforce_tariff_constraints' => true,
        ],
        'audit_integration' => [
            'log_all_validations' => true,
            'create_audit_trail' => true,
            'store_validation_metadata' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Validation Rules Schema
    |--------------------------------------------------------------------------
    |
    | JSON schema definitions for validation rule structures.
    | Used for validating service-specific validation configurations.
    |
    */
    'validation_schema' => [
        'consumption_limits' => [
            'type' => 'object',
            'properties' => [
                'min' => ['type' => 'number', 'minimum' => 0],
                'max' => ['type' => 'number', 'minimum' => 0],
                'variance_threshold' => ['type' => 'number', 'minimum' => 0, 'maximum' => 1],
            ],
            'required' => ['min', 'max'],
        ],
        'seasonal_adjustments' => [
            'type' => 'object',
            'properties' => [
                'summer_threshold' => ['type' => 'number'],
                'winter_threshold' => ['type' => 'number'],
                'variance_threshold' => ['type' => 'number', 'minimum' => 0, 'maximum' => 1],
            ],
        ],
        'business_constraints' => [
            'type' => 'array',
            'items' => [
                'type' => 'object',
                'properties' => [
                    'field' => ['type' => 'string'],
                    'operator' => ['type' => 'string', 'enum' => ['>', '<', '>=', '<=', '==', '!=']],
                    'value' => ['type' => 'number'],
                    'message' => ['type' => 'string'],
                    'severity' => ['type' => 'string', 'enum' => ['error', 'warning']],
                ],
                'required' => ['field', 'operator', 'value'],
            ],
        ],
    ],
];