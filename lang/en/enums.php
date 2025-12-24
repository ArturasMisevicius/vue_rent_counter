<?php

declare(strict_types=1);

return [
    'area_type' => [
        'commercial_area' => 'Commercial Area',
        'heated_area' => 'Heated Area',
        'total_area' => 'Total Area',
    ],
    'distribution_method' => [
        'area' => 'Area-Based',
        'area_description' => 'Distribute costs proportionally based on property area (square meters)',
        'by_consumption' => 'Consumption-Based',
        'by_consumption_description' => 'Distribute costs based on actual consumption ratios from historical data',
        'custom_formula' => 'Custom Formula',
        'custom_formula_description' => 'Use custom mathematical formulas for flexible distribution scenarios',
        'equal' => 'Equal Distribution',
        'equal_description' => 'Distribute costs equally among all properties regardless of size or consumption',
    ],
    'input_method' => [
        'api_integration_description' => 'Api Integration Description',
        'csv_import_description' => 'Csv Import Description',
        'estimated_description' => 'Estimated Description',
        'manual_description' => 'Manual Description',
        'photo_ocr_description' => 'Photo Ocr Description',
    ],
    'pricing_model' => [
        'consumption_based_description' => 'Charges based on actual utility consumption with per-unit rates',
        'custom_formula_description' => 'Uses custom mathematical formulas for complex pricing scenarios',
        'fixed_monthly_description' => 'Fixed monthly charge regardless of consumption',
        'flat_description' => 'Simple flat rate pricing (legacy compatibility)',
        'hybrid_description' => 'Combines fixed monthly charges with consumption-based rates',
        'tiered_rates_description' => 'Progressive rates that increase with higher consumption levels',
        'time_of_use_description' => 'Different rates based on time of day, day of week, or season',
    ],
    'service_type' => [
        'electricity' => 'Electricity',
        'heating' => 'Heating',
        'water' => 'Water',
        'gas' => 'Gas',
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
    'super_admin_audit_action' => [
        'backup_created' => 'Backup Created',
        'backup_restored' => 'Backup Restored',
        'bulk_operation' => 'Bulk Operation',
        'feature_flag_changed' => 'Feature Flag Changed',
        'impersonation_ended' => 'Impersonation Ended',
        'notification_sent' => 'Notification Sent',
        'security_policy_changed' => 'Security Policy Changed',
        'system_config_changed' => 'System Config Changed',
        'system_tenant_activated' => 'System Tenant Activated',
        'system_tenant_created' => 'System Tenant Created',
        'system_tenant_deleted' => 'System Tenant Deleted',
        'system_tenant_suspended' => 'System Tenant Suspended',
        'system_tenant_updated' => 'System Tenant Updated',
        'user_impersonated' => 'User Impersonated',
    ],
    'system_subscription_plan' => [
        'custom' => 'Custom',
        'enterprise' => 'Enterprise',
        'professional' => 'Professional',
        'starter' => 'Starter',
    ],
    'system_tenant_status' => [
        'active' => 'Active',
        'cancelled' => 'Cancelled',
        'pending' => 'Pending',
        'suspended' => 'Suspended',
    ],
    'user_role' => [
        'superadmin' => 'Super Admin',
        'admin' => 'Admin',
        'manager' => 'Manager',
        'tenant' => 'Tenant',
    ],
    'validation_status' => [
        'pending_description' => 'Pending Description',
        'rejected_description' => 'Rejected Description',
        'requires_review_description' => 'Requires Review Description',
        'validated_description' => 'Validated Description',
    ],
];
