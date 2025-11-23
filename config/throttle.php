<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting Configuration
    |--------------------------------------------------------------------------
    |
    | Configure rate limiting for Filament actions and API endpoints.
    | These settings help prevent abuse and protect system resources.
    |
    */

    /**
     * Maximum number of requests allowed per decay period.
     *
     * Default: 60 requests per minute
     * Production recommendation: 60-120 depending on load
     */
    'requests' => (int) env('THROTTLE_REQUESTS', 60),

    /**
     * Decay period in minutes.
     *
     * Default: 1 minute
     * The time window for counting requests
     */
    'decay_minutes' => (int) env('THROTTLE_DECAY_MINUTES', 1),

    /**
     * Tenant management specific limits.
     *
     * Lower limits for sensitive operations like tenant reassignment
     * to prevent abuse and notification spam.
     */
    'tenant_management' => [
        'requests' => (int) env('THROTTLE_TENANT_MANAGEMENT_REQUESTS', 30),
        'decay_minutes' => (int) env('THROTTLE_TENANT_MANAGEMENT_DECAY', 1),
    ],

    /**
     * Bulk operations limits.
     *
     * Stricter limits for bulk delete/export operations
     * to prevent resource exhaustion.
     */
    'bulk_operations' => [
        'requests' => (int) env('THROTTLE_BULK_REQUESTS', 10),
        'decay_minutes' => (int) env('THROTTLE_BULK_DECAY', 1),
    ],

];
