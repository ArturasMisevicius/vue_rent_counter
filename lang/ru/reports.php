<?php

declare(strict_types=1);

return [
    'common' => [
        'all_buildings' => 'All Buildings',
        'all_properties' => 'All Properties',
        'all_statuses' => 'All Statuses',
        'all_types' => 'All Types',
        'building' => 'Building',
        'end_date' => 'End Date',
        'meter_type' => 'Meter Type',
        'na' => 'Na',
        'property' => 'Property',
        'start_date' => 'Start Date',
        'status' => 'Status',
    ],
    'errors' => [
        'export_pending' => 'Export Pending',
    ],
    'manager' => [
        'compliance' => [
            'by_building' => [
                'properties' => 'Properties',
                'title' => 'Title',
            ],
            'by_meter_type' => [
                'meters' => 'Meters',
                'title' => 'Title',
            ],
            'description' => 'Description',
            'details' => [
                'add_readings' => 'Add Readings',
                'caption' => 'Caption',
                'headers' => [
                    'actions' => 'Actions',
                    'building' => 'Building',
                    'property' => 'Property',
                    'readings_submitted' => 'Readings Submitted',
                    'status' => 'Status',
                    'total_meters' => 'Total Meters',
                ],
                'mobile' => [
                    'meters' => 'Meters',
                    'readings' => 'Readings',
                ],
                'title' => 'Title',
            ],
            'export' => 'Export',
            'filters' => [
                'building' => 'Building',
                'month' => 'Month',
                'placeholders' => [
                    'building' => 'Building',
                ],
                'submit' => 'Submit',
            ],
            'summary' => [
                'complete' => [
                    'description' => 'Description',
                    'label' => 'Label',
                ],
                'none' => [
                    'description' => 'Description',
                    'label' => 'Label',
                ],
                'overall' => 'Overall',
                'partial' => [
                    'description' => 'Description',
                    'label' => 'Label',
                ],
                'properties' => 'Properties',
                'title' => 'Title',
            ],
            'title' => 'Title',
        ],
        'consumption' => [
            'description' => 'Description',
            'export' => 'Export',
            'filters' => [
                'submit' => 'Submit',
            ],
            'stats' => [
                'consumption_label' => 'Consumption Label',
                'empty' => 'Empty',
                'monthly_trend' => 'Monthly Trend',
                'property_caption' => 'Property Caption',
                'readings' => 'Readings',
                'readings_label' => 'Readings Label',
                'top_caption' => 'Top Caption',
                'top_properties' => 'Top Properties',
                'total_consumption' => 'Total Consumption',
            ],
            'title' => 'Title',
        ],
        'index' => [
            'cards' => [
                'compliance' => [
                    'cta' => 'Cta',
                    'description' => 'Description',
                    'title' => 'Title',
                ],
                'consumption' => [
                    'cta' => 'Cta',
                    'description' => 'Description',
                    'title' => 'Title',
                ],
                'revenue' => [
                    'cta' => 'Cta',
                    'description' => 'Description',
                    'title' => 'Title',
                ],
            ],
            'description' => 'Description',
            'guide' => [
                'items' => [
                    'compliance' => [
                        'body' => 'Body',
                        'title' => 'Title',
                    ],
                    'consumption' => [
                        'body' => 'Body',
                        'title' => 'Title',
                    ],
                    'revenue' => [
                        'body' => 'Body',
                        'title' => 'Title',
                    ],
                ],
                'title' => 'Title',
            ],
            'stats' => [
                'invoices' => 'Invoices',
                'meters' => 'Meters',
                'properties' => 'Properties',
                'readings' => 'Readings',
            ],
            'title' => 'Title',
        ],
        'revenue' => [
            'by_building' => [
                'caption' => 'Caption',
                'headers' => [
                    'invoices' => 'Invoices',
                    'revenue' => 'Revenue',
                ],
                'mobile' => [
                    'invoices' => 'Invoices',
                    'revenue' => 'Revenue',
                ],
                'title' => 'Title',
            ],
            'description' => 'Description',
            'export' => 'Export',
            'filters' => [
                'status_options' => [
                    'draft' => 'Draft',
                    'finalized' => 'Finalized',
                    'paid' => 'Paid',
                ],
                'submit' => 'Submit',
            ],
            'invoices' => [
                'caption' => 'Caption',
                'empty' => 'Empty',
                'headers' => [
                    'amount' => 'Amount',
                    'due' => 'Due',
                    'number' => 'Number',
                    'period' => 'Period',
                    'property' => 'Property',
                    'status' => 'Status',
                ],
                'title' => 'Title',
            ],
            'monthly' => [
                'paid' => 'Paid',
                'title' => 'Title',
            ],
            'stats' => [
                'finalized' => 'Finalized',
                'overdue' => 'Overdue',
                'paid' => 'Paid',
                'payment_rate' => 'Payment Rate',
                'total' => 'Total',
            ],
            'title' => 'Title',
        ],
    ],
    'public' => [
        'links' => [
            'consumption' => 'Consumption',
            'outstanding' => 'Outstanding',
            'revenue' => 'Revenue',
        ],
        'title' => 'Title',
    ],
    'validation' => [
        'end_date' => [
            'after_or_equal' => 'After Or Equal',
            'date' => 'Date',
        ],
        'format' => [
            'in' => 'In',
            'required' => 'Required',
        ],
        'month' => [
            'date_format' => 'Date Format',
        ],
        'property_id' => [
            'exists' => 'Exists',
        ],
        'report_type' => [
            'in' => 'In',
            'required' => 'Required',
        ],
        'start_date' => [
            'date' => 'Date',
        ],
    ],
];
