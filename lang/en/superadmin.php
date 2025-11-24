<?php

declare(strict_types=1);

return [
    'dashboard' => [
        'title' => 'Superadmin Dashboard',
        'subtitle' => 'System-wide statistics and organization management',
        
        'stats' => [
            'total_subscriptions' => 'Total Subscriptions',
            'active_subscriptions' => 'Active Subscriptions',
            'expired_subscriptions' => 'Expired Subscriptions',
            'suspended_subscriptions' => 'Suspended Subscriptions',
            'cancelled_subscriptions' => 'Cancelled Subscriptions',
            'total_properties' => 'Total Properties',
            'total_buildings' => 'Total Buildings',
            'total_tenants' => 'Total Tenants',
            'total_invoices' => 'Total Invoices',
        ],
        'stats_descriptions' => [
            'total_subscriptions' => 'All subscriptions in the system',
            'active_subscriptions' => 'Currently active',
            'expired_subscriptions' => 'Require renewal',
            'suspended_subscriptions' => 'Temporarily suspended',
            'cancelled_subscriptions' => 'Permanently cancelled',
            'total_organizations' => 'All organizations in the system',
            'active_organizations' => 'Currently active',
            'inactive_organizations' => 'Suspended or inactive',
        ],
        
        'organizations' => [
            'title' => 'Organizations',
            'total' => 'Total Organizations',
            'active' => 'Active Organizations',
            'inactive' => 'Inactive Organizations',
            'view_all' => 'View all organizations →',
            'top_by_properties' => 'Top Organizations by Properties',
            'properties_count' => 'properties',
            'no_organizations' => 'No organizations yet',
        ],
        
        'subscription_plans' => [
            'title' => 'Subscription Plans',
            'basic' => 'Basic',
            'professional' => 'Professional',
            'enterprise' => 'Enterprise',
            'view_all' => 'View all subscriptions →',
        ],
        
        'expiring_subscriptions' => [
            'title' => 'Expiring Subscriptions',
            'alert' => ':count subscription(s) expiring within 14 days',
            'expires' => 'Expires:',
        ],

        'organizations_widget' => [
            'total' => 'Total Organizations',
            'active' => 'Active Organizations',
            'inactive' => 'Inactive Organizations',
            'new_this_month' => 'New This Month',
            'growth_up' => '↑ :value% from last month',
            'growth_down' => '↓ :value% from last month',
        ],
        
        'recent_activity' => [
            'title' => 'Recent Admin Activity',
            'last_activity' => 'Last activity:',
            'no_activity' => 'No activity yet',
            'created_header' => 'Created',
        ],
        'recent_activity_widget' => [
            'heading' => 'Recent Activity',
            'description' => 'Last 10 actions across all organizations',
            'empty_heading' => 'No recent activity',
            'empty_description' => 'Activity logs will appear here',
            'default_system' => 'System',
            'columns' => [
                'time' => 'Time',
                'user' => 'User',
                'organization' => 'Organization',
                'action' => 'Action',
                'resource' => 'Resource',
                'id' => 'ID',
                'details' => 'Details',
            ],
            'modal_heading' => 'Activity Details',
        ],
        
        'quick_actions' => [
            'title' => 'Quick Actions',
            'create_organization' => 'Create New Organization',
            'manage_organizations' => 'Manage Organizations',
            'manage_subscriptions' => 'Manage Subscriptions',
        ],

        'overview' => [
            'subscriptions' => [
                'title' => 'Subscriptions overview',
                'description' => 'Recent subscriptions backing the widget totals',
                'open' => 'Open subscriptions',
                'headers' => [
                    'organization' => 'Organization',
                    'plan' => 'Plan',
                    'status' => 'Status',
                    'expires' => 'Expires',
                    'manage' => 'Manage',
                ],
                'empty' => 'No subscriptions yet',
            ],
            'organizations' => [
                'title' => 'Organizations overview',
                'description' => 'Latest organizations contributing to counts',
                'open' => 'Open organizations',
                'headers' => [
                    'organization' => 'Organization',
                    'subscription' => 'Subscription',
                    'status' => 'Status',
                    'created' => 'Created',
                    'manage' => 'Manage',
                ],
                'no_subscription' => 'No subscription',
                'status_active' => 'Active',
                'status_inactive' => 'Inactive',
                'empty' => 'No organizations yet',
            ],
            'resources' => [
                'title' => 'System resources',
                'description' => 'Latest records that make up the resource widgets',
                'manage_orgs' => 'Manage organizations',
                'properties' => [
                    'title' => 'Properties',
                    'open_owners' => 'Open owners',
                    'building' => 'Building',
                    'organization' => 'Organization',
                    'unknown_org' => 'Unknown',
                    'empty' => 'No properties found',
                ],
                'buildings' => [
                    'title' => 'Buildings',
                    'open_owners' => 'Open owners',
                    'address' => 'Address',
                    'organization' => 'Organization',
                    'manage' => 'Manage',
                    'empty' => 'No buildings found',
                ],
                'tenants' => [
                    'title' => 'Tenants',
                    'open_owners' => 'Open owners',
                    'property' => 'Property',
                    'not_assigned' => 'Not assigned',
                    'organization' => 'Organization',
                    'status_active' => 'Active',
                    'status_inactive' => 'Inactive',
                    'empty' => 'No tenants found',
                ],
                'invoices' => [
                    'title' => 'Invoices',
                    'open_owners' => 'Open owners',
                    'amount' => 'Amount',
                    'status' => 'Status',
                    'organization' => 'Organization',
                    'manage' => 'Manage',
                    'empty' => 'No invoices found',
                ],
            ],
        ],

        'organizations_list' => [
            'expires' => 'Expires:',
            'no_subscription' => 'No subscription',
            'status_active' => 'Active',
            'status_inactive' => 'Inactive',
            'actions' => [
                'view' => 'View',
                'edit' => 'Edit',
            ],
            'empty' => 'No organizations found',
        ],

        'organization_show' => [
            'status' => 'Status',
            'created' => 'Created',
            'start_date' => 'Start Date',
            'expiry_date' => 'Expiry Date',
            'limits' => 'Limits',
            'limit_values' => ':properties properties, :tenants tenants',
            'manage_subscription' => 'Manage Subscription →',
            'no_subscription' => 'No subscription found',
            'stats' => [
                'properties' => 'Properties',
                'buildings' => 'Buildings',
                'tenants' => 'Tenants',
                'active_tenants' => 'Active Tenants',
                'invoices' => 'Invoices',
                'meters' => 'Meters',
            ],
        ],
    ],
];
