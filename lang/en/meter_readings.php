<?php

declare(strict_types=1);

return [
    'headings' => [
        'index' => 'Meter Readings',
        'create' => 'Enter Meter Reading',
        'show' => 'Meter Reading Details',
        'edit' => 'Edit Meter Reading',
    ],

    'actions' => [
        'back' => 'Back to Meter Readings',
        'enter_new' => 'Enter New Reading',
        'view' => 'View',
        'edit' => 'Edit',
        'delete' => 'Delete',
        'correct' => 'Correct Reading',
    ],

    'tables' => [
        'date' => 'Date',
        'meter' => 'Meter',
        'value' => 'Value',
        'zone' => 'Zone',
        'entered_by' => 'Entered By',
        'actions' => 'Actions',
    ],

    'labels' => [
        'meter_serial' => 'Meter Serial',
        'reading_date' => 'Reading Date',
        'value' => 'Value',
        'zone' => 'Zone',
        'entered_by' => 'Entered By',
        'created_at' => 'Created At',
        'history' => 'Correction History',
        'old_value' => 'Old Value',
        'new_value' => 'New Value',
        'reason' => 'Reason',
        'changed_by' => 'Changed By',
    ],

    'empty' => [
        'readings' => 'No meter readings found.',
    ],
    'recent_empty' => 'No recent readings',

    'na' => 'N/A',

    'manager' => [
        'index' => [
            'title' => 'Meter Readings',
            'description' => 'Recorded utility consumption across all properties',
            'filters' => [
                'group_by' => 'Group By',
                'none' => 'No Grouping',
                'property' => 'By Property',
                'meter_type' => 'By Meter Type',
                'property_label' => 'Property',
                'all_properties' => 'All Properties',
                'meter_type_label' => 'Meter Type',
                'all_types' => 'All Types',
                'apply' => 'Apply Filters',
            ],
            'captions' => [
                'property' => 'Meter readings grouped by property',
                'meter_type' => 'Meter readings grouped by meter type',
            ],
            'count' => '{1} :count reading|[2,*] :count readings',
            'empty' => [
                'text' => 'No meter readings found.',
                'cta' => 'Create one now',
            ],
        ],
        'show' => [
            'description' => 'Meter details and reading history',
        ],
        'create' => [
            'select_meter' => 'Select a meter...',
            'zone_options' => [
                'day' => 'Day Rate',
                'night' => 'Night Rate',
            ],
        ],
    ],

    'tenant' => [
        'title' => 'My Consumption History',
        'description' => 'View your meter readings and consumption patterns',
        'filters' => [
            'title' => 'Filters',
            'description' => 'Narrow down readings by meter type or time window.',
            'meter_type' => 'Meter Type',
            'all_types' => 'All Types',
            'date_from' => 'Date From',
            'date_to' => 'Date To',
        ],
        'submit' => [
            'title' => 'Submit a Reading',
            'description' => 'Add a new reading for your meter.',
            'no_property' => 'You need an assigned property with a meter to submit readings.',
            'meter' => 'Meter',
            'reading_date' => 'Reading Date',
            'value' => 'Value',
            'button' => 'Submit Reading',
        ],
        'table' => [
            'date' => 'Date',
            'reading' => 'Reading',
            'consumption' => 'Consumption',
            'zone' => 'Zone',
        ],
        'empty' => [
            'title' => 'No readings found',
            'description' => 'No meter readings match your current filters.',
        ],
    ],

    'validation' => [
        'meter_id' => [
            'required' => 'Meter is required',
            'exists' => 'Selected meter does not exist',
        ],
        'reading_date' => [
            'required' => 'Reading date is required',
            'date' => 'Reading date must be a valid date',
            'before_or_equal' => 'Reading date cannot be in the future',
        ],
        'value' => [
            'required' => 'Meter reading is required',
            'numeric' => 'Reading must be a number',
            'min' => 'Reading must be a positive number',
        ],
        'change_reason' => [
            'required' => 'Change reason is required for audit trail',
            'min' => 'Change reason must be at least 10 characters',
            'max' => 'Change reason must not exceed 500 characters',
        ],
        'zone' => [
            'string' => 'Zone must be text.',
            'max' => 'Zone may not exceed 50 characters.',
        ],
        'custom' => [
            'monotonicity_lower' => 'Reading cannot be lower than previous reading (:previous)',
            'monotonicity_higher' => 'Reading cannot be higher than next reading (:next)',
            'zone' => [
                'unsupported' => 'This meter does not support zone-based readings',
                'required_for_multi_zone' => 'Zone is required for meters that support multiple zones',
            ],
        ],
        'bulk' => [
            'readings' => [
                'required' => 'At least one reading is required.',
                'array' => 'Readings must be provided as an array.',
            ],
        ],
    ],

    'errors' => [
        'export_pending' => 'Export not yet implemented',
    ],
];
