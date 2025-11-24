<?php

declare(strict_types=1);

return [
    'validation' => [
        'plan_type' => [
            'required' => 'Plan type is required.',
            'in' => 'Plan type must be basic, professional, or enterprise.',
        ],
        'status' => [
            'required' => 'Status is required.',
            'in' => 'Status must be active, expired, suspended, or cancelled.',
        ],
        'expires_at' => [
            'required' => 'Expiration date is required.',
            'date' => 'Expiration date must be a valid date.',
            'after' => 'Expiration date must be after today.',
        ],
        'max_properties' => [
            'required' => 'Maximum properties value is required.',
            'integer' => 'Maximum properties must be a number.',
            'min' => 'Maximum properties must be at least 1.',
        ],
        'max_tenants' => [
            'required' => 'Maximum tenants value is required.',
            'integer' => 'Maximum tenants must be a number.',
            'min' => 'Maximum tenants must be at least 1.',
        ],
        'reason' => [
            'required' => 'Suspension reason is required.',
            'string' => 'Suspension reason must be text.',
            'max' => 'Suspension reason may not exceed 500 characters.',
        ],
    ],
];
