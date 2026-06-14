<?php

declare(strict_types=1);

return [
    'actions' => [
        'help' => 'Help',
        'open_help_center' => 'Open Help Center',
    ],
    'pages' => [
        'admin' => [
            'title' => 'Help',
            'eyebrow' => 'Product documentation',
            'heading' => 'Help Center',
            'description' => 'Find role-aware guides for billing, services, invoices, readings, tenants, contracts, documents, and troubleshooting.',
        ],
        'tenant' => [
            'title' => 'Help',
            'eyebrow' => 'Tenant guide',
            'heading' => 'Help Center',
            'description' => 'Find tenant-facing instructions for readings, invoices, documents, profile updates, and overdue payments.',
        ],
    ],
    'navigation' => [
        'center' => 'Help',
        'tenant' => 'Help',
    ],
    'search' => [
        'label' => 'Search help',
        'placeholder' => 'Search help articles',
    ],
    'filters' => [
        'all_categories' => 'All categories',
    ],
    'context' => [
        'heading' => 'Page help',
        'related_heading' => 'Related help for this page',
        'empty' => [
            'heading' => 'No contextual help yet',
            'description' => 'Open the Help Center to search the full product documentation.',
        ],
    ],
    'empty' => [
        'no_categories' => 'No help categories are available.',
        'no_results_heading' => 'No help articles found',
        'no_results_description' => 'Try a different category or search term.',
    ],
    'fields' => [
        'tariff' => 'The price per unit used to calculate meter-based invoice items. Example: 0.25 EUR per kWh.',
        'previous_reading' => 'The last approved reading from the previous billing period.',
        'current_reading' => 'Enter the current number shown on the meter. The system calculates consumption from the previous approved reading.',
        'tenant_visible_description' => 'This text is shown to the tenant on invoices or document pages. Do not write internal notes here.',
        'internal_note' => 'Visible only to admins and managers. Tenants will not see this.',
        'billing_period' => 'The period for which invoices and readings are collected. Example: March 2026.',
    ],
    'checklist' => [
        'heading' => 'Setup checklist',
        'description' => 'Complete these steps before generating the first invoice for this organization.',
        'status' => [
            'complete' => 'Complete',
            'pending' => 'Pending',
        ],
        'items' => [
            'building' => [
                'label' => 'Add first building',
                'description' => 'Buildings group properties, meters, tenants, and documents.',
                'action' => 'Open buildings',
            ],
            'property' => [
                'label' => 'Add first property',
                'description' => 'A property is the billable unit assigned to a tenant.',
                'action' => 'Open properties',
            ],
            'tenant' => [
                'label' => 'Add first tenant',
                'description' => 'Tenants receive invoices, invitations, readings, and documents.',
                'action' => 'Open tenants',
            ],
            'assignment' => [
                'label' => 'Assign tenant to property',
                'description' => 'Invoices and readings need an active tenant-property assignment.',
                'action' => 'Open tenants',
            ],
            'meters' => [
                'label' => 'Add meters',
                'description' => 'Meter-based services need active meters before readings can be collected.',
                'action' => 'Open meters',
            ],
            'services' => [
                'label' => 'Configure services',
                'description' => 'Services and tariffs define how invoice items are calculated.',
                'action' => 'Open services',
            ],
            'invitation' => [
                'label' => 'Send tenant invitation',
                'description' => 'Invite the tenant so they can submit readings and view invoices.',
                'action' => 'Open tenants',
            ],
            'invoice' => [
                'label' => 'Generate first invoice',
                'description' => 'Create the first invoice only after setup and readings are ready.',
                'action' => 'Open invoices',
            ],
        ],
    ],
];
