<?php

declare(strict_types=1);

return [
    'validation' => [
        'report_type' => [
            'required' => 'Report type is required.',
            'in' => 'Report type must be one of consumption, revenue, outstanding, or meter-readings.',
        ],
        'format' => [
            'required' => 'Export format is required.',
            'in' => 'Export format must be csv, excel, or pdf.',
        ],
        'start_date' => [
            'date' => 'Start date must be a valid date.',
        ],
        'end_date' => [
            'date' => 'End date must be a valid date.',
            'after_or_equal' => 'End date must be after or the same as the start date.',
        ],
        'property_id' => [
            'exists' => 'The selected property does not exist.',
        ],
        'month' => [
            'date_format' => 'Month must be in YYYY-MM format.',
        ],
    ],

    'public' => [
        'title' => 'Reports',
        'links' => [
            'consumption' => 'Consumption Report',
            'revenue' => 'Revenue Report',
            'outstanding' => 'Outstanding Report',
        ],
    ],

    'common' => [
        'title' => 'Reports',
        'export_csv' => 'Export CSV',
        'generate_report' => 'Generate Report',
        'start_date' => 'Start Date',
        'end_date' => 'End Date',
        'building' => 'Building',
        'property' => 'Property',
        'meter_type' => 'Meter Type',
        'status' => 'Status',
        'month' => 'Month',
        'all_buildings' => 'All buildings...',
        'all_properties' => 'All properties...',
        'all_types' => 'All types...',
        'all_statuses' => 'All statuses...',
        'na' => 'N/A',
        'invoices_count' => '{1} :count invoice|[2,*] :count invoices',
        'readings_count' => '{1} :count reading|[2,*] :count readings',
    ],

    'manager' => [
        'index' => [
            'title' => 'Reports',
            'description' => 'Analytics and insights for your managed units',
            'breadcrumbs' => [
                'reports' => 'Reports',
            ],
            'stats' => [
                'properties' => 'Properties',
                'meters' => 'Total Meters',
                'readings' => 'Readings This Month',
                'invoices' => 'Invoices This Month',
            ],
            'cards' => [
                'consumption' => [
                    'title' => 'Consumption',
                    'description' => 'Track usage trends by property, meter type, or date range',
                    'cta' => 'View detailed analytics',
                ],
                'revenue' => [
                    'title' => 'Revenue',
                    'description' => 'See invoiced, paid, and outstanding amounts over time',
                    'cta' => 'Monitor billing health',
                ],
                'compliance' => [
                    'title' => 'Compliance',
                    'description' => 'Spot properties missing meter readings for the current cycle',
                    'cta' => 'Stay on schedule',
                ],
            ],
            'guide' => [
                'title' => 'How to use these reports',
                'items' => [
                    'consumption' => [
                        'title' => 'Consumption',
                        'body' => 'Compare usage month-over-month, filter by property or building, and export trends for provider reviews. Includes meter type breakdowns and top consuming properties.',
                    ],
                    'revenue' => [
                        'title' => 'Revenue',
                        'body' => 'Validate invoicing progress before closing the period and ensure overdue balances are visible. Track payment rates and revenue by building.',
                    ],
                    'compliance' => [
                        'title' => 'Compliance',
                        'body' => 'Identify meters without current readings and redirect your team to the right properties. Monitor compliance rates by building and meter type.',
                    ],
                ],
            ],
        ],

        'consumption' => [
            'title' => 'Consumption Report',
            'breadcrumb' => 'Consumption',
            'description' => 'Utility consumption by property and meter type',
            'export' => 'Export CSV',
            'filters' => [
                'start_date' => 'Start Date',
                'end_date' => 'End Date',
                'building' => 'Building',
                'property' => 'Property',
                'meter_type' => 'Meter Type',
                'placeholders' => [
                    'building' => 'All buildings...',
                    'property' => 'All properties...',
                    'meter_type' => 'All types...',
                ],
                'submit' => 'Generate Report',
            ],
            'stats' => [
                'monthly_trend' => 'Monthly Consumption Trend',
                'top_properties' => 'Top Consuming Properties',
                'top_caption' => 'Top consuming properties',
                'total_consumption' => 'Total Consumption',
                'readings' => 'Readings',
                'consumption_label' => 'Consumption:',
                'readings_label' => 'Readings:',
                'table' => [
                    'date' => 'Date',
                    'meter' => 'Meter',
                    'type' => 'Type',
                    'value' => 'Value',
                    'zone' => 'Zone',
                ],
                'property_caption' => 'Consumption readings for :property',
                'empty' => 'No consumption data found for the selected period.',
            ],
        ],

        'revenue' => [
            'title' => 'Revenue Report',
            'breadcrumb' => 'Revenue',
            'description' => 'Billing revenue by period and status',
            'export' => 'Export CSV',
            'filters' => [
                'start_date' => 'Start Date',
                'end_date' => 'End Date',
                'building' => 'Building',
                'status' => 'Status',
                'placeholders' => [
                    'building' => 'All buildings...',
                    'status' => 'All statuses...',
                ],
                'status_options' => [
                    'draft' => 'Draft',
                    'finalized' => 'Finalized',
                    'paid' => 'Paid',
                ],
                'submit' => 'Generate Report',
            ],
            'stats' => [
                'total' => 'Total Revenue',
                'paid' => 'Paid',
                'payment_rate' => ':rate% payment rate',
                'finalized' => 'Finalized',
                'overdue' => 'Overdue',
            ],
            'monthly' => [
                'title' => 'Monthly Revenue Trend',
                'invoices' => ':count invoices',
                'paid' => '€:amount paid',
            ],
            'by_building' => [
                'title' => 'Revenue by Building',
                'caption' => 'Revenue breakdown by building',
                'headers' => [
                    'building' => 'Building',
                    'revenue' => 'Total Revenue',
                    'invoices' => 'Invoices',
                ],
                'mobile' => [
                    'revenue' => 'Revenue:',
                    'invoices' => 'Invoices:',
                ],
            ],
            'invoices' => [
                'title' => 'Invoice Details',
                'caption' => 'Invoices in revenue report',
                'headers' => [
                    'number' => 'Invoice #',
                    'property' => 'Property',
                    'period' => 'Period',
                    'amount' => 'Amount',
                    'status' => 'Status',
                    'due' => 'Due',
                ],
                'empty' => 'No invoices found for the selected period.',
            ],
        ],

        'compliance' => [
            'title' => 'Meter Reading Compliance Report',
            'breadcrumb' => 'Reading Compliance',
            'description' => 'Track meter reading completion by property',
            'export' => 'Export CSV',
            'filters' => [
                'month' => 'Month',
                'building' => 'Building',
                'placeholders' => [
                    'building' => 'All buildings...',
                ],
                'submit' => 'Generate Report',
            ],
            'summary' => [
                'title' => 'Compliance Summary',
                'complete' => [
                    'label' => 'Complete',
                    'description' => 'All meters read',
                ],
                'partial' => [
                    'label' => 'Partial',
                    'description' => 'Some meters missing',
                ],
                'none' => [
                    'label' => 'No Readings',
                    'description' => 'No meters read',
                ],
                'overall' => 'Overall Compliance Rate',
                'properties' => 'Properties',
            ],
            'by_building' => [
                'title' => 'Compliance by Building',
                'properties' => ':complete / :total properties',
            ],
            'by_meter_type' => [
                'title' => 'Compliance by Meter Type',
                'meters' => ':complete / :total meters',
            ],
            'details' => [
                'title' => 'Property Details',
                'caption' => 'Property meter reading compliance',
                'headers' => [
                    'property' => 'Property',
                    'building' => 'Building',
                    'total_meters' => 'Total Meters',
                    'readings_submitted' => 'Readings Submitted',
                    'status' => 'Status',
                    'actions' => 'Actions',
                ],
                'add_readings' => 'Add Readings',
                'mobile' => [
                    'meters' => 'Meters:',
                    'readings' => 'Readings:',
                ],
            ],
        ],
    ],
];
