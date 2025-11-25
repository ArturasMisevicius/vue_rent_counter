<?php

return [
    'errors' => [
        'cross_tenant_access_denied' => 'You do not have permission to access this tenant.',
        'tenant_inactive' => 'The tenant account is inactive.',
        'authentication_required' => 'Authentication is required for this operation.',
        'rate_limit_user' => 'You have exceeded the invoice generation limit. Please try again in an hour.',
        'rate_limit_tenant' => 'The tenant has exceeded the invoice generation limit. Please try again later.',
        'duplicate_invoice' => 'An invoice already exists for this period.',
        'tenant_no_property' => 'The tenant has no associated property.',
        'property_no_meters' => 'The property has no meters configured.',
        'provider_not_found' => 'No provider found for this service type.',
        'negative_consumption' => 'Invalid negative consumption detected for meter :meter.',
        'excessive_consumption' => 'Unusually high consumption detected for meter :meter.',
    ],
    
    'validation' => [
        'tenant_required' => 'Tenant is required.',
        'tenant_not_found' => 'Tenant not found.',
        'tenant_inactive' => 'Tenant is inactive or deleted.',
        'period_start_required' => 'Period start date is required.',
        'period_start_future' => 'Period start date cannot be in the future.',
        'period_end_required' => 'Period end date is required.',
        'period_end_future' => 'Period end date cannot be in the future.',
        'period_too_long' => 'Billing period cannot exceed 3 months.',
        'duplicate_invoice' => 'An invoice already exists for this period.',
    ],
    
    'fields' => [
        'tenant' => 'Tenant',
        'period_start' => 'Period Start',
        'period_end' => 'Period End',
    ],
    
    'audit' => [
        'invoice_generated' => 'Invoice generated successfully',
        'invoice_finalized' => 'Invoice finalized',
    ],
];
