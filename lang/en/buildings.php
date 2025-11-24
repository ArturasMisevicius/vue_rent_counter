<?php

declare(strict_types=1);

return [
    'labels' => [
        'building' => 'Building',
        'buildings' => 'Buildings',
        'name' => 'Building Name',
        'address' => 'Address',
        'total_apartments' => 'Total Apartments',
        'total_area' => 'Total Area',
    ],

    'validation' => [
        'tenant_id' => [
            'required' => 'Tenant is required.',
            'integer' => 'Tenant identifier must be a valid number.',
        ],
        'name' => [
            'required' => 'The building name is required.',
            'string' => 'The building name must be text.',
            'max' => 'The building name may not be greater than 255 characters.',
        ],
        'address' => [
            'required' => 'The building address is required.',
            'string' => 'The building address must be text.',
            'max' => 'The building address may not be greater than 255 characters.',
        ],
        'total_apartments' => [
            'required' => 'The total number of apartments is required.',
            'numeric' => 'The total number of apartments must be a whole number.',
            'integer' => 'The total number of apartments must be a whole number.',
            'min' => 'The building must have at least 1 apartment.',
            'max' => 'The building cannot have more than 1,000 apartments.',
        ],
        'total_area' => [
            'required' => 'The total area is required.',
            'numeric' => 'The total area must be a number.',
            'min' => 'The total area must be at least 0.',
        ],
        'gyvatukas' => [
            'start_date' => [
                'required' => 'Start date is required to calculate the gyvatukas average.',
                'date' => 'Start date must be a valid date.',
            ],
            'end_date' => [
                'required' => 'End date is required to calculate the gyvatukas average.',
                'date' => 'End date must be a valid date.',
                'after' => 'End date must be after the start date.',
            ],
        ],
    ],

    'errors' => [
        'has_properties' => 'Cannot delete building with associated properties.',
    ],
];
