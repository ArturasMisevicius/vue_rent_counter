<?php

declare(strict_types=1);

return [
    'labels' => [
        'user' => 'User',
        'users' => 'Users',
        'name' => 'Name',
        'email' => 'Email',
        'password' => 'Password',
        'password_confirmation' => 'Confirm Password',
        'role' => 'Role',
        'organization_name' => 'Organization Name',
        'properties' => 'Properties',
        'is_active' => 'Active',
    ],

    'validation' => [
        'name' => [
            'required' => 'The name is required.',
            'max' => 'The name cannot exceed 255 characters.',
        ],
        'email' => [
            'required' => 'The email is required.',
            'email' => 'The email must be a valid email address.',
            'unique' => 'This email is already registered.',
            'max' => 'The email cannot exceed 255 characters.',
        ],
        'password' => [
            'required' => 'The password is required.',
            'min' => 'Password must be at least 8 characters.',
            'confirmed' => 'Password confirmation does not match.',
        ],
        'password_confirmation' => [
            'required' => 'Password confirmation is required.',
        ],
        'role' => [
            'required' => 'The role is required.',
            'enum' => 'The selected role is invalid.',
        ],
        'organization_name' => [
            'required' => 'Organization name is required for admin users.',
            'max' => 'Organization name cannot exceed 255 characters.',
        ],
        'properties' => [
            'required' => 'Property assignment is required for tenant users.',
            'exists' => 'The selected property does not exist.',
        ],
        'is_active' => [
            'boolean' => 'Account status must be active or inactive.',
        ],
    ],
];
