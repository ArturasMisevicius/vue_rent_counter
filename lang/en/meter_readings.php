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

    'form_component' => [
        'title' => 'Enter Meter Reading',
        'select_meter' => 'Select Meter',
        'meter_placeholder' => '-- Select a meter --',
        'select_provider' => 'Select Provider',
        'provider_placeholder' => '-- Select a provider --',
        'select_tariff' => 'Select Tariff',
        'tariff_placeholder' => '-- Select a tariff --',
        'previous' => 'Previous Reading',
        'date_label' => 'Date:',
        'value_label' => 'Value:',
        'reading_date' => 'Reading Date',
        'reading_value' => 'Reading Value',
        'day_zone' => 'Day Zone Reading',
        'night_zone' => 'Night Zone Reading',
        'consumption' => 'Consumption',
        'units' => 'units',
        'estimated_charge' => 'Estimated Charge',
        'rate' => 'Rate:',
        'per_unit' => 'per unit',
        'reset' => 'Reset',
        'submit' => 'Submit Reading',
        'submitting' => 'Submitting...',
    ],

    'tables' => [
        'date' => 'Date',
        'meter' => 'Meter',
        'value' => 'Value',
        'zone' => 'Zone',
        'entered_by' => 'Entered By',
        'actions' => 'Actions',
        'property' => 'Property',
        'meter_type' => 'Meter Type',
        'consumption' => 'Consumption',
        'created_at' => 'Created At',
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
        'property' => 'Property',
        'meter' => 'Meter',
        'consumption' => 'Consumption',
        'from_date' => 'From Date',
        'until_date' => 'Until Date',
        'meter_type' => 'Meter Type',
        'reading_value' => 'Reading Value',
        'reading_id' => 'Reading #:id',
    ],

    'empty' => [
        'readings' => 'No meter readings found.',
    ],
    'recent_empty' => 'No recent readings',

    'na' => 'N/A',
    'units' => 'units',

    'helper_text' => [
        'select_property_first' => 'Select a property first',
        'zone_optional' => 'Optional: For multi-zone meters (e.g., day/night)',
        'change_reason' => 'Explain why this reading is being modified (minimum :min characters)',
    ],

    'filters' => [
        'from' => 'From Date',
        'until' => 'Until Date',
        'meter_type' => 'Meter Type',
        'indicator_from' => 'From: :date',
        'indicator_until' => 'Until: :date',
    ],

    'modals' => [
        'bulk_delete' => [
            'title' => 'Delete Meter Readings',
            'description' => 'Are you sure you want to delete these meter readings? This action cannot be undone.',
            'confirm' => 'Yes, delete them',
        ],
    ],

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
        'edit' => [
            'title' => 'Correct Meter Reading',
            'subtitle' => 'Update reading with audit trail',
            'breadcrumb' => 'Correct',
            'current' => [
                'title' => 'Current Reading',
                'meter' => 'Meter',
                'value' => 'Current Value',
                'date' => 'Reading Date',
            ],
            'form' => [
                'title' => 'New Values',
                'reading_date' => 'Reading Date',
                'value' => 'Reading Value',
                'placeholder' => '1234.56',
                'zone_label' => 'Time-of-Use Zone',
                'zone_options' => [
                    'day' => 'Day Rate',
                    'night' => 'Night Rate',
                ],
                'zone_placeholder' => 'Select zone...',
                'reason_label' => 'Correction Reason',
                'reason_placeholder' => 'Explain why this reading is being corrected...',
            ],
            'audit_notice' => [
                'title' => 'Audit Trail',
                'body' => 'This correction will be recorded in the audit trail. The original value and your reason for the change will be preserved.',
            ],
            'actions' => [
                'cancel' => 'Cancel',
                'save' => 'Save Correction',
            ],
        ],
        'mobile' => [
            'meter' => 'Meter:',
            'value' => 'Value:',
            'zone' => 'Zone:',
        ],
        'captions' => [
            'list' => 'Meter readings list',
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
            'min' => 'Change reason must be at least :min characters',
            'max' => 'Change reason must not exceed :max characters',
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
