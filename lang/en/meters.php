<?php

declare(strict_types=1);

return [
    'labels' => [
        'meter' => 'Meter',
        'meters' => 'Meters',
        'property' => 'Property',
        'type' => 'Meter Type',
        'serial_number' => 'Serial Number',
        'installation_date' => 'Installation Date',
        'last_reading' => 'Last Reading',
        'status' => 'Status',
    ],

    'validation' => [
        'property_id' => [
            'required' => 'The property is required.',
            'exists' => 'The selected property does not exist.',
        ],
        'type' => [
            'required' => 'The meter type is required.',
            'enum' => 'The meter type must be a valid type.',
        ],
        'serial_number' => [
            'required' => 'The meter serial number is required.',
            'unique' => 'This serial number is already registered.',
            'max' => 'The serial number may not exceed 255 characters.',
        ],
        'installation_date' => [
            'required' => 'The installation date is required.',
            'date' => 'The installation date must be a valid date.',
            'before_or_equal' => 'The installation date cannot be in the future.',
        ],
    ],
];
