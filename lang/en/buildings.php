<?php

declare(strict_types=1);

return [
    'labels' => [
        'name' => 'Name',
        'address' => 'Address',
        'total_apartments' => 'Total Apartments',
        'property_count' => 'Properties',
        'created_at' => 'Created',
        'building' => 'Здание',
        'buildings' => 'Здания',
    ],
    'validation' => [
        'tenant_id' => [
            'required' => 'Organization is required.',
            'integer' => 'Organization ID must be a number.',
            'exists' => 'The selected organization does not exist.',
        ],
        'name' => [
            'required' => 'Building name is required.',
            'string' => 'Building name must be text.',
            'max' => 'Building name may not be greater than 255 characters.',
            'regex' => 'Building name may only contain letters, numbers, spaces, hyphens, underscores, dots, and hash symbols.',
            'unique' => 'A building with this name already exists in your organization.',
        ],
        'address' => [
            'required' => 'Address is required.',
            'string' => 'Address must be text.',
            'max' => 'Address may not be greater than 500 characters.',
            'regex' => 'Address contains invalid characters.',
        ],
        'city' => [
            'regex' => 'City name may only contain letters, spaces, and hyphens.',
        ],
        'postal_code' => [
            'regex' => 'Postal code format is invalid.',
        ],
        'country' => [
            'size' => 'Country code must be exactly 2 characters.',
        ],
        'total_apartments' => [
            'required' => 'Total number of apartments is required.',
            'integer' => 'Total apartments must be a number.',
            'min' => 'Building must have at least 1 apartment.',
            'max' => 'Building cannot have more than 1000 apartments.',
        ],
        'built_year' => [
            'min' => 'Built year cannot be earlier than 1800.',
            'max' => 'Built year cannot be more than 5 years in the future.',
        ],
        'heating_type' => [
            'in' => 'The selected heating type is invalid.',
        ],
        'parking_spaces' => [
            'max' => 'Parking spaces cannot exceed 500.',
        ],
        'notes' => [
            'max' => 'Notes may not be greater than 2000 characters.',
        ],
        'apartments_per_floor_excessive' => 'The number of apartments per floor seems excessive. Please verify.',
        'central_heating_anachronistic' => 'Central heating was not common in buildings built before 1900.',
    ],
    'attributes' => [
        'tenant_id' => 'organization',
        'name' => 'building name',
        'address' => 'address',
        'city' => 'city',
        'postal_code' => 'postal code',
        'country' => 'country',
        'total_apartments' => 'total apartments',
        'floors' => 'floors',
        'built_year' => 'year built',
        'heating_type' => 'heating type',
        'elevator' => 'elevator',
        'parking_spaces' => 'parking spaces',
        'notes' => 'notes',
    ],
    'errors' => [
        'has_properties' => 'Имеет свойства',
    ],
    'pages' => [
        'manager_form' => [
            'actions' => [
                'cancel' => 'Отмена',
                'save_create' => 'Сохранить Создать',
                'save_edit' => 'Сохранить Редактировать',
            ],
            'create_subtitle' => 'Создать субтитры',
            'create_title' => 'Создать заголовок',
            'edit_subtitle' => 'Редактировать субтитры',
            'edit_title' => 'Изменить заголовок',
            'labels' => [
                'address' => 'Адрес',
                'name' => 'Имя',
                'total_apartments' => 'Всего квартир',
            ],
            'placeholders' => [
                'address' => 'Адрес',
                'name' => 'Имя',
                'total_apartments' => 'Всего квартир',
            ],
        ],
        'manager_index' => [
            'add' => 'Добавлять',
            'create_now' => 'Создать сейчас',
            'description' => 'Описание',
            'empty' => 'Пустой',
            'headers' => [
                'actions' => 'Действия',
                'building' => 'Здание',
                'last_calculated' => 'Последний расчет',
                'properties' => 'Характеристики',
                'total_apartments' => 'Всего квартир',
            ],
            'mobile' => [
                'apartments' => 'Квартиры',
                'edit' => 'Редактировать',
                'last' => 'Последний',
                'properties' => 'Характеристики',
                'view' => 'Вид',
            ],
            'never' => 'Никогда',
            'not_calculated' => 'Не рассчитано',
            'table_caption' => 'Заголовок таблицы',
            'title' => 'Заголовок',
        ],
        'manager_show' => [
            'add_property' => 'Добавить недвижимость',
            'calculated' => 'Рассчитано',
            'delete_building' => 'Удалить здание',
            'delete_confirm' => 'Удалить Подтвердить',
            'description' => 'Описание',
            'edit_building' => 'Редактировать здание',
            'empty_properties' => 'Пустые свойства',
            'form' => [
                'end_date' => 'Дата окончания',
                'start_date' => 'Дата начала',
                'submit' => 'Представлять на рассмотрение',
            ],
            'info_title' => 'Информация Название',
            'labels' => [
                'address' => 'Адрес',
                'name' => 'Имя',
                'properties_registered' => 'Недвижимость зарегистрирована',
                'total_apartments' => 'Всего квартир',
            ],
            'last_calculated' => 'Последний расчет',
            'never' => 'Никогда',
            'not_calculated' => 'Не рассчитано',
            'pending' => 'В ожидании',
            'properties_headers' => [
                'actions' => 'Действия',
                'address' => 'Адрес',
                'area' => 'Область',
                'meters' => 'Метры',
                'tenant' => 'Жилец',
                'type' => 'Тип',
            ],
            'properties_title' => 'Название недвижимости',
            'status' => 'Статус',
            'summer_average' => 'Летний средний показатель',
            'title' => 'Заголовок',
            'vacant' => 'Вакантный',
            'view' => 'Вид',
        ],
        'show' => [
            'heading' => 'Заголовок',
            'title' => 'Заголовок',
        ],
    ],
];
