<?php

return [
    'meters' => [
        'generated_demo_meter' => [
            'name' => 'Demo :type Meter',
        ],
        'operations_demo_meter' => [
            'name' => 'Operations Demo Meter: :type',
        ],
    ],
    'meter_readings' => [
        'notes' => [
            'seeded_legacy_operations_reading' => 'Seeded legacy operations reading.',
        ],
        'change_reasons' => [
            'seeded_baseline_validation_check' => 'Seeded baseline validation check',
        ],
    ],
    'buildings' => [
        'demo_building' => [
            'name' => 'Demo Building :number',
        ],
    ],
    'properties' => [
        'demo_unit' => [
            'name' => 'Demo Unit :number',
        ],
    ],
    'invoices' => [
        'demo_invoice' => [
            'notes' => 'Demo invoice :number for :property',
        ],
        'seeded_login_demo_invoice' => [
            'notes' => 'Seeded login demo invoice :number',
        ],
        'legacy_operations_foundation_demo_invoice' => [
            'notes' => 'Legacy operations foundation demo invoice.',
        ],
    ],
    'billing_records' => [
        'seeded_billing_record' => [
            'notes' => 'Seeded :description billing record',
        ],
        'seeded_demo_invoice_record' => [
            'notes' => 'Seeded billing record for demo invoice.',
        ],
    ],
    'projects' => [
        'legacy_collaboration_demo_project' => [
            'name' => 'Legacy Collaboration Demo Project',
            'description' => 'Imported collaboration foundation demo project.',
        ],
        'modernization_program' => [
            'name' => ':name Modernization Program',
            'description' => 'Operational improvement plan for :name.',
        ],
    ],
    'tasks' => [
        'demo_task' => [
            'title' => 'Demo Task :number',
        ],
        'inspect_shared_utility_setup' => [
            'title' => 'Inspect shared utility setup',
            'description' => 'Review imported utility service configuration data.',
        ],
        'review_imported_collaboration_layer' => [
            'title' => 'Review imported collaboration layer',
            'description' => 'Higher-fidelity task imported from legacy collaboration domain.',
        ],
        'inspect_shared_systems' => [
            'description' => 'Inspect shared systems.',
        ],
        'coordinate_resident_communication' => [
            'description' => 'Coordinate resident communication.',
        ],
    ],
    'task_assignments' => [
        'seeded_operational_assignment' => [
            'notes' => 'Seeded operational assignment.',
        ],
        'demo_tenant_assignment' => [
            'notes' => 'Demo tenant assignment for collaboration foundation.',
        ],
    ],
    'utility_services' => [
        'organization_service' => [
            'name' => 'Org :number :service',
        ],
        'global' => [
            'electricity' => [
                'description' => 'Electricity consumption charges for residential properties.',
            ],
            'water' => [
                'description' => 'Water supply and sewage charges with a fixed and variable component.',
            ],
            'heating' => [
                'description' => 'District heating utility charges.',
            ],
        ],
        'organization' => [
            'electricity' => [
                'description' => 'Electricity utility for tenant consumption billing.',
            ],
            'water' => [
                'description' => 'Water utility with fixed and variable components.',
            ],
            'heating' => [
                'description' => 'Heating utility for seasonal usage.',
            ],
        ],
    ],
    'tags' => [
        'legacy_foundation' => [
            'description' => 'Imported from the legacy collaboration foundation.',
        ],
    ],
    'comments' => [
        'legacy_collaboration_imported' => [
            'body' => 'Legacy collaboration layer imported successfully.',
        ],
    ],
    'attachments' => [
        'seeded_demo_collaboration_attachment' => [
            'description' => 'Seeded demo collaboration attachment.',
        ],
    ],
    'subscription_renewals' => [
        'seeded_legacy_operations_history' => [
            'notes' => 'Seeded renewal history for legacy operations foundation.',
        ],
    ],
    'system_configurations' => [
        'platform_default_currency' => [
            'description' => 'Default billing currency for platform-level operations.',
        ],
    ],
    'time_entries' => [
        'seeded_progress_update' => [
            'description' => 'Seeded progress update.',
        ],
        'seeded_imported_collaboration_task' => [
            'description' => 'Seeded demo time entry for imported collaboration task.',
        ],
    ],
    'activity_logs' => [
        'seeded_collaboration_foundation' => [
            'description' => 'Seeded collaboration foundation activity',
        ],
        'imported_from_legacy_collaboration_foundation' => [
            'description' => 'Imported from the legacy collaboration foundation.',
        ],
    ],
];
