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
        'supports_zones' => 'Supports Zones',
        'supports_time_of_use' => 'Supports Time-of-Use Zones',
        'zones' => 'Zones',
        'created_at' => 'Created At',
        'initial_reading' => 'Initial Reading',
        'installed' => 'Installed',
        'readings' => 'Readings',
    ],

    'headings' => [
        'show' => 'Meter :serial',
        'show_description' => 'Meter details and reading history',
        'information' => 'Meter Information',
    ],

    'actions' => [
        'add' => 'Add Meter',
        'view' => 'View',
        'edit' => 'Edit',
        'edit_meter' => 'Edit Meter',
        'delete' => 'Delete',
    ],

    'confirmations' => [
        'delete' => 'Are you sure you want to delete this meter?',
    ],

    'manager' => [
        'index' => [
            'title' => 'Meters',
            'description' => 'Utility meters across all properties',
            'caption' => 'Meters list',
            'headers' => [
                'serial_number' => 'Serial Number',
                'type' => 'Type',
                'property' => 'Property',
                'installation_date' => 'Installation Date',
                'latest_reading' => 'Latest Reading',
                'zones' => 'Zones',
                'actions' => 'Actions',
            ],
            'zones' => [
                'yes' => 'Yes',
                'no' => 'No',
            ],
            'empty' => [
                'text' => 'No meters found.',
                'cta' => 'Create one now',
            ],
        ],
    ],

    'helper_text' => [
        'supports_time_of_use' => 'Enable for electricity meters with day/night rate capability',
    ],

    'filters' => [
        'supports_zones' => 'Supports Zones',
        'all_meters' => 'All meters',
        'with_zones' => 'With zones',
        'without_zones' => 'Without zones',
    ],

    'relation' => [
        'meter_type' => 'Meter Type',
        'serial_number' => 'Serial Number',
        'installation_date' => 'Installation Date',
        'initial_reading' => 'Initial Reading',
        'readings' => 'Readings',
        'installed' => 'Installed',
        'type' => 'Type',
        'empty_heading' => 'No meters installed',
        'empty_description' => 'Add meters to track utility consumption for this property.',
        'add_first' => 'Add First Meter',
    ],

    'units' => [
        'kwh' => 'kWh',
    ],

    'validation' => [
        'tenant_id' => [
            'required' => 'Tenant is required.',
            'integer' => 'Tenant identifier must be numeric.',
        ],
        'property_id' => [
            'required' => 'The property is required.',
            'exists' => 'The selected property does not exist.',
        ],
        'type' => [
            'required' => 'The meter type is required.',
            'enum' => 'The meter type must be a valid type.',
            'enum_detail' => 'The meter type must be one of electricity, cold water, hot water, or heating.',
        ],
        'serial_number' => [
            'required' => 'The meter serial number is required.',
            'string' => 'The meter serial number must be text.',
            'unique' => 'This serial number is already registered.',
            'max' => 'The serial number may not exceed 255 characters.',
        ],
        'installation_date' => [
            'required' => 'The installation date is required.',
            'date' => 'The installation date must be a valid date.',
            'before_or_equal' => 'The installation date cannot be in the future.',
        ],
        'supports_zones' => [
            'boolean' => 'Supports zones must be true or false.',
        ],
    ],

    'errors' => [
        'has_readings' => 'Cannot delete meter with associated readings.',
    ],
];
