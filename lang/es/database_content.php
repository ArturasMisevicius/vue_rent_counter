<?php

return [
    'meters' => [
        'generated_demo_meter' => [
            'name' => 'Contador de demostración: :type',
        ],
        'operations_demo_meter' => [
            'name' => 'Contador de demostración de operaciones: :type',
        ],
    ],
    'meter_readings' => [
        'notes' => [
            'seeded_legacy_operations_reading' => 'Lectura importada del sistema operativo heredado.',
        ],
        'change_reasons' => [
            'seeded_baseline_validation_check' => 'Motivo importado de validación base',
        ],
    ],
    'buildings' => [
        'demo_building' => [
            'name' => 'Edificio de demostración :number',
        ],
    ],
    'properties' => [
        'demo_unit' => [
            'name' => 'Unidad de demostración :number',
        ],
    ],
    'invoices' => [
        'demo_invoice' => [
            'notes' => 'Factura de demostración :number: :property',
        ],
        'seeded_login_demo_invoice' => [
            'notes' => 'Factura de demostración de acceso importada :number',
        ],
        'legacy_operations_foundation_demo_invoice' => [
            'notes' => 'Factura de demostración del sistema operativo heredado.',
        ],
    ],
    'billing_records' => [
        'seeded_billing_record' => [
            'notes' => 'Registro de facturación importado: :description',
        ],
        'seeded_demo_invoice_record' => [
            'notes' => 'Registro de facturación importado para factura de demostración.',
        ],
    ],
    'projects' => [
        'legacy_collaboration_demo_project' => [
            'name' => 'Proyecto de demostración de colaboración heredada',
            'description' => 'Proyecto de demostración importado de la base de colaboración heredada.',
        ],
        'modernization_program' => [
            'name' => 'Programa de modernización de :name',
            'description' => 'Plan de mejora operativa para :name.',
        ],
    ],
    'tasks' => [
        'demo_task' => [
            'title' => 'Tarea de demostración :number',
        ],
        'inspect_shared_utility_setup' => [
            'title' => 'Inspeccionar la configuración de servicios compartidos',
            'description' => 'Revisar los datos importados de configuración de servicios.',
        ],
        'review_imported_collaboration_layer' => [
            'title' => 'Revisar la capa de colaboración importada',
            'description' => 'Tarea de mayor detalle importada del dominio de colaboración heredado.',
        ],
        'inspect_shared_systems' => [
            'description' => 'Inspeccionar los sistemas compartidos.',
        ],
        'coordinate_resident_communication' => [
            'description' => 'Coordinar la comunicación con residentes.',
        ],
    ],
    'task_assignments' => [
        'seeded_operational_assignment' => [
            'notes' => 'Asignación operativa importada.',
        ],
        'demo_tenant_assignment' => [
            'notes' => 'Asignación de inquilino de demostración para la base de colaboración.',
        ],
    ],
    'utility_services' => [
        'organization_service' => [
            'name' => 'Organización :number: :service',
        ],
        'global' => [
            'electricity' => [
                'description' => 'Cargos por consumo de electricidad para propiedades residenciales.',
            ],
            'water' => [
                'description' => 'Cargos de suministro de agua y alcantarillado con componente fijo y variable.',
            ],
            'heating' => [
                'description' => 'Cargos de calefacción urbana.',
            ],
        ],
        'organization' => [
            'electricity' => [
                'description' => 'Servicio de electricidad para facturación del consumo del inquilino.',
            ],
            'water' => [
                'description' => 'Servicio de agua con componentes fijos y variables.',
            ],
            'heating' => [
                'description' => 'Servicio de calefacción para uso estacional.',
            ],
        ],
    ],
    'tags' => [
        'legacy_foundation' => [
            'description' => 'Importado desde la base de colaboración heredada.',
        ],
    ],
    'comments' => [
        'legacy_collaboration_imported' => [
            'body' => 'La capa de colaboración heredada se importó correctamente.',
        ],
    ],
    'attachments' => [
        'seeded_demo_collaboration_attachment' => [
            'description' => 'Adjunto de colaboración de demostración importado.',
        ],
    ],
    'subscription_renewals' => [
        'seeded_legacy_operations_history' => [
            'notes' => 'Historial de renovación de suscripción importado del sistema operativo heredado.',
        ],
    ],
    'system_configurations' => [
        'platform_default_currency' => [
            'description' => 'Moneda de facturación predeterminada para operaciones de nivel de plataforma.',
        ],
    ],
    'time_entries' => [
        'seeded_progress_update' => [
            'description' => 'Actualización de progreso importada.',
        ],
        'seeded_imported_collaboration_task' => [
            'description' => 'Registro de tiempo de demostración importado para la tarea de colaboración.',
        ],
    ],
    'activity_logs' => [
        'seeded_collaboration_foundation' => [
            'description' => 'Actividad de base de colaboración importada',
        ],
        'imported_from_legacy_collaboration_foundation' => [
            'description' => 'Importado desde la base de colaboración heredada.',
        ],
    ],
];
