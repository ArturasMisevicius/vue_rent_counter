<?php

declare(strict_types=1);

return [
    'cluster' => [
        'label' => 'Super Admin',
        'navigation_label' => 'Super Admin',
    ],

    'tenants' => [
        'label' => 'Tenant',
        'plural_label' => 'Tenants',
        'navigation_label' => 'Tenants',
        'navigation_group' => 'Tenant Management',

        'pages' => [
            'list' => [
                'title' => 'Tenants',
            ],
            'create' => [
                'title' => 'Create Tenant',
            ],
            'view' => [
                'title' => 'Tenant: :name',
            ],
            'edit' => [
                'title' => 'Edit Tenant: :name',
            ],
        ],

        'fields' => [
            'name' => 'Name',
            'slug' => 'Slug',
            'domain' => 'Domain',
            'primary_contact_email' => 'Primary Contact Email',
            'status' => 'Status',
            'subscription_plan' => 'Subscription Plan',
            'max_users' => 'Max Users',
            'max_storage_gb' => 'Max Storage (GB)',
            'max_api_calls_per_month' => 'Max API Calls/Month',
            'current_users' => 'Current Users',
            'current_storage_gb' => 'Current Storage (GB)',
            'current_api_calls' => 'Current API Calls',
            'billing_email' => 'Billing Email',
            'billing_name' => 'Billing Name',
            'billing_address' => 'Billing Address',
            'monthly_price' => 'Monthly Price',
            'setup_fee' => 'Setup Fee',
            'billing_cycle' => 'Billing Cycle',
            'next_billing_date' => 'Next Billing Date',
            'auto_billing' => 'Auto Billing',
            'trial_ends_at' => 'Trial Ends At',
            'subscription_ends_at' => 'Subscription Ends At',
            'enforce_quotas' => 'Enforce Quotas',
            'quota_notifications' => 'Quota Notifications',
            'timezone' => 'Timezone',
            'locale' => 'Locale',
            'currency' => 'Currency',
            'allow_registration' => 'Allow Registration',
            'require_email_verification' => 'Require Email Verification',
            'maintenance_mode' => 'Maintenance Mode',
            'api_access_enabled' => 'API Access Enabled',
            'suspended_at' => 'Suspended At',
            'suspension_reason' => 'Suspension Reason',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ],

        'sections' => [
            'basic_info' => 'Basic Information',
            'subscription' => 'Subscription & Limits',
            'billing' => 'Billing Configuration',
            'quotas' => 'Resource Quotas',
            'settings' => 'Tenant Settings',
            'suspension' => 'Suspension Information',
            'status_management' => 'Status Management',
            'metadata' => 'Metadata',
            'metrics' => 'Metrics',
        ],

        'billing_cycles' => [
            'monthly' => 'Monthly',
            'quarterly' => 'Quarterly',
            'yearly' => 'Yearly',
        ],

        'help' => [
            'current_users' => 'Number of users currently registered',
            'current_storage' => 'Storage currently used by this tenant',
            'current_api_calls' => 'API calls made this month',
            'enforce_quotas' => 'Prevent exceeding resource limits',
            'quota_notifications' => 'Send notifications when approaching limits',
            'allow_registration' => 'Allow new users to register',
            'require_email_verification' => 'Require email verification for new users',
            'maintenance_mode' => 'Put tenant in maintenance mode',
            'api_access_enabled' => 'Enable API access for this tenant',
        ],

        'actions' => [
            'view' => 'View',
            'edit' => 'Edit',
            'delete' => 'Delete',
            'suspend' => 'Suspend',
            'activate' => 'Activate',
            'impersonate' => 'Impersonate',
            'view_users' => 'View Users',
            'export' => 'Export',
            'bulk_suspend' => 'Suspend Selected',
            'bulk_activate' => 'Activate Selected',
            'bulk_delete' => 'Delete Selected',
        ],

        'filters' => [
            'status' => 'Status',
            'subscription_plan' => 'Subscription Plan',
            'created_date' => 'Created Date',
        ],

        'modals' => [
            'suspend' => [
                'heading' => 'Suspend Tenant',
                'description' => 'Are you sure you want to suspend this tenant? Users will not be able to access their account.',
                'reason_label' => 'Suspension Reason',
                'reason_placeholder' => 'Enter reason for suspension...',
                'confirm' => 'Suspend Tenant',
            ],
            'activate' => [
                'heading' => 'Activate Tenant',
                'description' => 'Are you sure you want to activate this tenant?',
                'confirm' => 'Activate Tenant',
            ],
            'delete' => [
                'heading' => 'Delete Tenant',
                'description' => 'Are you sure you want to delete this tenant? This action cannot be undone.',
                'confirm' => 'Delete Tenant',
            ],
            'bulk_suspend' => [
                'heading' => 'Suspend Selected Tenants',
                'description' => 'Are you sure you want to suspend the selected tenants?',
                'reason_label' => 'Suspension Reason',
                'confirm' => 'Suspend Tenants',
            ],
        ],

        'notifications' => [
            'suspended' => 'Tenant suspended successfully',
            'activated' => 'Tenant activated successfully',
            'deleted' => 'Tenant deleted successfully',
            'bulk_suspended' => 'Selected tenants suspended successfully',
            'bulk_activated' => 'Selected tenants activated successfully',
            'bulk_deleted' => 'Selected tenants deleted successfully',
        ],

        'empty_state' => [
            'heading' => 'No tenants found',
            'description' => 'Get started by creating your first tenant.',
        ],
    ],

    'users' => [
        'label' => 'System User',
        'plural_label' => 'System Users',
        'navigation_label' => 'System Users',
        'navigation_group' => 'User Management',

        'pages' => [
            'list' => [
                'title' => 'System Users',
            ],
            'create' => [
                'title' => 'Create System User',
            ],
            'view' => [
                'title' => 'User: :name',
            ],
            'edit' => [
                'title' => 'Edit User: :name',
            ],
            'activity_report' => [
                'title' => 'Activity Report: :name',
            ],
        ],

        'fields' => [
            'name' => 'Name',
            'email' => 'Email',
            'email_verified_at' => 'Email Verified At',
            'is_super_admin' => 'Super Admin',
            'is_suspended' => 'Suspended',
            'suspension_reason' => 'Suspension Reason',
            'suspended_at' => 'Suspended At',
            'last_login_at' => 'Last Login',
            'login_count' => 'Login Count',
            'current_team_id' => 'Current Team',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
            'password' => 'Password',
            'password_confirmation' => 'Confirm Password',
        ],

        'sections' => [
            'basic_info' => 'Basic Information',
            'permissions' => 'Permissions & Access',
            'activity' => 'Activity & Sessions',
            'metadata' => 'Metadata',
        ],

        'actions' => [
            'view' => 'View',
            'edit' => 'Edit',
            'delete' => 'Delete',
            'suspend' => 'Suspend',
            'reactivate' => 'Reactivate',
            'impersonate' => 'Impersonate',
            'view_activity' => 'View Activity',
            'reset_password' => 'Reset Password',
            'send_verification' => 'Send Verification Email',
            'bulk_suspend' => 'Suspend Selected',
            'bulk_reactivate' => 'Reactivate Selected',
            'bulk_delete' => 'Delete Selected',
        ],

        'filters' => [
            'is_super_admin' => 'Super Admin',
            'is_suspended' => 'Suspended',
            'email_verified' => 'Email Verified',
            'last_login' => 'Last Login',
        ],

        'modals' => [
            'suspend' => [
                'heading' => 'Suspend User',
                'description' => 'Are you sure you want to suspend this user?',
                'reason_label' => 'Suspension Reason',
                'confirm' => 'Suspend User',
            ],
            'reactivate' => [
                'heading' => 'Reactivate User',
                'description' => 'Are you sure you want to reactivate this user?',
                'confirm' => 'Reactivate User',
            ],
            'delete' => [
                'heading' => 'Delete User',
                'description' => 'Are you sure you want to delete this user? This action cannot be undone.',
                'confirm' => 'Delete User',
            ],
            'impersonate' => [
                'heading' => 'Impersonate User',
                'description' => 'You are about to impersonate this user. All actions will be logged.',
                'confirm' => 'Start Impersonation',
            ],
        ],

        'notifications' => [
            'suspended' => 'User suspended successfully',
            'reactivated' => 'User reactivated successfully',
            'deleted' => 'User deleted successfully',
            'password_reset' => 'Password reset email sent',
            'verification_sent' => 'Verification email sent',
            'impersonation_started' => 'Impersonation started',
        ],

        'activity' => [
            'total_logins' => 'Total Logins',
            'last_login' => 'Last Login',
            'account_age' => 'Account Age',
            'teams_count' => 'Teams',
            'recent_activity' => 'Recent Activity',
            'no_activity' => 'No recent activity found',
        ],
    ],

    'audit' => [
        'label' => 'Audit Log',
        'plural_label' => 'Audit Logs',
        'navigation_label' => 'Audit Logs',
        'navigation_group' => 'System Monitoring',

        'pages' => [
            'list' => [
                'title' => 'Audit Logs',
            ],
            'view' => [
                'title' => 'Audit Log Entry',
            ],
        ],

        'fields' => [
            'admin_id' => 'Admin',
            'action' => 'Action',
            'target_type' => 'Target Type',
            'target_id' => 'Target ID',
            'changes' => 'Changes',
            'ip_address' => 'IP Address',
            'user_agent' => 'User Agent',
            'created_at' => 'Created At',
        ],

        'sections' => [
            'basic_info' => 'Basic Information',
            'changes' => 'Changes Made',
            'metadata' => 'Request Metadata',
        ],

        'actions' => [
            'view' => 'View Details',
            'export' => 'Export',
        ],

        'filters' => [
            'action' => 'Action',
            'admin' => 'Admin',
            'target_type' => 'Target Type',
            'date_range' => 'Date Range',
            'ip_address' => 'IP Address',
        ],

        'action' => [
            'tenant_created' => 'Tenant Created',
            'tenant_updated' => 'Tenant Updated',
            'tenant_suspended' => 'Tenant Suspended',
            'tenant_activated' => 'Tenant Activated',
            'tenant_deleted' => 'Tenant Deleted',
            'user_impersonated' => 'User Impersonated',
            'impersonation_ended' => 'Impersonation Ended',
            'bulk_operation' => 'Bulk Operation',
            'system_config_changed' => 'System Config Changed',
            'system_config_created' => 'System Config Created',
            'system_config_updated' => 'System Config Updated',
            'system_config_deleted' => 'System Config Deleted',
            'backup_created' => 'Backup Created',
            'backup_restored' => 'Backup Restored',
            'notification_sent' => 'Notification Sent',
            'resource_quota_changed' => 'Resource Quota Changed',
            'billing_updated' => 'Billing Updated',
            'feature_flag_changed' => 'Feature Flag Changed',
            'user_suspended' => 'User Suspended',
            'user_reactivated' => 'User Reactivated',
        ],

        'empty_state' => [
            'heading' => 'No audit logs found',
            'description' => 'Audit logs will appear here as actions are performed.',
        ],

        'changes' => [
            'no_changes' => 'No changes recorded',
            'from' => 'From',
            'to' => 'To',
        ],
    ],

    'config' => [
        'label' => 'System Configuration',
        'plural_label' => 'System Configurations',
        'navigation_label' => 'System Config',
        'navigation_group' => 'System Management',

        'pages' => [
            'list' => [
                'title' => 'System Configuration',
            ],
            'create' => [
                'title' => 'Create Configuration',
            ],
            'view' => [
                'title' => 'Configuration: :key',
            ],
            'edit' => [
                'title' => 'Edit Configuration: :key',
            ],
        ],

        'fields' => [
            'key' => 'Key',
            'category' => 'Category',
            'type' => 'Type',
            'value' => 'Value',
            'description' => 'Description',
            'is_sensitive' => 'Sensitive',
            'created_by' => 'Created By',
            'updated_by' => 'Updated By',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ],

        'sections' => [
            'basic_info' => 'Basic Information',
            'value' => 'Value Configuration',
            'metadata' => 'Metadata',
        ],

        'actions' => [
            'view' => 'View',
            'edit' => 'Edit',
            'delete' => 'Delete',
            'export' => 'Export',
        ],

        'filters' => [
            'category' => 'Category',
            'type' => 'Type',
            'is_sensitive' => 'Sensitive',
        ],

        'modals' => [
            'delete' => [
                'heading' => 'Delete Configuration',
                'description' => 'Are you sure you want to delete this configuration? This action cannot be undone.',
                'confirm' => 'Delete Configuration',
            ],
        ],

        'notifications' => [
            'created' => 'Configuration created successfully',
            'created_body' => 'Configuration ":key" has been created.',
            'updated' => 'Configuration updated successfully',
            'updated_body' => 'Configuration ":key" has been updated.',
            'deleted' => 'Configuration deleted successfully',
        ],

        'types' => [
            'string' => 'String',
            'integer' => 'Integer',
            'float' => 'Float',
            'boolean' => 'Boolean',
            'array' => 'Array',
            'json' => 'JSON',
        ],

        'categories' => [
            'general' => 'General',
            'security' => 'Security',
            'billing' => 'Billing',
            'features' => 'Features',
            'integrations' => 'Integrations',
            'notifications' => 'Notifications',
        ],

        'empty_state' => [
            'heading' => 'No configurations found',
            'description' => 'Get started by creating your first system configuration.',
        ],
    ],

    'dashboard' => [
        'title' => 'Super Admin Dashboard',
        'widgets' => [
            'tenant_overview' => [
                'title' => 'Tenant Overview',
                'total_tenants' => 'Total Tenants',
                'active_tenants' => 'Active Tenants',
                'suspended_tenants' => 'Suspended Tenants',
                'trial_tenants' => 'Trial Tenants',
            ],
            'system_metrics' => [
                'title' => 'System Metrics',
                'total_users' => 'Total Users',
                'active_sessions' => 'Active Sessions',
                'api_calls_today' => 'API Calls Today',
                'storage_used' => 'Storage Used',
            ],
            'recent_activity' => [
                'title' => 'Recent Activity',
                'no_activity' => 'No recent activity',
            ],
        ],
    ],

    'common' => [
        'actions' => [
            'save' => 'Save',
            'cancel' => 'Cancel',
            'delete' => 'Delete',
            'edit' => 'Edit',
            'view' => 'View',
            'create' => 'Create',
            'update' => 'Update',
            'search' => 'Search',
            'filter' => 'Filter',
            'export' => 'Export',
            'import' => 'Import',
            'refresh' => 'Refresh',
        ],
        'status' => [
            'active' => 'Active',
            'inactive' => 'Inactive',
            'suspended' => 'Suspended',
            'pending' => 'Pending',
            'trial' => 'Trial',
        ],
        'messages' => [
            'no_results' => 'No results found',
            'loading' => 'Loading...',
            'success' => 'Operation completed successfully',
            'error' => 'An error occurred',
        ],
    ],
];