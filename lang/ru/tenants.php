<?php

return [
    'actions' => [
        'add' => 'Добавлять',
        'deactivate' => 'Деактивировать',
        'reactivate' => 'Повторно активировать',
        'reassign' => 'Переназначить',
        'view' => 'Вид',
    ],
    'empty' => [
        'assignment_history' => 'История назначений',
        'list' => 'Список',
        'list_cta' => 'Список призывов к действию',
        'property' => 'Свойство',
        'recent_invoices' => 'Последние счета',
        'recent_readings' => 'Недавние чтения',
    ],
    'headings' => [
        'account' => 'Счет',
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
        'area' => 'Область',
        'created' => 'Созданный',
        'created_by' => 'Создано',
        'email' => 'Электронная почта',
        'invoice' => 'Счет',
        'name' => 'Имя',
        'property' => 'Свойство',
        'reading' => 'Чтение',
        'reason' => 'Причина',
        'status' => 'Статус',
        'type' => 'Тип',
    ],
    'pages' => [
        'admin_form' => [
            'actions' => [
                'cancel' => 'Отмена',
                'submit' => 'Представлять на рассмотрение',
            ],
            'errors_title' => 'Название ошибки',
            'labels' => [
                'email' => 'Электронная почта',
                'name' => 'Имя',
                'password' => 'Пароль',
                'password_confirmation' => 'Подтверждение пароля',
                'property' => 'Свойство',
            ],
            'notes' => [
                'credentials_sent' => 'Учетные данные отправлены',
                'no_properties' => 'Нет свойств',
            ],
            'placeholders' => [
                'property' => 'Выберите объект недвижимости',
            ],
            'subtitle' => 'Создайте аккаунт арендатора и назначьте его объекту недвижимости в вашем портфеле.',
            'title' => 'Создать арендатора',
        ],
        'reassign' => [
            'actions' => [
                'cancel' => 'Отмена',
                'submit' => 'Представлять на рассмотрение',
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
        'active' => 'Активный',
        'inactive' => 'Неактивный',
    ],
    'validation' => [
        'email' => [
            'email' => 'Электронная почта',
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
            'string' => 'Нить',
        ],
        'phone' => [
            'max' => 'Макс',
            'string' => 'Нить',
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
