<?php

declare(strict_types=1);

return [
    'headings' => [
        'index' => 'Tariffs Management',
        'show' => 'Tariff Details',
        'information' => 'Tariff Information',
        'configuration' => 'Configuration',
        'version_history' => 'Version History',
        'quick_actions' => 'Quick Actions',
        'list' => 'Tariffs list',
    ],

    'descriptions' => [
        'index' => 'Configure utility pricing and time-of-use rates',
        'show' => 'View tariff configuration and version history',
    ],

    'labels' => [
        'name' => 'Name',
        'provider' => 'Provider',
        'type' => 'Type',
        'active_period' => 'Active Period',
        'active_from' => 'Active From',
        'active_until' => 'Active Until',
        'present' => 'Present',
        'status' => 'Status',
        'actions' => 'Actions',
        'service_type' => 'Service Type',
        'created_at' => 'Created At',
    ],

    'sections' => [
        'basic_information' => 'Basic Information',
        'effective_period' => 'Effective Period',
        'configuration' => 'Tariff Configuration',
    ],

    'forms' => [
        'provider' => 'Provider',
        'name' => 'Tariff Name',
        'active_from' => 'Active From',
        'active_until' => 'Active Until',
        'type' => 'Tariff Type',
        'currency' => 'Currency',
        'flat_rate' => 'Rate (€/kWh or €/m³)',
        'zones' => 'Time-of-Use Zones',
        'zone_id' => 'Zone ID',
        'zone_placeholder' => 'e.g., day, night, peak',
        'start_time' => 'Start Time',
        'start_placeholder' => 'HH:MM (e.g., 07:00)',
        'end_time' => 'End Time',
        'end_placeholder' => 'HH:MM (e.g., 23:00)',
        'zone_rate' => 'Rate (€/kWh)',
        'add_zone' => 'Add Zone',
        'weekend_logic' => 'Weekend Logic',
        'weekend_helper' => 'How to handle weekends for time-of-use tariffs',
        'fixed_fee' => 'Fixed Monthly Fee',
        'fixed_fee_helper' => 'Optional fixed monthly fee (e.g., meter rental)',
        'no_end_date' => 'No end date',
    ],

    'actions' => [
        'add' => 'Add Tariff',
        'edit' => 'Edit Tariff',
        'view' => 'View',
        'back' => 'Back to List',
        'delete' => 'Delete Tariff',
    ],

    'statuses' => [
        'active' => 'Active',
        'inactive' => 'Inactive',
    ],

    'empty' => [
        'list' => 'No tariffs found. Create your first tariff to get started.',
    ],

    'confirmations' => [
        'delete' => 'Are you sure you want to delete this tariff?',
    ],

    'validation' => [
        'provider_id' => [
            'required' => 'Provider is required',
            'exists' => 'Selected provider does not exist',
        ],
        'name' => [
            'required' => 'Tariff name is required',
            'string' => 'Tariff name must be text',
            'max' => 'Tariff name may not be greater than 255 characters',
        ],
        'configuration' => [
            'required' => 'Tariff configuration is required',
            'array' => 'Tariff configuration must be an array',
            'type' => [
                'required' => 'Tariff type is required',
                'string' => 'Tariff type must be text',
                'in' => 'Tariff type must be either flat or time_of_use',
            ],
            'currency' => [
                'required' => 'Currency is required',
                'string' => 'Currency must be text',
                'in' => 'Currency must be EUR',
            ],
            'rate' => [
                'required_if' => 'Rate is required for flat tariffs',
                'numeric' => 'Rate must be a number',
                'min' => 'Rate must be zero or greater',
            ],
            'zones' => [
                'required_if' => 'Zones are required for time-of-use tariffs',
                'array' => 'Zones must be provided as an array',
                'min' => 'At least one zone is required for time-of-use tariffs',
                'id' => [
                    'required_with' => 'Zone id is required when configuring zones',
                    'string' => 'Zone id must be text',
                ],
                'start' => [
                    'required_with' => 'Zone start time is required',
                    'string' => 'Zone start time must be text',
                    'regex' => 'Zone start time must be in HH:MM format (24-hour)',
                ],
                'end' => [
                    'required_with' => 'Zone end time is required',
                    'string' => 'Zone end time must be text',
                    'regex' => 'Zone end time must be in HH:MM format (24-hour)',
                ],
                'rate' => [
                    'required_with' => 'Rate is required for each zone',
                    'numeric' => 'Zone rate must be a number',
                    'min' => 'Zone rate must be a positive number',
                ],
                'errors' => [
                    'required' => 'At least one zone is required',
                    'overlap' => 'Time zones cannot overlap.',
                    'coverage' => 'Time zones must cover all 24 hours. Gap detected starting at :time.',
                ],
            ],
            'weekend_logic' => [
                'string' => 'Weekend logic must be text.',
                'in' => 'Weekend logic must be apply_night_rate, apply_day_rate, or apply_weekend_rate.',
            ],
            'fixed_fee' => [
                'numeric' => 'Fixed fee must be a number.',
                'min' => 'Fixed fee must be zero or greater.',
            ],
        ],
        'active_from' => [
            'required' => 'Active from date is required',
            'date' => 'Active from must be a valid date',
        ],
        'active_until' => [
            'after' => 'Active until date must be after active from date',
            'date' => 'Active until must be a valid date',
        ],
        'create_new_version' => [
            'boolean' => 'Create new version must be true or false.',
        ],
    ],

    'pages' => [
        'admin_form' => [
            'create_title' => 'Create Tariff',
            'create_subtitle' => 'Add a new tariff configuration',
            'edit_title' => 'Edit Tariff',
            'edit_subtitle' => 'Update tariff configuration or create a new version',
            'breadcrumb_create' => 'Create',
            'breadcrumb_edit' => 'Edit',
            'labels' => [
                'name' => 'Tariff Name',
                'provider' => 'Provider',
                'configuration' => 'Configuration',
                'active_from' => 'Active From',
                'active_until' => 'Active Until (Optional)',
            ],
            'placeholders' => [
                'configuration' => 'Enter tariff configuration as JSON object',
            ],
            'examples' => [
                'flat_heading' => 'Flat Rate Example:',
                'tou_heading' => 'Time of Use Example:',
                'required_fields' => 'Required Fields:',
                'flat_fields' => 'For flat: rate (numeric)',
                'tou_fields' => 'For time_of_use: zones array with id, start, end, rate',
            ],
            'versioning' => [
                'title' => 'Versioning',
                'body' => 'Check the box below to create a new version instead of updating the existing tariff. This preserves historical pricing data.',
                'checkbox' => 'Create new version (preserves historical data)',
            ],
            'actions' => [
                'cancel' => 'Cancel',
                'save_create' => 'Create Tariff',
                'save_edit' => 'Update Tariff',
            ],
        ],
    ],
];
