<?php

declare(strict_types=1);

return [
    'headings' => [
        'index' => 'Users Management',
        'create' => 'Create User',
        'edit' => 'Edit User',
        'show' => 'User Details',
        'information' => 'User Information',
        'quick_actions' => 'Quick Actions',
    ],

    'descriptions' => [
        'index' => 'Manage user accounts and roles',
    ],

    'actions' => [
        'add' => 'Add User',
        'create' => 'Create User',
        'edit' => 'Edit User',
        'update' => 'Update User',
        'delete' => 'Delete',
        'view' => 'View',
        'back' => 'Back',
        'filter' => 'Filter',
        'clear' => 'Clear',
    ],

    'tables' => [
        'name' => 'Name',
        'email' => 'Email',
        'role' => 'Role',
        'tenant' => 'Tenant',
        'actions' => 'Actions',
    ],

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
        'created_at' => 'Created',
        'updated_at' => 'Last Updated',
        'activity_history' => 'Activity History',
        'meter_readings_entered' => 'Meter readings entered',
        'no_activity' => 'No activity recorded.',
        'activity_hint' => 'This user has entered :count meter readings.',
    ],

    'validation' => [
        'name' => [
            'required' => 'The name is required.',
            'string' => 'The name must be text.',
            'max' => 'The name cannot exceed 255 characters.',
        ],
        'email' => [
            'required' => 'The email is required.',
            'string' => 'The email must be text.',
            'email' => 'The email must be a valid email address.',
            'unique' => 'This email is already registered.',
            'max' => 'The email cannot exceed 255 characters.',
        ],
        'password' => [
            'required' => 'The password is required.',
            'string' => 'The password must be text.',
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
            'string' => 'Organization name must be text.',
            'max' => 'Organization name cannot exceed 255 characters.',
        ],
        'properties' => [
            'required' => 'Property assignment is required for tenant users.',
            'exists' => 'The selected property does not exist.',
        ],
        'is_active' => [
            'boolean' => 'Account status must be active or inactive.',
        ],
        'tenant_id' => [
            'required' => 'Tenant is required.',
            'integer' => 'Tenant identifier must be numeric.',
            'exists' => 'The selected tenant does not exist.',
        ],
        'current_password' => [
            'required' => 'Current password is required.',
            'required_with' => 'Current password is required when setting a new password.',
            'string' => 'Current password must be text.',
            'current_password' => 'Current password is incorrect.',
        ],
    ],

    'errors' => [
        'has_readings' => 'Cannot delete user with associated meter readings.',
    ],

    'empty' => [
        'users' => 'No users found.',
    ],
];
