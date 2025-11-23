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
            'total_properties' => 'Total Properties',
            'total_buildings' => 'Total Buildings',
            'total_tenants' => 'Total Tenants',
            'total_invoices' => 'Total Invoices',
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
        
        'recent_activity' => [
            'title' => 'Recent Admin Activity',
            'last_activity' => 'Last activity:',
            'no_activity' => 'No activity yet',
        ],
        
        'quick_actions' => [
            'title' => 'Quick Actions',
            'create_organization' => 'Create New Organization',
            'manage_organizations' => 'Manage Organizations',
            'manage_subscriptions' => 'Manage Subscriptions',
        ],
    ],
];
