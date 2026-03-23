<?php

return [
    'actions' => [
        'add' => 'Add',
        'create' => 'Create',
        'delete' => 'Delete',
        'edit' => 'Edit',
        'edit_meter' => 'Edit Contador',
        'view' => 'View',
        'view_readings' => 'View readings',
    ],
    'confirmations' => [
        'delete' => 'Delete',
    ],
    'empty_state' => [
        'description' => 'Description',
        'heading' => 'Heading',
    ],
    'errors' => [
        'has_readings' => 'Has lecturas',
    ],
    'filters' => [
        'no_readings' => 'No lecturas',
        'property' => 'Propiedad',
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
        'property' => 'Propiedad',
        'serial_number' => 'Serial Number',
        'supports_zones' => 'Supports Zones',
        'type' => 'Type',
    ],
    'labels' => [
        'created' => 'Created',
        'installation_date' => 'Installation Date',
        'meter' => 'Contador',
        'meters' => 'Contadors',
        'property' => 'Propiedad',
        'readings' => 'lecturas',
        'readings_count' => 'lecturas Count',
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
                'latest_reading' => 'Latest lectura',
                'property' => 'Propiedad',
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
        'initial_reading' => 'Initial lectura',
        'installation_date' => 'Installation Date',
        'installed' => 'Installed',
        'meter_type' => 'Contador Type',
        'readings' => 'lecturas',
        'serial_number' => 'Serial Number',
        'type' => 'Type',
    ],
    'sections' => [
        'meter_details' => 'Contador Details',
        'meter_details_description' => 'Contador Details Description',
    ],
    'tooltips' => [
        'copy_serial' => 'Copy Serial',
        'property_address' => 'Propiedad Address',
        'readings_count' => 'lecturas Count',
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
