<?php

declare(strict_types=1);

return [
    'labels' => [
        'name' => 'Name',
        'address' => 'Address',
        'total_apartments' => 'Total Apartments',
        'property_count' => 'Properties',
        'created_at' => 'Created',
    ],
    'validation' => [
        'tenant_id' => [
            'required' => 'Organization is required.',
            'integer' => 'Organization ID must be a number.',
            'exists' => 'The selected organization does not exist.',
        ],
        'name' => [
            'required' => 'Building name is required.',
            'string' => 'Building name must be text.',
            'max' => 'Building name may not be greater than 255 characters.',
            'regex' => 'Building name may only contain letters, numbers, spaces, hyphens, underscores, dots, and hash symbols.',
            'unique' => 'A building with this name already exists in your organization.',
        ],
        'address' => [
            'required' => 'Address is required.',
            'string' => 'Address must be text.',
            'max' => 'Address may not be greater than 500 characters.',
            'regex' => 'Address contains invalid characters.',
        ],
        'city' => [
            'regex' => 'City name may only contain letters, spaces, and hyphens.',
        ],
        'postal_code' => [
            'regex' => 'Postal code format is invalid.',
        ],
        'country' => [
            'size' => 'Country code must be exactly 2 characters.',
        ],
        'total_apartments' => [
            'required' => 'Total number of apartments is required.',
            'integer' => 'Total apartments must be a number.',
            'min' => 'Building must have at least 1 apartment.',
            'max' => 'Building cannot have more than 1000 apartments.',
        ],
        'built_year' => [
            'min' => 'Built year cannot be earlier than 1800.',
            'max' => 'Built year cannot be more than 5 years in the future.',
        ],
        'heating_type' => [
            'in' => 'The selected heating type is invalid.',
        ],
        'parking_spaces' => [
            'max' => 'Parking spaces cannot exceed 500.',
        ],
        'notes' => [
            'max' => 'Notes may not be greater than 2000 characters.',
        ],
        'apartments_per_floor_excessive' => 'The number of apartments per floor seems excessive. Please verify.',
        'central_heating_anachronistic' => 'Central heating was not common in buildings built before 1900.',
    ],
    'attributes' => [
        'tenant_id' => 'organization',
        'name' => 'building name',
        'address' => 'address',
        'city' => 'city',
        'postal_code' => 'postal code',
        'country' => 'country',
        'total_apartments' => 'total apartments',
        'floors' => 'floors',
        'built_year' => 'year built',
        'heating_type' => 'heating type',
        'elevator' => 'elevator',
        'parking_spaces' => 'parking spaces',
        'notes' => 'notes',
    ],
];
