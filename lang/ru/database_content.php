<?php

return [
    'meters' => [
        'generated_demo_meter' => [
            'name' => 'Демонстрационный счетчик: :type',
        ],
        'operations_demo_meter' => [
            'name' => 'Демонстрационный операционный счетчик: :type',
        ],
    ],
    'meter_readings' => [
        'notes' => [
            'seeded_legacy_operations_reading' => 'Импортированное показание из старой операционной системы.',
        ],
        'change_reasons' => [
            'seeded_baseline_validation_check' => 'Импортированная причина базовой проверки',
        ],
    ],
    'buildings' => [
        'demo_building' => [
            'name' => 'Демонстрационное здание :number',
        ],
    ],
    'properties' => [
        'demo_unit' => [
            'name' => 'Демонстрационное помещение :number',
        ],
    ],
    'invoices' => [
        'demo_invoice' => [
            'notes' => 'Демонстрационный счет :number: :property',
        ],
        'seeded_login_demo_invoice' => [
            'notes' => 'Импортированный демонстрационный счет для входа :number',
        ],
        'legacy_operations_foundation_demo_invoice' => [
            'notes' => 'Демонстрационный счет старой операционной системы.',
        ],
    ],
    'billing_records' => [
        'seeded_billing_record' => [
            'notes' => 'Импортированная расчетная запись: :description',
        ],
        'seeded_demo_invoice_record' => [
            'notes' => 'Импортированная расчетная запись для демонстрационного счета.',
        ],
    ],
    'projects' => [
        'legacy_collaboration_demo_project' => [
            'name' => 'Демонстрационный проект старого модуля совместной работы',
            'description' => 'Импортированный демонстрационный проект основы совместной работы.',
        ],
        'modernization_program' => [
            'name' => 'Программа модернизации :name',
            'description' => 'План операционного улучшения для :name.',
        ],
    ],
    'tasks' => [
        'demo_task' => [
            'title' => 'Демонстрационная задача :number',
        ],
        'inspect_shared_utility_setup' => [
            'title' => 'Проверить настройки общих коммунальных услуг',
            'description' => 'Проверить импортированные данные конфигурации коммунальных услуг.',
        ],
        'review_imported_collaboration_layer' => [
            'title' => 'Проверить импортированный слой совместной работы',
            'description' => 'Более детальная задача, импортированная из старого домена совместной работы.',
        ],
        'inspect_shared_systems' => [
            'description' => 'Проверить общие системы.',
        ],
        'coordinate_resident_communication' => [
            'description' => 'Согласовать коммуникацию с жильцами.',
        ],
    ],
    'task_assignments' => [
        'seeded_operational_assignment' => [
            'notes' => 'Импортированное операционное назначение.',
        ],
        'demo_tenant_assignment' => [
            'notes' => 'Демонстрационное назначение арендатора для основы совместной работы.',
        ],
    ],
    'utility_services' => [
        'organization_service' => [
            'name' => 'Организация :number: :service',
        ],
        'global' => [
            'electricity' => [
                'description' => 'Начисления за потребление электроэнергии для жилых объектов.',
            ],
            'water' => [
                'description' => 'Начисления за водоснабжение и канализацию с фиксированной и переменной частью.',
            ],
            'heating' => [
                'description' => 'Начисления за централизованное отопление.',
            ],
        ],
        'organization' => [
            'electricity' => [
                'description' => 'Услуга электроэнергии для расчета потребления арендаторов.',
            ],
            'water' => [
                'description' => 'Услуга водоснабжения с фиксированной и переменной частью.',
            ],
            'heating' => [
                'description' => 'Услуга отопления для сезонного использования.',
            ],
        ],
    ],
    'tags' => [
        'legacy_foundation' => [
            'description' => 'Импортировано из старой основы совместной работы.',
        ],
    ],
    'comments' => [
        'legacy_collaboration_imported' => [
            'body' => 'Слой старой совместной работы успешно импортирован.',
        ],
    ],
    'attachments' => [
        'seeded_demo_collaboration_attachment' => [
            'description' => 'Импортированное демонстрационное вложение для совместной работы.',
        ],
    ],
    'subscription_renewals' => [
        'seeded_legacy_operations_history' => [
            'notes' => 'Импортированная история продления подписки из старой операционной системы.',
        ],
    ],
    'system_configurations' => [
        'platform_default_currency' => [
            'description' => 'Валюта расчетов по умолчанию для операций уровня платформы.',
        ],
    ],
    'time_entries' => [
        'seeded_progress_update' => [
            'description' => 'Импортированное обновление прогресса.',
        ],
        'seeded_imported_collaboration_task' => [
            'description' => 'Импортированная демонстрационная запись времени для задачи совместной работы.',
        ],
    ],
    'activity_logs' => [
        'seeded_collaboration_foundation' => [
            'description' => 'Импортированная активность основы совместной работы',
        ],
        'imported_from_legacy_collaboration_foundation' => [
            'description' => 'Импортировано из старой основы совместной работы.',
        ],
    ],
];
