<?php

declare(strict_types=1);

return [
    'actions' => [
        'activate' => 'Активировать',
        'bulk_activate' => 'Массовая активация',
        'bulk_deactivate' => 'Массовая деактивация',
        'deactivate' => 'Деактивировать',
        'set_default' => 'Установить по умолчанию',
    ],
    'empty' => [
        'action' => 'Действие',
        'description' => 'Описание',
        'heading' => 'Заголовок',
    ],
    'errors' => [
        'cannot_deactivate_default' => 'Невозможно деактивировать значение по умолчанию',
        'cannot_delete_default' => 'Невозможно удалить значение по умолчанию',
        'cannot_delete_last_active' => 'Невозможно удалить последний активный',
    ],
    'filters' => [
        'active_only' => 'Только активный',
        'active_placeholder' => 'Активный заполнитель',
        'default_only' => 'Только по умолчанию',
        'default_placeholder' => 'Заполнитель по умолчанию',
        'inactive_only' => 'Только неактивный',
        'non_default_only' => 'Только не по умолчанию',
    ],
    'helper_text' => [
        'active' => 'Активный',
        'code' => 'Код',
        'default' => 'По умолчанию',
        'details' => 'Подробности',
        'name' => 'Имя',
        'native_name' => 'Родное имя',
        'order' => 'Заказ',
    ],
    'labels' => [
        'active' => 'Активный',
        'code' => 'Код',
        'created' => 'Созданный',
        'default' => 'По умолчанию',
        'locale' => 'Языковой стандарт',
        'name' => 'Имя',
        'native_name' => 'Родное имя',
        'order' => 'Заказ',
    ],
    'messages' => [
        'code_copied' => 'Код скопирован',
    ],
    'modals' => [
        'delete' => [
            'description' => 'Описание',
            'heading' => 'Заголовок',
        ],
        'set_default' => [
            'description' => 'Описание',
            'heading' => 'Заголовок',
        ],
    ],
    'navigation' => 'Языки',
    'notifications' => [
        'default_set' => 'Набор по умолчанию',
    ],
    'placeholders' => [
        'code' => 'Код',
        'name' => 'Имя',
        'native_name' => 'Родное имя',
    ],
    'sections' => [
        'details' => 'Подробности',
        'settings' => 'Настройки',
    ],
    'validation' => [
        'code_format' => 'Формат кода',
        'locale' => [
            'max' => 'Макс',
            'required' => 'Необходимый',
            'string' => 'Нить',
        ],
    ],
];
