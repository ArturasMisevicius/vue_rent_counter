<?php

return [
    'actions' => [
        'add' => 'Добавить',
        'deactivate' => 'Деактивировать',
        'reactivate' => 'Повторно активировать',
        'reassign' => 'Переназначить',
        'view' => 'Просмотреть',
    ],
    'empty' => [
        'assignment_history' => 'История назначений',
        'list' => 'Список',
        'list_cta' => 'Список призывов к действию',
        'property' => 'Объект',
        'recent_invoices' => 'Последние счета',
        'recent_readings' => 'Недавние чтения',
    ],
    'headings' => [
        'account' => 'Аккаунт',
        'assignment_history' => 'История назначений',
        'current_property' => 'Текущая недвижимость',
        'index' => 'Индекс',
        'index_description' => 'Индекс Описание',
        'list' => 'Список',
        'recent_invoices' => 'Последние счета',
        'recent_readings' => 'Недавние чтения',
        'show' => 'Показывать',
    ],
    'labels' => [
        'actions' => 'Действия',
        'address' => 'Адрес',
        'area' => 'Площадь',
        'building' => 'Здание',
        'created' => 'Создано',
        'created_by' => 'Создано',
        'email' => 'Эл. почта',
        'invoice' => 'Счет',
        'name' => 'Имя',
        'phone' => 'Телефон',
        'property' => 'Объект',
        'reading' => 'Чтение',
        'reason' => 'Причина',
        'status' => 'Статус',
        'type' => 'Тип',
    ],
    'pages' => [
        'index' => [
            'subtitle' => 'Все арендаторы во всех организациях',
            'title' => 'Арендаторы',
        ],
        'admin_form' => [
            'actions' => [
                'cancel' => 'Отмена',
                'submit' => 'Отправить',
            ],
            'errors_title' => 'Название ошибки',
            'labels' => [
                'email' => 'Эл. почта',
                'name' => 'Имя',
                'password' => 'Пароль',
                'password_confirmation' => 'Подтверждение пароля',
                'property' => 'Объект',
            ],
            'notes' => [
                'credentials_sent' => 'Учетные данные отправлены',
                'no_properties' => 'Нет свойств',
            ],
            'placeholders' => [
                'property' => 'Выберите недвижимость',
            ],
            'subtitle' => 'Создайте учетную запись арендатора и назначьте ее объекту недвижимости в своем портфолио.',
            'title' => 'Создать арендатора',
        ],
        'reassign' => [
            'actions' => [
                'cancel' => 'Отмена',
                'submit' => 'Отправить',
            ],
            'current_property' => [
                'empty' => 'Пустой',
                'title' => 'Заголовок',
            ],
            'errors_title' => 'Название ошибки',
            'history' => [
                'empty' => 'Пустой',
                'title' => 'Заголовок',
            ],
            'new_property' => [
                'empty' => 'Пустой',
                'label' => 'Этикетка',
                'note' => 'Примечание',
                'placeholder' => 'Заполнитель',
            ],
            'subtitle' => 'Субтитры',
            'title' => 'Заголовок',
            'warning' => [
                'items' => [
                    'audit' => 'Аудит',
                    'notify' => 'Уведомить',
                    'preserved' => 'Сохранился',
                ],
                'title' => 'Заголовок',
            ],
        ],
    ],
    'statuses' => [
        'active' => 'Активен',
        'inactive' => 'Неактивен',
    ],
    'sections' => [
        'details' => 'Подробности',
        'invoices' => 'Счета',
        'stats' => 'Статистика',
    ],
    'validation' => [
        'email' => [
            'email' => 'Эл. почта',
            'max' => 'Макс',
            'required' => 'Необходимый',
        ],
        'invoice_id' => [
            'exists' => 'Существует',
            'required' => 'Необходимый',
        ],
        'lease_end' => [
            'after' => 'После',
            'date' => 'Дата',
        ],
        'lease_start' => [
            'date' => 'Дата',
            'required' => 'Необходимый',
        ],
        'name' => [
            'max' => 'Макс',
            'required' => 'Необходимый',
            'string' => 'Строка',
        ],
        'phone' => [
            'max' => 'Макс',
            'string' => 'Строка',
        ],
        'property_id' => [
            'exists' => 'Существует',
            'required' => 'Необходимый',
        ],
        'tenant_id' => [
            'integer' => 'Целое число',
            'required' => 'Необходимый',
        ],
    ],
];
