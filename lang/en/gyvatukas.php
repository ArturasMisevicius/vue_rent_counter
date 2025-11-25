<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Gyvatukas Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines are used for gyvatukas (circulation fee)
    | calculations and related validation messages.
    |
    */

    'attributes' => [
        'building' => 'building',
        'billing_month' => 'billing month',
        'distribution_method' => 'distribution method',
    ],

    'validation' => [
        'building_required' => 'Building is required.',
        'building_not_found' => 'Building not found.',
        'no_properties' => 'Building must have at least one property.',
        'unauthorized_building' => 'You are not authorized to calculate for this building.',
        
        'billing_month_required' => 'Billing month is required.',
        'billing_month_invalid' => 'Billing month must be a valid date.',
        'billing_month_future' => 'Billing month cannot be in the future.',
        'billing_month_too_old' => 'Billing month is too far in the past.',
        
        'distribution_method_invalid' => 'Distribution method must be either "equal" or "area".',
    ],

    'errors' => [
        'unauthorized' => 'You are not authorized to perform this calculation.',
        'rate_limit_exceeded' => 'Too many calculations. Please try again later.',
        'invalid_configuration' => 'Invalid gyvatukas configuration. Please contact support.',
        'calculation_failed' => 'Calculation failed. Please try again.',
    ],

    'messages' => [
        'calculation_complete' => 'Gyvatukas calculation completed successfully.',
        'audit_created' => 'Calculation audit record created.',
    ],
];
