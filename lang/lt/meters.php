<?php

declare(strict_types=1);

return [
    'actions' => [
        'add' => 'Add',
        'create' => 'Create',
        'delete' => 'Delete',
        'edit' => 'Edit',
        'edit_meter' => 'Edit Meter',
        'view' => 'View',
    ],
    'confirmations' => [
        'delete' => 'Delete',
    ],
    'empty_state' => [
        'description' => 'Description',
        'heading' => 'Heading',
    ],
    'errors' => [
        'has_readings' => 'Has Readings',
    ],
    'filters' => [
        'no_readings' => 'No Readings',
        'property' => 'Property',
        'supports_zones' => 'Supports Zones',
        'type' => 'Type',
    ],
    'headings' => [
        'information' => 'Information',
        'show' => 'Show',
        'show_description' => 'Show Description',
    ],
    'helper_text' => [
        'installation_date' => 'Installation Date',
        'property' => 'Property',
        'serial_number' => 'Serial Number',
        'supports_zones' => 'Supports Zones',
        'type' => 'Type',
    ],
    'labels' => [
        'created' => 'Created',
        'installation_date' => 'Installation Date',
        'meter' => 'Meter',
        'meters' => 'Meters',
        'property' => 'Property',
        'readings' => 'Readings',
        'readings_count' => 'Readings Count',
        'serial_number' => 'Serial Number',
        'supports_zones' => 'Supports Zones',
        'type' => 'Type',
    ],
    'manager' => [
        'index' => [
            'caption' => 'Caption',
            'description' => 'Description',
            'empty' => [
                'cta' => 'Cta',
                'text' => 'Text',
            ],
            'headers' => [
                'actions' => 'Actions',
                'installation_date' => 'Installation Date',
                'latest_reading' => 'Latest Reading',
                'property' => 'Property',
                'serial_number' => 'Serial Number',
                'type' => 'Type',
                'zones' => 'Zones',
            ],
            'title' => 'Title',
            'zones' => [
                'no' => 'No',
                'yes' => 'Yes',
            ],
        ],
    ],
    'modals' => [
        'bulk_delete' => [
            'confirm' => 'Confirm',
            'description' => 'Description',
            'title' => 'Title',
        ],
        'delete_confirm' => 'Delete Confirm',
        'delete_description' => 'Delete Description',
        'delete_heading' => 'Delete Heading',
    ],
    'notifications' => [
        'created' => 'Created',
        'updated' => 'Updated',
    ],
    'placeholders' => [
        'serial_number' => 'Serial Number',
    ],
    'relation' => [
        'add_first' => 'Add First',
        'empty_description' => 'Empty Description',
        'empty_heading' => 'Empty Heading',
        'initial_reading' => 'Initial Reading',
        'installation_date' => 'Installation Date',
        'installed' => 'Installed',
        'meter_type' => 'Meter Type',
        'readings' => 'Readings',
        'serial_number' => 'Serial Number',
        'type' => 'Type',
    ],
    'sections' => [
        'meter_details' => 'Meter Details',
        'meter_details_description' => 'Meter Details Description',
    ],
    'tooltips' => [
        'copy_serial' => 'Copy Serial',
        'property_address' => 'Property Address',
        'readings_count' => 'Readings Count',
        'supports_zones_no' => 'Supports Zones No',
        'supports_zones_yes' => 'Supports Zones Yes',
    ],
    'units' => [
        'kwh' => 'Kwh',
    ],
    'validation' => [
        'installation_date' => [
            'before_or_equal' => 'Before Or Equal',
            'date' => 'Date',
            'required' => 'Required',
        ],
        'property_id' => [
            'exists' => 'Exists',
            'required' => 'Required',
        ],
        'serial_number' => [
            'max' => 'Max',
            'required' => 'Required',
            'string' => 'String',
            'unique' => 'Unique',
        ],
        'supports_zones' => [
            'boolean' => 'Boolean',
        ],
        'tenant_id' => [
            'integer' => 'Integer',
            'required' => 'Required',
        ],
        'type' => [
            'enum_detail' => 'Enum Detail',
            'required' => 'Required',
        ],
    ],
];
