<?php

declare(strict_types=1);

return [
    'actions' => [
        'back' => 'Back',
        'delete' => 'Delete',
        'edit' => 'Edit',
        'view' => 'View',
    ],
    'confirmations' => [
        'delete' => 'Delete',
    ],
    'descriptions' => [
        'show' => 'Show',
    ],
    'forms' => [
        'active_from' => 'Active From',
        'active_until' => 'Active Until',
        'add_zone' => 'Add Zone',
        'currency' => 'Currency',
        'end_placeholder' => 'End Placeholder',
        'end_time' => 'End Time',
        'fixed_fee' => 'Fixed Fee',
        'fixed_fee_helper' => 'Fixed Fee Helper',
        'flat_rate' => 'Flat Rate',
        'manual_mode' => 'Manual Mode',
        'manual_mode_helper' => 'Manual Mode Helper',
        'name' => 'Name',
        'no_end_date' => 'No End Date',
        'provider' => 'Provider',
        'remote_id' => 'Remote Id',
        'remote_id_helper' => 'Remote Id Helper',
        'start_placeholder' => 'Start Placeholder',
        'start_time' => 'Start Time',
        'type' => 'Type',
        'weekend_helper' => 'Weekend Helper',
        'weekend_logic' => 'Weekend Logic',
        'zone_id' => 'Zone Id',
        'zone_placeholder' => 'Zone Placeholder',
        'zone_rate' => 'Zone Rate',
        'zones' => 'Zones',
    ],
    'headings' => [
        'configuration' => 'Configuration',
        'information' => 'Information',
        'quick_actions' => 'Quick Actions',
        'show' => 'Show',
        'version_history' => 'Version History',
    ],
    'index' => [
        'create_button' => 'Create Button',
        'empty' => [
            'create_button' => 'Create Button',
            'description' => 'Description',
            'title' => 'Title',
        ],
        'table' => [
            'actions' => 'Actions',
            'active_period' => 'Active Period',
            'delete' => 'Delete',
            'delete_confirm' => 'Delete Confirm',
            'edit' => 'Edit',
            'name' => 'Name',
            'ongoing' => 'Ongoing',
            'provider' => 'Provider',
            'type' => 'Type',
            'view' => 'View',
        ],
        'title' => 'Title',
    ],
    'labels' => [
        'actions' => 'Actions',
        'active_from' => 'Active From',
        'active_until' => 'Active Until',
        'created_at' => 'Created At',
        'name' => 'Name',
        'present' => 'Present',
        'provider' => 'Provider',
        'service_type' => 'Service Type',
        'status' => 'Status',
    ],
    'pages' => [
        'admin_form' => [
            'actions' => [
                'cancel' => 'Cancel',
                'save_create' => 'Save Create',
                'save_edit' => 'Save Edit',
            ],
            'create_subtitle' => 'Create Subtitle',
            'create_title' => 'Create Title',
            'edit_subtitle' => 'Edit Subtitle',
            'edit_title' => 'Edit Title',
            'examples' => [
                'flat_fields' => 'Flat Fields',
                'flat_heading' => 'Flat Heading',
                'required_fields' => 'Required Fields',
                'tou_fields' => 'Tou Fields',
                'tou_heading' => 'Tou Heading',
            ],
            'labels' => [
                'active_from' => 'Active From',
                'active_until' => 'Active Until',
                'configuration' => 'Configuration',
                'name' => 'Name',
                'provider' => 'Provider',
            ],
            'placeholders' => [
                'configuration' => 'Configuration',
            ],
            'versioning' => [
                'body' => 'Body',
                'checkbox' => 'Checkbox',
                'title' => 'Title',
            ],
        ],
    ],
    'statuses' => [
        'active' => 'Active',
        'inactive' => 'Inactive',
    ],
    'types' => 'Types',
    'validation' => [
        'active_from' => [
            'date' => 'Date',
            'required' => 'Required',
        ],
        'active_until' => [
            'after' => 'After',
            'date' => 'Date',
        ],
        'configuration' => [
            'array' => 'Array',
            'currency' => [
                'in' => 'In',
                'required' => 'Required',
                'string' => 'String',
            ],
            'fixed_fee' => [
                'max' => 'Max',
                'min' => 'Min',
                'numeric' => 'Numeric',
            ],
            'rate' => [
                'max' => 'Max',
                'min' => 'Min',
                'numeric' => 'Numeric',
                'required_if' => 'Required If',
            ],
            'required' => 'Required',
            'type' => [
                'in' => 'In',
                'required' => 'Required',
                'string' => 'String',
            ],
            'weekend_logic' => [
                'in' => 'In',
                'string' => 'String',
            ],
            'zones' => [
                'array' => 'Array',
                'end' => [
                    'regex' => 'Regex',
                    'required_with' => 'Required With',
                    'string' => 'String',
                ],
                'errors' => [
                    'coverage' => 'Gap detected starting at :time',
                    'overlap' => 'Time zones cannot overlap.',
                    'required' => 'At least one zone is required',
                ],
                'id' => [
                    'max' => 'Max',
                    'regex' => 'Regex',
                    'required_with' => 'Required With',
                    'string' => 'String',
                ],
                'min' => 'Min',
                'rate' => [
                    'max' => 'Max',
                    'min' => 'Min',
                    'numeric' => 'Numeric',
                    'required_with' => 'Required With',
                ],
                'required_if' => 'Required If',
                'start' => [
                    'regex' => 'Regex',
                    'required_with' => 'Required With',
                    'string' => 'String',
                ],
            ],
        ],
        'create_new_version' => [
            'boolean' => 'Boolean',
        ],
        'name' => [
            'max' => 'Max',
            'regex' => 'Regex',
            'required' => 'Required',
            'string' => 'String',
        ],
        'provider_id' => [
            'exists' => 'Exists',
            'required' => 'Required',
            'required_with' => 'Required With',
        ],
        'remote_id' => [
            'format' => 'Format',
            'max' => 'Max',
        ],
    ],
    'types' => [
        '' => '',
    ],
];
