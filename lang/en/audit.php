<?php

declare(strict_types=1);

return [
    'navigation' => 'Activity Logs',
    'labels' => [
        'timestamp' => 'Timestamp',
        'organization' => 'Organization',
        'user' => 'User',
        'action' => 'Action',
        'resource' => 'Resource',
        'resource_type' => 'Resource Type',
        'resource_id' => 'Resource ID',
        'ip_address' => 'IP Address',
        'user_agent' => 'User Agent',
        'details' => 'Details',
        'action_type' => 'Action Type',
        'additional_data' => 'Additional Data',
    ],
    'filters' => [
        'from' => 'From',
        'until' => 'Until',
        'create' => 'Create',
        'update' => 'Update',
        'delete' => 'Delete',
        'view' => 'View',
    ],
    'sections' => [
        'activity_details' => 'Activity Details',
        'request_information' => 'Request Information',
        'metadata' => 'Metadata',
    ],
    'pages' => [
        'index' => [
            'title' => 'Audit Trail',
            'breadcrumb' => 'Audit Trail',
            'description' => 'View system activity and meter reading changes',
            'filters' => [
                'from_date' => 'From Date',
                'to_date' => 'To Date',
                'meter_serial' => 'Meter Serial',
                'meter_placeholder' => 'Search by serial...',
                'apply' => 'Apply Filters',
                'clear' => 'Clear',
            ],
            'table' => [
                'caption' => 'Audit trail',
                'timestamp' => 'Timestamp',
                'meter' => 'Meter',
                'reading_date' => 'Reading Date',
                'old_value' => 'Old Value',
                'new_value' => 'New Value',
                'changed_by' => 'Changed By',
                'reason' => 'Reason',
                'reading' => 'Reading:',
            ],
            'states' => [
                'not_available' => 'N/A',
                'system' => 'System',
                'empty' => 'No audit records found.',
                'clear_filters' => 'Clear filters',
                'see_all' => 'to see all records.',
                'by' => 'By:',
                'old_short' => 'Old:',
                'new_short' => 'New:',
            ],
        ],
    ],
];
