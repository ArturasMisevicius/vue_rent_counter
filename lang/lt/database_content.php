<?php

return [
    'meters' => [
        'generated_demo_meter' => [
            'name' => 'Demonstracinis skaitiklis: :type',
        ],
        'operations_demo_meter' => [
            'name' => 'Operacijų demonstracinis skaitiklis: :type',
        ],
    ],
    'meter_readings' => [
        'notes' => [
            'seeded_legacy_operations_reading' => 'Importuotas senos operacijų sistemos rodmuo.',
        ],
        'change_reasons' => [
            'seeded_baseline_validation_check' => 'Importuota bazinė patikros priežastis',
        ],
    ],
    'buildings' => [
        'demo_building' => [
            'name' => 'Demonstracinis pastatas :number',
        ],
    ],
    'properties' => [
        'demo_unit' => [
            'name' => 'Demonstracinė patalpa :number',
        ],
    ],
    'invoices' => [
        'demo_invoice' => [
            'notes' => 'Demonstracinė sąskaita :number: :property',
        ],
        'seeded_login_demo_invoice' => [
            'notes' => 'Importuota prisijungimo demonstracinė sąskaita :number',
        ],
        'legacy_operations_foundation_demo_invoice' => [
            'notes' => 'Senos operacijų sistemos demonstracinė sąskaita.',
        ],
    ],
    'billing_records' => [
        'seeded_billing_record' => [
            'notes' => 'Importuotas atsiskaitymo įrašas: :description',
        ],
        'seeded_demo_invoice_record' => [
            'notes' => 'Importuotas demonstracinės sąskaitos atsiskaitymo įrašas.',
        ],
    ],
    'projects' => [
        'legacy_collaboration_demo_project' => [
            'name' => 'Senos bendradarbiavimo sistemos demonstracinis projektas',
            'description' => 'Importuotas senos bendradarbiavimo sistemos demonstracinis projektas.',
        ],
        'modernization_program' => [
            'name' => ':name modernizavimo programa',
            'description' => ':name operacijų tobulinimo planas.',
        ],
    ],
    'tasks' => [
        'demo_task' => [
            'title' => 'Demonstracinė užduotis :number',
        ],
        'inspect_shared_utility_setup' => [
            'title' => 'Patikrinti bendrų komunalinių paslaugų nustatymus',
            'description' => 'Peržiūrėti importuotus komunalinių paslaugų konfigūracijos duomenis.',
        ],
        'review_imported_collaboration_layer' => [
            'title' => 'Peržiūrėti importuotą bendradarbiavimo sluoksnį',
            'description' => 'Detalesnė užduotis, importuota iš senos bendradarbiavimo srities.',
        ],
        'inspect_shared_systems' => [
            'description' => 'Patikrinti bendras sistemas.',
        ],
        'coordinate_resident_communication' => [
            'description' => 'Suderinti komunikaciją su gyventojais.',
        ],
    ],
    'task_assignments' => [
        'seeded_operational_assignment' => [
            'notes' => 'Importuotas operacinis priskyrimas.',
        ],
        'demo_tenant_assignment' => [
            'notes' => 'Demonstracinis nuomininko priskyrimas iš bendradarbiavimo pagrindo.',
        ],
    ],
    'utility_services' => [
        'organization_service' => [
            'name' => 'Organizacija :number: :service',
        ],
        'global' => [
            'electricity' => [
                'description' => 'Elektros suvartojimo mokesčiai gyvenamosioms patalpoms.',
            ],
            'water' => [
                'description' => 'Vandens tiekimo ir nuotekų mokesčiai su fiksuota ir kintama dalimi.',
            ],
            'heating' => [
                'description' => 'Centralizuoto šildymo komunaliniai mokesčiai.',
            ],
        ],
        'organization' => [
            'electricity' => [
                'description' => 'Elektros paslauga nuomininkų suvartojimo atsiskaitymui.',
            ],
            'water' => [
                'description' => 'Vandens paslauga su fiksuota ir kintama dalimi.',
            ],
            'heating' => [
                'description' => 'Šildymo paslauga sezoniniam naudojimui.',
            ],
        ],
    ],
    'tags' => [
        'legacy_foundation' => [
            'description' => 'Importuota iš seno bendradarbiavimo pagrindo.',
        ],
    ],
    'comments' => [
        'legacy_collaboration_imported' => [
            'body' => 'Senas bendradarbiavimo sluoksnis importuotas sėkmingai.',
        ],
    ],
    'attachments' => [
        'seeded_demo_collaboration_attachment' => [
            'description' => 'Importuotas demonstracinis bendradarbiavimo priedas.',
        ],
    ],
    'subscription_renewals' => [
        'seeded_legacy_operations_history' => [
            'notes' => 'Importuota senos operacijų sistemos prenumeratos atnaujinimo istorija.',
        ],
    ],
    'system_configurations' => [
        'platform_default_currency' => [
            'description' => 'Numatytoji atsiskaitymo valiuta platformos lygio operacijoms.',
        ],
    ],
    'time_entries' => [
        'seeded_progress_update' => [
            'description' => 'Importuotas progreso atnaujinimas.',
        ],
        'seeded_imported_collaboration_task' => [
            'description' => 'Importuotas demonstracinis laiko įrašas bendradarbiavimo užduočiai.',
        ],
    ],
    'activity_logs' => [
        'seeded_collaboration_foundation' => [
            'description' => 'Importuota bendradarbiavimo pagrindo veikla',
        ],
        'imported_from_legacy_collaboration_foundation' => [
            'description' => 'Importuota iš seno bendradarbiavimo pagrindo.',
        ],
    ],
];
