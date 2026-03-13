<?php

declare(strict_types=1);

return [
    'fields' => [
        'period_end' => 'Period End',
        'period_start' => 'Period Start',
        'tenant' => 'Tenant',
    ],
    'validation' => [
        'duplicate_invoice' => 'Duplicate Invoice',
        'period_end_future' => 'Period End Future',
        'period_end_required' => 'Period End Required',
        'period_start_future' => 'Period Start Future',
        'period_start_required' => 'Period Start Required',
        'period_too_long' => 'Period Too Long',
        'tenant_inactive' => 'Tenant Inactive',
        'tenant_not_found' => 'Tenant Not Found',
        'tenant_required' => 'Tenant Required',
    ],
];
