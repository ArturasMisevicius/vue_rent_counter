<?php

declare(strict_types=1);

return [
    'area_type' => [
        'commercial_area' => 'Commercial Area',
        'heated_area' => 'Heated Area',
        'total_area' => 'Total Area',
    ],
    'distribution_method' => [
        'area' => 'Area',
        'area_description' => 'Area Description',
        'by_consumption' => 'By Consumption',
        'by_consumption_description' => 'By Consumption Description',
        'custom_formula' => 'Custom Formula',
        'custom_formula_description' => 'Custom Formula Description',
        'equal' => 'Equal',
        'equal_description' => 'Equal Description',
    ],
    'gyvatukas_calculation_type' => [
        'summer' => 'Summer',
        'winter' => 'Winter',
    ],
    'input_method' => [
        'api_integration_description' => 'Api Integration Description',
        'csv_import_description' => 'Csv Import Description',
        'estimated_description' => 'Estimated Description',
        'manual_description' => 'Manual Description',
        'photo_ocr_description' => 'Photo Ocr Description',
    ],
    'pricing_model' => [
        'consumption_based_description' => 'Consumption Based Description',
        'custom_formula_description' => 'Custom Formula Description',
        'fixed_monthly_description' => 'Fixed Monthly Description',
        'flat_description' => 'Flat Description',
        'hybrid_description' => 'Hybrid Description',
        'tiered_rates_description' => 'Tiered Rates Description',
        'time_of_use_description' => 'Time Of Use Description',
    ],
    'service_type' => [
        'electricity' => 'Electricity',
        'heating' => 'Heating',
        'water' => 'Water',
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
