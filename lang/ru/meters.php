<?php

return [
    'actions' => [
        'add' => 'Добавлять',
        'create' => 'Создавать',
        'delete' => 'Удалить',
        'edit' => 'Редактировать',
        'edit_meter' => 'Редактировать счетчик',
        'view' => 'Вид',
        'view_readings' => 'Просмотр показаний',
    ],
    'confirmations' => [
        'delete' => 'Удалить',
    ],
    'empty_state' => [
        'description' => 'Описание',
        'heading' => 'Заголовок',
    ],
    'errors' => [
        'has_readings' => 'Есть чтения',
    ],
    'filters' => [
        'no_readings' => 'Нет показаний',
        'property' => 'Свойство',
        'supports_zones' => 'Поддерживает зоны',
        'type' => 'Тип',
    ],
    'headings' => [
        'information' => 'Информация',
        'show' => 'Показывать',
        'show_description' => 'Показать описание',
    ],
    'helper_text' => [
        'installation_date' => 'Дата установки',
        'property' => 'Свойство',
        'serial_number' => 'Серийный номер',
        'supports_zones' => 'Поддерживает зоны',
        'type' => 'Тип',
    ],
    'labels' => [
        'created' => 'Созданный',
        'installation_date' => 'Дата установки',
        'meter' => 'Метр',
        'meters' => 'Метры',
        'property' => 'Свойство',
        'readings' => 'Чтения',
        'readings_count' => 'Количество показаний',
        'serial_number' => 'Серийный номер',
        'supports_zones' => 'Поддерживает зоны',
        'type' => 'Тип',
    ],
    'manager' => [
        'index' => [
            'caption' => 'Подпись',
            'description' => 'Описание',
            'empty' => [
                'cta' => 'призыв к действию',
                'text' => 'Текст',
            ],
            'headers' => [
                'actions' => 'Действия',
                'installation_date' => 'Дата установки',
                'latest_reading' => 'Последнее чтение',
                'property' => 'Свойство',
                'serial_number' => 'Серийный номер',
                'type' => 'Тип',
                'zones' => 'Зоны',
            ],
            'title' => 'Заголовок',
            'zones' => [
                'no' => 'Нет',
                'yes' => 'Да',
            ],
        ],
    ],
    'modals' => [
        'bulk_delete' => [
            'confirm' => 'Подтверждать',
            'description' => 'Описание',
            'title' => 'Заголовок',
        ],
        'delete_confirm' => 'Удалить Подтвердить',
        'delete_description' => 'Удалить описание',
        'delete_heading' => 'Удалить заголовок',
    ],
    'notifications' => [
        'created' => 'Созданный',
        'updated' => 'Обновлено',
    ],
    'placeholders' => [
        'serial_number' => 'Серийный номер',
    ],
    'relation' => [
        'add_first' => 'Добавить первым',
        'empty_description' => 'Пустое описание',
        'empty_heading' => 'Пустой заголовок',
        'initial_reading' => 'Первоначальное чтение',
        'installation_date' => 'Дата установки',
        'installed' => 'Установлено',
        'meter_type' => 'Тип счетчика',
        'readings' => 'Чтения',
        'serial_number' => 'Серийный номер',
        'type' => 'Тип',
    ],
    'sections' => [
        'meter_details' => 'Детали счетчика',
        'meter_details_description' => 'Описание счетчика',
    ],
    'tooltips' => [
        'copy_serial' => 'Копировать серийный номер',
        'property_address' => 'Адрес объекта недвижимости',
        'readings_count' => 'Количество показаний',
        'supports_zones_no' => 'Поддерживает зоны Нет',
        'supports_zones_yes' => 'Поддерживает зоны Да',
    ],
    'units' => [
        'kwh' => 'кВтч',
    ],
    'validation' => [
        'installation_date' => [
            'before_or_equal' => 'Раньше или равно',
            'date' => 'Дата',
            'required' => 'Необходимый',
        ],
        'property_id' => [
            'exists' => 'Существует',
            'required' => 'Необходимый',
        ],
        'serial_number' => [
            'max' => 'Макс',
            'required' => 'Необходимый',
            'string' => 'Нить',
            'unique' => 'Уникальный',
        ],
        'supports_zones' => [
            'boolean' => 'логическое значение',
        ],
        'tenant_id' => [
            'integer' => 'Целое число',
            'required' => 'Необходимый',
        ],
        'type' => [
            'enum_detail' => 'Подробности перечисления',
            'required' => 'Необходимый',
        ],
    ],
];
