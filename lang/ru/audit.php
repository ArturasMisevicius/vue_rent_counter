<?php

declare(strict_types=1);

return [
    'navigation' => 'Журналы активности',
    'labels' => [
        'timestamp' => 'Метка времени',
        'organization' => 'Организация',
        'user' => 'Пользователь',
        'action' => 'Действие',
        'resource' => 'Ресурс',
        'resource_type' => 'Тип ресурса',
        'resource_id' => 'ID ресурса',
        'ip_address' => 'IP-адрес',
        'user_agent' => 'User Agent',
        'details' => 'Детали',
        'action_type' => 'Тип действия',
        'additional_data' => 'Дополнительные данные',
    ],
    'filters' => [
        'from' => 'С',
        'until' => 'До',
        'create' => 'Создание',
        'update' => 'Обновление',
        'delete' => 'Удаление',
        'view' => 'Просмотр',
    ],
    'sections' => [
        'activity_details' => 'Детали активности',
        'request_information' => 'Информация о запросе',
        'metadata' => 'Метаданные',
    ],
    'pages' => [
        'index' => [
            'title' => 'Журнал аудита',
            'breadcrumb' => 'Журнал аудита',
            'description' => 'Просмотр активности системы и изменений показаний счетчиков',
            'filters' => [
                'from_date' => 'Дата с',
                'to_date' => 'Дата по',
                'meter_serial' => 'Номер счетчика',
                'meter_placeholder' => 'Поиск по номеру...',
                'apply' => 'Применить фильтры',
                'clear' => 'Сбросить',
            ],
            'table' => [
                'caption' => 'Журнал аудита',
                'timestamp' => 'Время',
                'meter' => 'Счетчик',
                'reading_date' => 'Дата показания',
                'old_value' => 'Предыдущее значение',
                'new_value' => 'Новое значение',
                'changed_by' => 'Кем изменено',
                'reason' => 'Причина',
                'reading' => 'Показание:',
            ],
            'states' => [
                'not_available' => 'Н/Д',
                'system' => 'Система',
                'empty' => 'Записей аудита не найдено.',
                'clear_filters' => 'Сбросить фильтры',
                'see_all' => 'чтобы увидеть все записи.',
                'by' => 'Кем:',
                'old_short' => 'Старое:',
                'new_short' => 'Новое:',
            ],
        ],
    ],
];
