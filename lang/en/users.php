<?php

declare(strict_types=1);

return [
    'actions' => [
        'add' => 'Add',
        'back' => 'Back',
        'clear' => 'Clear',
        'create' => 'Create',
        'delete' => 'Delete',
        'edit' => 'Edit',
        'filter' => 'Filter',
        'update' => 'Update',
        'view' => 'View',
    ],
    'descriptions' => [
        'index' => 'Index',
    ],
    'empty' => [
        'users' => 'Users',
    ],
    'empty_state' => [
        'description' => 'Description',
        'heading' => 'Heading',
    ],
    'errors' => [
        'has_readings' => 'Has Readings',
    ],
    'filters' => [
        'active_only' => 'Active Only',
        'all_users' => 'All Users',
        'inactive_only' => 'Inactive Only',
        'is_active' => 'Is Active',
        'role' => 'Role',
    ],
    'headings' => [
        'create' => 'Create',
        'edit' => 'Edit',
        'index' => 'Index',
        'information' => 'Information',
        'quick_actions' => 'Quick Actions',
        'show' => 'Show',
    ],
    'helper_text' => [
        'is_active' => 'Is Active',
        'password' => 'Password',
        'role' => 'Role',
        'tenant' => 'Tenant',
    ],
    'labels' => [
        'activity_hint' => 'Activity Hint',
        'activity_history' => 'Activity History',
        'created' => 'Created',
        'created_at' => 'Created At',
        'email' => 'Email',
        'is_active' => 'Is Active',
        'last_login_at' => 'Last Login At',
        'meter_readings_entered' => 'Meter Readings Entered',
        'name' => 'Name',
        'no_activity' => 'No Activity',
        'password' => 'Password',
        'password_confirmation' => 'Password Confirmation',
        'role' => 'Role',
        'tenant' => 'Tenant',
        'updated_at' => 'Updated At',
        'user' => 'User',
        'users' => 'Users',
    ],
    'placeholders' => [
        'email' => 'Email',
        'name' => 'Name',
        'password' => 'Password',
        'password_confirmation' => 'Password Confirmation',
    ],
    'sections' => [
        'role_and_access' => 'Role And Access',
        'role_and_access_description' => 'Role And Access Description',
        'user_details' => 'User Details',
        'user_details_description' => 'User Details Description',
    ],
    'tables' => [
        'actions' => 'Actions',
        'email' => 'Email',
        'name' => 'Name',
        'role' => 'Role',
        'tenant' => 'Tenant',
    ],
    'tooltips' => [
        'copy_email' => 'Copy Email',
    ],
    'validation' => [
        'current_password' => [
            'current_password' => 'Current Password',
            'required' => 'Required',
            'required_with' => 'Required With',
            'string' => 'String',
        ],
        'email' => [
            'email' => 'Email',
            'max' => 'Max',
            'required' => 'Required',
            'string' => 'String',
            'unique' => 'Unique',
        ],
        'name' => [
            'max' => 'Max',
            'required' => 'Required',
            'string' => 'String',
        ],
        'organization_name' => [
            'max' => 'Max',
            'string' => 'String',
        ],
        'password' => [
            'confirmed' => 'Confirmed',
            'min' => 'Min',
            'required' => 'Required',
            'string' => 'String',
        ],
        'role' => [
            'enum' => 'Enum',
            'required' => 'Required',
        ],
        'tenant_id' => [
            'exists' => 'Exists',
            'integer' => 'Integer',
            'required' => 'Required',
        ],
    ],
];
