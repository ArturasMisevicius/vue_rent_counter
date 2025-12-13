<?php

return [
    'meter_type' => [
        'electricity' => 'Electricity',
        'water_cold' => 'Cold Water',
        'water_hot' => 'Hot Water',
        'heating' => 'Heating',
    ],

    'property_type' => [
        'apartment' => 'Apartment',
        'house' => 'House',
    ],

    'service_type' => [
        'electricity' => 'Electricity',
        'water' => 'Water',
        'heating' => 'Heating',
    ],

    'invoice_status' => [
        'draft' => 'Draft',
        'finalized' => 'Finalized',
        'paid' => 'Paid',
    ],

    'user_role' => [
        'superadmin' => 'Super Admin',
        'admin' => 'Admin',
        'manager' => 'Manager',
        'tenant' => 'Tenant',
    ],

    'tariff_type' => [
        'flat' => 'Flat Rate',
        'time_of_use' => 'Time of Use',
    ],

    'tariff_zone' => [
        'day' => 'Day Rate',
        'night' => 'Night Rate',
        'weekend' => 'Weekend Rate',
    ],

    'weekend_logic' => [
        'apply_night_rate' => 'Apply Night Rate on Weekends',
        'apply_day_rate' => 'Apply Day Rate on Weekends',
        'apply_weekend_rate' => 'Apply Special Weekend Rate',
    ],

    'subscription_plan_type' => [
        'basic' => 'Basic',
        'professional' => 'Professional',
        'enterprise' => 'Enterprise',
    ],

    'subscription_status' => [
        'active' => 'Active',
        'expired' => 'Expired',
        'suspended' => 'Suspended',
        'cancelled' => 'Cancelled',
    ],

    'user_assignment_action' => [
        'created' => 'Created',
        'assigned' => 'Assigned',
        'reassigned' => 'Reassigned',
        'deactivated' => 'Deactivated',
        'reactivated' => 'Reactivated',
    ],

    'system_tenant_status' => [
        'active' => 'Active',
        'suspended' => 'Suspended',
        'pending' => 'Pending',
        'cancelled' => 'Cancelled',
    ],

    'system_subscription_plan' => [
        'starter' => 'Starter',
        'professional' => 'Professional',
        'enterprise' => 'Enterprise',
        'custom' => 'Custom',
    ],

    'super_admin_audit_action' => [
        'system_tenant_created' => 'System Tenant Created',
        'system_tenant_updated' => 'System Tenant Updated',
        'system_tenant_suspended' => 'System Tenant Suspended',
        'system_tenant_activated' => 'System Tenant Activated',
        'system_tenant_deleted' => 'System Tenant Deleted',
        'user_impersonated' => 'User Impersonated',
        'impersonation_ended' => 'Impersonation Ended',
        'bulk_operation' => 'Bulk Operation',
        'system_config_changed' => 'System Configuration Changed',
        'backup_created' => 'Backup Created',
        'backup_restored' => 'Backup Restored',
        'notification_sent' => 'Notification Sent',
        'feature_flag_changed' => 'Feature Flag Changed',
        'security_policy_changed' => 'Security Policy Changed',
    ],
];
