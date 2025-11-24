<?php

declare(strict_types=1);

return [
    'validation' => [
        'name' => [
            'required' => 'Name is required.',
            'string' => 'Name must be text.',
            'max' => 'Name may not exceed 255 characters.',
        ],
        'email' => [
            'required' => 'Email is required.',
            'string' => 'Email must be text.',
            'email' => 'Email must be a valid address.',
            'max' => 'Email may not exceed 255 characters.',
            'unique' => 'This email is already in use.',
        ],
        'password' => [
            'required' => 'Password is required.',
            'string' => 'Password must be text.',
            'min' => 'Password must be at least 8 characters.',
        ],
        'organization_name' => [
            'required' => 'Organization name is required.',
            'string' => 'Organization name must be text.',
            'max' => 'Organization name may not exceed 255 characters.',
        ],
        'plan_type' => [
            'required' => 'Plan type is required.',
            'in' => 'Plan type must be basic, professional, or enterprise.',
        ],
        'expires_at' => [
            'required' => 'Expiration date is required.',
            'date' => 'Expiration date must be a valid date.',
            'after' => 'Expiration date must be after today.',
        ],
        'is_active' => [
            'boolean' => 'Active state must be true or false.',
        ],
    ],
];
