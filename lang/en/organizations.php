<?php

declare(strict_types=1);

return [
    'navigation' => 'Organizations',

    'sections' => [
        'details' => 'Organization Details',
        'subscription' => 'Subscription & Limits',
        'regional' => 'Regional Settings',
        'status' => 'Status',
    ],

    'labels' => [
        'name' => 'Name',
        'slug' => 'Slug',
        'email' => 'Email',
        'phone' => 'Phone',
        'domain' => 'Domain',
        'plan' => 'Plan',
        'max_properties' => 'Max Properties',
        'max_users' => 'Max Users',
        'trial_end' => 'Trial End Date',
        'subscription_end' => 'Subscription End Date',
        'timezone' => 'Timezone',
        'locale' => 'Locale',
        'currency' => 'Currency',
        'is_active' => 'Active',
        'suspended_at' => 'Suspended At',
        'suspension_reason' => 'Suspension Reason',
        'users' => 'Users',
        'properties' => 'Properties',
        'subscription_status' => 'Subscription Status',
        'expired_subscriptions' => 'Expired Subscriptions',
        'expiring_soon' => 'Expiring Soon (14 days)',
        'analytics' => 'Analytics',
        'new_plan' => 'New Plan',
        'reason' => 'Reason',
        'created_at' => 'Created At',
        'max_users' => 'Max Users',
        'timezone' => 'Timezone',
        'locale' => 'Locale',
        'currency' => 'Currency',
        'total_users' => 'Total Users',
        'total_properties' => 'Total Properties',
        'total_buildings' => 'Total Buildings',
        'total_invoices' => 'Total Invoices',
        'remaining_properties' => 'Remaining Properties',
        'remaining_users' => 'Remaining Users',
        'not_on_trial' => 'Not on trial',
        'not_suspended' => 'Not suspended',
    ],

    'helper_text' => [
        'slug' => 'Auto-generated from name, but can be customized',
        'domain' => 'Custom domain for this organization (optional)',
        'trial' => 'Leave empty if not on trial',
        'subscription_end' => 'Subscription end date',
        'inactive' => 'Inactive organizations cannot access the system',
        'suspended_at' => 'Set automatically when suspended',
        'suspension_reason' => 'Reason for suspension',
        'impersonation_reason' => 'This will be logged in the audit trail',
        'change_plan' => 'Resource limits will be updated automatically',
    ],

    'filters' => [
        'active_placeholder' => 'All organizations',
        'active_only' => 'Active only',
        'inactive_only' => 'Inactive only',
    ],

    'actions' => [
        'suspend_selected' => 'Suspend Selected',
        'reactivate_selected' => 'Reactivate Selected',
        'export_selected' => 'Export Selected',
        'change_plan' => 'Change Plan',
        'analytics' => 'Analytics',
        'impersonate' => 'Impersonate Organization Admin',
        'suspend' => 'Suspend',
        'reactivate' => 'Reactivate',
    ],

    'modals' => [
        'impersonate_heading' => 'Impersonate Organization Admin',
        'impersonate_description' => 'You will be logged in as this organization\'s admin. All actions will be logged.',
        'impersonation_reason' => 'Reason for Impersonation',
        'no_admin' => 'No admin user found',
        'impersonation_started' => 'Impersonation started',
    ],

    'notifications' => [
        'bulk_suspended' => 'Suspended :count organizations',
        'bulk_reactivated' => 'Reactivated :count organizations',
        'bulk_updated' => 'Updated :count organizations',
        'bulk_failed_suffix' => ', :count failed',
    ],

    'relations' => [
        'properties' => [
            'building' => 'Building',
            'area' => 'Area (m²)',
            'tenants' => 'Tenants',
            'meters' => 'Meters',
            'empty_heading' => 'No properties yet',
            'empty_description' => 'Properties will appear here when created',
        ],
        'users' => [
            'active' => 'Active',
            'empty_heading' => 'No users yet',
            'empty_description' => 'Create a user for this organization',
        ],
        'subscriptions' => [
            'plan' => 'Plan',
            'start' => 'Start Date',
            'expiry' => 'Expiry Date',
            'properties_limit' => 'Properties Limit',
            'tenants_limit' => 'Tenants Limit',
            'empty_heading' => 'No subscription history',
            'empty_description' => 'Subscription records will appear here',
        ],
        'activity_logs' => [
            'time' => 'Time',
            'user' => 'User',
            'resource' => 'Resource',
            'id' => 'ID',
            'ip' => 'IP',
            'details' => 'Details',
            'modal_heading' => 'Activity Details',
            'empty_heading' => 'No activity logs',
            'empty_description' => 'Activity logs will appear here',
        ],
    ],
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
