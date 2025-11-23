<?php

declare(strict_types=1);

return [
    'labels' => [
        'building' => 'Building',
        'buildings' => 'Buildings',
        'address' => 'Address',
        'total_apartments' => 'Total Apartments',
        'total_area' => 'Total Area',
    ],

    'validation' => [
        'address' => [
            'required' => 'The building address is required.',
            'max' => 'The building address may not be greater than 255 characters.',
        ],
        'total_apartments' => [
            'required' => 'The total number of apartments is required.',
            'numeric' => 'The total number of apartments must be a whole number.',
            'integer' => 'The total number of apartments must be a whole number.',
            'min' => 'The total number of apartments must be at least 0.',
        ],
        'total_area' => [
            'required' => 'The total area is required.',
            'numeric' => 'The total area must be a number.',
            'min' => 'The total area must be at least 0.',
        ],
    ],
];
