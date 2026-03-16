<?php

return [
    'actions' => [
        'add' => 'Добавить',
        'add_first_property' => 'Добавить первый объект',
        'assign_tenant' => 'Назначить арендатора',
        'edit' => 'Редактировать',
        'export_selected' => 'Экспортировать выбранное',
        'manage_tenant' => 'Управление арендатором',
        'reassign_tenant' => 'Переназначить арендатора',
        'view' => 'Просмотреть',
    ],
    'badges' => [
        'vacant' => 'Свободен',
    ],
    'empty_state' => [
        'description' => 'Описание',
        'heading' => 'Заголовок',
    ],
    'errors' => [
        'has_relations' => 'Имеет отношения',
    ],
    'filters' => [
        'all_properties' => 'Все объекты',
        'building' => 'Здание',
        'large_properties' => 'Крупные объекты',
        'occupancy' => 'Заселенность',
        'occupied' => 'Заселен',
        'type' => 'Тип',
        'vacant' => 'Свободен',
        'tags' => 'Теги',
    ],
    'helper_text' => [
        'address' => 'Адрес',
        'area' => 'Площадь',
        'tenant_available' => 'Доступен арендатор',
        'tenant_reassign' => 'Переназначение арендатора',
        'type' => 'Тип',
        'tags' => 'Выберите теги для этого объекта',
    ],
    'labels' => [
        'address' => 'Адрес',
        'area' => 'Площадь',
        'building' => 'Здание',
        'created' => 'Создано',
        'current_tenant' => 'Текущий арендатор',
        'installed_meters' => 'Установленные счетчики',
        'meters' => 'Счетчики',
        'properties' => 'Объекты',
        'property' => 'Объект',
        'type' => 'Тип',
        'tags' => 'Теги',
    ],
    'manager' => [
        'index' => [
            'caption' => 'Подпись',
            'description' => 'Описание',
            'empty' => [
                'cta' => 'призыв к действию',
                'text' => 'Текст',
            ],
            'filters' => [
                'all_buildings' => 'Все здания',
                'all_types' => 'Все типы',
                'building' => 'Здание',
                'clear' => 'Сбросить',
                'filter' => 'Фильтр',
                'search' => 'Поиск',
                'search_placeholder' => 'Заполнитель поиска',
                'type' => 'Тип',
            ],
            'headers' => [
                'actions' => 'Действия',
                'address' => 'Адрес',
                'area' => 'Площадь',
                'building' => 'Здание',
                'meters' => 'Счетчики',
                'tenants' => 'Арендаторы',
                'type' => 'Тип',
            ],
            'title' => 'Заголовок',
        ],
    ],
    'modals' => [
        'bulk_delete' => [
            'confirm' => 'Подтвердить',
            'description' => 'Описание',
            'title' => 'Заголовок',
        ],
        'delete_confirmation' => 'Подтверждение удаления',
    ],
    'notifications' => [
        'bulk_deleted' => [
            'body' => 'Тело',
            'title' => 'Заголовок',
        ],
        'created' => [
            'body' => 'Тело',
            'title' => 'Заголовок',
        ],
        'deleted' => [
            'body' => 'Тело',
            'title' => 'Заголовок',
        ],
        'export_started' => [
            'body' => 'Тело',
            'title' => 'Заголовок',
        ],
        'updated' => [
            'body' => 'Тело',
            'title' => 'Заголовок',
        ],
        '{$action}' => [
            'body' => 'Тело',
            'title' => 'Заголовок',
        ],
    ],
    'pages' => [
        'superadmin_index' => [
            'description' => 'Все объекты во всех организациях',
        ],
        'superadmin_show' => [
            'sections' => [
                'invoices_description' => 'Счета-фактуры для арендаторов этого объекта недвижимости',
                'meters_description' => 'Счетчики закреплены за этим объектом недвижимости',
                'property_details' => 'Данные объекта',
            ],
        ],
        'manager_form' => [
            'actions' => [
                'cancel' => 'Отмена',
                'save_create' => 'Сохранить и создать',
                'save_edit' => 'Сохранить изменения',
            ],
            'create_subtitle' => 'Создать субтитры',
            'create_title' => 'Создать заголовок',
            'edit_subtitle' => 'Редактировать субтитры',
            'edit_title' => 'Изменить заголовок',
            'labels' => [
                'address' => 'Адрес',
                'area' => 'Площадь',
                'building' => 'Здание',
                'type' => 'Тип',
            ],
            'placeholders' => [
                'address' => 'Адрес',
                'area' => 'Площадь',
                'building' => 'Здание',
            ],
        ],
        'manager_show' => [
            'add_meter' => 'Добавить счетчик',
            'building_missing' => 'Здание отсутствует',
            'current_tenant_title' => 'Текущее название арендатора',
            'delete_confirm' => 'Удалить Подтвердить',
            'delete_property' => 'Удалить объект',
            'description' => 'Описание',
            'edit_property' => 'Редактировать объект',
            'info_title' => 'Информация Название',
            'labels' => [
                'address' => 'Адрес',
                'area' => 'Площадь',
                'building' => 'Здание',
                'type' => 'Тип',
            ],
            'latest_none' => 'Последние Нет',
            'meters_headers' => [
                'actions' => 'Действия',
                'installation' => 'Установка',
                'latest' => 'Последний',
                'serial' => 'Серийный',
                'type' => 'Тип',
            ],
            'meters_title' => 'Название метра',
            'no_meters_installed' => 'Счетчики не установлены',
            'no_tenant' => 'Нет арендатора',
            'tenant_labels' => [
                'email' => 'Эл. почта',
                'name' => 'Имя',
                'phone' => 'Телефон',
            ],
            'tenant_na' => 'Арендатор На',
            'title' => 'Заголовок',
            'view' => 'Просмотреть',
        ],
    ],
    'placeholders' => [
        'address' => 'Адрес',
        'area' => 'Площадь',
    ],
    'sections' => [
        'additional_info' => 'Дополнительная информация',
        'additional_info_description' => 'Дополнительная информация',
        'property_details' => 'Данные объекта',
        'property_details_description' => 'Подробности о недвижимости Описание',
    ],
    'tooltips' => [
        'copy_address' => 'Копировать адрес',
        'meters_count' => 'Количество метров',
        'no_tenant' => 'Нет арендатора',
        'occupied_by' => 'Занят',
    ],
    'validation' => [
        'address' => [
            'format' => 'Формат',
            'invalid_characters' => 'Недопустимые символы',
            'max' => 'Макс',
            'prohibited_content' => 'Запрещенный контент',
            'required' => 'Необходимый',
            'string' => 'Строка',
        ],
        'area_sqm' => [
            'format' => 'Формат',
            'max' => 'Макс',
            'min' => 'Мин',
            'negative' => 'Отрицательный',
            'numeric' => 'Числовой',
            'precision' => 'Точность',
            'required' => 'Необходимый',
        ],
        'building_id' => [
            'exists' => 'Существует',
        ],
        'property_id' => [
            'exists' => 'Существует',
            'required' => 'Необходимый',
        ],
        'tenant_id' => [
            'integer' => 'Целое число',
            'required' => 'Необходимый',
        ],
        'type' => [
            'enum' => 'Перечисление',
            'required' => 'Необходимый',
        ],
    ],
];
