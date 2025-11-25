<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Billing Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the billing service including tariffs, rate limiting,
    | and security settings.
    |
    */

    'rate_limit' => [
        'enabled' => env('BILLING_RATE_LIMIT_ENABLED', true),
        'max_attempts' => env('BILLING_RATE_LIMIT_MAX_ATTEMPTS', 10),
        'decay_minutes' => env('BILLING_RATE_LIMIT_DECAY_MINUTES', 1),
    ],

    'water_tariffs' => [
        'default_supply_rate' => env('WATER_SUPPLY_RATE', 0.97),
        'default_sewage_rate' => env('WATER_SEWAGE_RATE', 1.23),
        'default_fixed_fee' => env('WATER_FIXED_FEE', 0.85),
    ],

    'invoice' => [
        'default_due_days' => env('INVOICE_DUE_DAYS', 14),
    ],

    'security' => [
        'audit_retention_days' => env('AUDIT_RETENTION_DAYS', 90),
        'encrypt_audit_logs' => env('ENCRYPT_AUDIT_LOGS', true),
        'redact_pii_in_logs' => env('REDACT_PII_IN_LOGS', true),
    ],
];
