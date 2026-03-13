<?php

declare(strict_types=1);

return [
    'status' => [
        'healthy' => 'Healthy',
        'degraded' => 'Degraded',
        'unhealthy' => 'Unhealthy',
        'circuit_open' => 'Circuit Open',
        'maintenance' => 'Maintenance',
        'unknown' => 'Unknown',
    ],

    'errors' => [
        'operation_failed' => 'The external service operation failed. Please try again later.',
        'service_unavailable' => 'The external service is currently unavailable. Please try again later.',
        'service_temporarily_unavailable' => 'The service is temporarily unavailable due to repeated failures.',
        'service_required' => 'This operation requires an external service that is currently unavailable.',
        'sync_failed' => 'Data synchronization failed. Some changes may not be saved.',
        'configuration_error' => 'There is a configuration error with the external service.',
        'authentication_failed' => 'Authentication with the external service failed.',
        'rate_limit_exceeded' => 'Too many requests to the external service. Please wait before trying again.',
        'data_validation_failed' => 'The data could not be validated by the external service.',
        'timeout' => 'The external service request timed out.',
        'unknown_error' => 'An unknown error occurred with the external service.',
    ],

    'offline' => [
        'utility_provider_unavailable' => 'Utility provider service is offline. Using cached data.',
        'meter_reading_unavailable' => 'Meter reading service is offline. Manual entry required.',
        'billing_unavailable' => 'Billing service is offline. Calculations may be delayed.',
        'ocr_unavailable' => 'OCR service is offline. Manual reading entry required.',
        'service_unavailable' => 'Service :service is offline. Limited functionality available.',
    ],

    'sync' => [
        'started' => 'Data synchronization started',
        'completed' => 'Data synchronization completed successfully',
        'failed' => 'Data synchronization failed',
        'partial' => 'Data synchronization completed with some errors',
        'no_data' => 'No data to synchronize',
        'conflict_resolved' => 'Data conflict resolved automatically',
        'conflict_manual_review' => 'Data conflict requires manual review',
    ],

    'health' => [
        'check_started' => 'Health check started for :service',
        'check_completed' => 'Health check completed for :service',
        'check_failed' => 'Health check failed for :service',
        'all_services_healthy' => 'All external services are healthy',
        'some_services_degraded' => 'Some external services are experiencing issues',
        'critical_services_down' => 'Critical external services are unavailable',
    ],

    'circuit_breaker' => [
        'opened' => 'Circuit breaker opened for :service due to repeated failures',
        'closed' => 'Circuit breaker closed for :service - service is healthy again',
        'half_open' => 'Circuit breaker is testing :service availability',
    ],

    'maintenance' => [
        'enabled' => 'Maintenance mode enabled for :service',
        'disabled' => 'Maintenance mode disabled for :service',
        'scheduled' => 'Scheduled maintenance for :service from :start to :end',
    ],

    'notifications' => [
        'service_down' => ':service is currently unavailable',
        'service_restored' => ':service has been restored',
        'sync_completed' => 'Data synchronization completed for :service',
        'sync_failed' => 'Data synchronization failed for :service',
        'manual_review_required' => 'Manual review required for data conflicts in :service',
    ],
];