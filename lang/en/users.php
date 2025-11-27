<?php

declare(strict_types=1);

return [
    'labels' => [
        'users' => 'Users',
        'user' => 'User',
        'name' => 'Name',
        'email' => 'Email',
        'password' => 'Password',
        'password_confirmation' => 'Confirm Password',
        'role' => 'Role',
        'tenant' => 'Organization',
        'is_active' => 'Active',
        'created' => 'Created At',
    ],

    'placeholders' => [
        'name' => 'Enter full name',
        'email' => 'user@example.com',
        'password' => 'Enter password',
        'password_confirmation' => 'Re-enter password',
    ],

    'helper_text' => [
        'password' => 'Minimum 8 characters required',
        'role' => 'Select the user role',
        'tenant' => 'Required for Manager and Tenant roles',
        'is_active' => 'Inactive users cannot log in',
    ],

    'sections' => [
        'user_details' => 'User Details',
        'user_details_description' => 'Basic information about the user',
        'role_and_access' => 'Role and Access',
        'role_and_access_description' => 'User role and organization assignment',
    ],

    'filters' => [
        'role' => 'Role',
        'is_active' => 'Status',
        'all_users' => 'All Users',
        'active_only' => 'Active Only',
        'inactive_only' => 'Inactive Only',
    ],

    'tooltips' => [
        'copy_email' => 'Email copied to clipboard',
    ],

    'empty_state' => [
        'heading' => 'No users found',
        'description' => 'Get started by creating your first user.',
    ],

    'validation' => [
        'name' => [
            'required' => 'Name is required',
            'string' => 'Name must be a valid text',
            'max' => 'Name cannot exceed 255 characters',
        ],
        'email' => [
            'required' => 'Email is required',
            'email' => 'Please provide a valid email address',
            'unique' => 'This email is already in use',
            'max' => 'Email cannot exceed 255 characters',
        ],
        'current_password' => [
            'required_with' => 'Current password is required when changing password',
            'string' => 'Current password must be valid text',
            'current_password' => 'The current password is incorrect',
        ],
        'password' => [
            'required' => 'Password is required',
            'string' => 'Password must be valid text',
            'min' => 'Password must be at least 8 characters',
            'confirmed' => 'Password confirmation does not match',
        ],
        'role' => [
            'required' => 'Role is required',
            'enum' => 'Invalid role selected',
        ],
        'tenant_id' => [
            'required' => 'Organization is required for this role',
            'integer' => 'Organization must be a valid number',
            'exists' => 'Selected organization does not exist',
        ],
    ],
];
