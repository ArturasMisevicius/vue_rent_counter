<?php

declare(strict_types=1);

return [
    'errors' => [
        'has_properties' => 'Имеет свойства',
    ],
    'labels' => [
        'address' => 'Адрес',
        'building' => 'Здание',
        'buildings' => 'Здания',
        'created_at' => 'Создано в',
        'name' => 'Имя',
        'property_count' => 'Количество недвижимости',
        'total_apartments' => 'Всего квартир',
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
    'validation' => [
        'address' => [
            'max' => 'Макс',
            'required' => 'Необходимый',
            'string' => 'Нить',
        ],
        'name' => [
            'max' => 'Макс',
            'required' => 'Необходимый',
            'string' => 'Нить',
        ],
        'tenant_id' => [
            'integer' => 'Целое число',
            'required' => 'Необходимый',
        ],
        'total_apartments' => [
            'integer' => 'Целое число',
            'max' => 'Макс',
            'min' => 'Мин',
            'required' => 'Необходимый',
        ],
    ],
];
