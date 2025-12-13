<?php

declare(strict_types=1);

return [
    'attributes' => [
        'billing_month' => 'Billing Month',
        'building' => 'Building',
        'distribution_method' => 'Distribution Method',
    ],
    'validation' => [
        'billing_month_future' => 'Billing Month Future',
        'billing_month_invalid' => 'Billing Month Invalid',
        'billing_month_required' => 'Billing Month Required',
        'billing_month_too_old' => 'Billing Month Too Old',
        'building_not_found' => 'Building Not Found',
        'building_required' => 'Building Required',
        'distribution_method_invalid' => 'Distribution Method Invalid',
        'no_properties' => 'No Properties',
        'unauthorized_building' => 'Unauthorized Building',
    ],
];
