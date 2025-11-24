<?php

declare(strict_types=1);

return [
    'labels' => [
        'organization' => 'Organization',
        'email' => 'Email',
        'contact_name' => 'Contact Name',
        'plan_type' => 'Plan Type',
        'status' => 'Status',
        'starts_at' => 'Start Date',
        'expires_at' => 'Expiration Date',
        'days_left' => 'Days Left',
        'days_until_expiry' => 'Days Until Expiry',
        'max_properties' => 'Max Properties',
        'max_tenants' => 'Max Tenants',
        'properties_limit' => 'Properties Limit',
        'tenants_limit' => 'Tenants Limit',
        'properties_used' => 'Properties Used',
        'properties_remaining' => 'Properties Remaining',
        'tenants_used' => 'Tenants Used',
        'tenants_remaining' => 'Tenants Remaining',
        'usage' => 'Usage',
        'new_expiration_date' => 'New Expiration Date',
        'renewal_duration' => 'Renewal Duration',
        'created_at' => 'Created At',
        'updated_at' => 'Updated At',
    ],

    'sections' => [
        'details' => 'Subscription Details',
        'period' => 'Subscription Period',
        'limits' => 'Limits',
        'usage' => 'Usage Statistics',
        'timestamps' => 'Timestamps',
    ],

    'helper_text' => [
        'select_organization' => 'Select the organization for this subscription',
        'max_properties' => 'Maximum number of properties allowed',
        'max_tenants' => 'Maximum number of tenants allowed',
    ],

    'filters' => [
        'plan_type' => 'Plan Type',
        'status' => 'Status',
        'expiring_soon' => 'Expiring Soon (14 days)',
        'expired' => 'Expired',
    ],

    'actions' => [
        'renew' => 'Renew',
        'suspend' => 'Suspend',
        'activate' => 'Activate',
        'renew_selected' => 'Renew Selected',
        'suspend_selected' => 'Suspend Selected',
        'activate_selected' => 'Activate Selected',
        'export_selected' => 'Export Selected',
        'send_reminder' => 'Send Reminder',
        'view_usage' => 'Usage',
        'close' => 'Close',
        'subscription_usage' => 'Subscription Usage',
        'view' => 'View',
    ],

    'options' => [
        'duration' => [
            '1_month' => '1 Month',
            '3_months' => '3 Months',
            '6_months' => '6 Months',
            '1_year' => '1 Year',
        ],
    ],

    'notifications' => [
        'renewed' => 'Subscription renewed successfully',
        'reminder_sent' => 'Renewal reminder sent',
        'suspended' => 'Subscription suspended successfully',
        'activated' => 'Subscription activated successfully',
        'bulk_renewed' => 'Renewed :count subscriptions',
        'bulk_suspended' => 'Suspended :count subscriptions',
        'bulk_activated' => 'Activated :count subscriptions',
        'bulk_failed_suffix' => ', :count failed',
    ],

    'widgets' => [
        'expiring_heading' => 'Subscriptions Expiring Soon (14 Days)',
        'expiring_description' => 'Active subscriptions that will expire within the next 14 days',
        'expiring_empty_heading' => 'No expiring subscriptions',
        'expiring_empty_description' => 'All subscriptions are valid for more than 14 days',
    ],

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
