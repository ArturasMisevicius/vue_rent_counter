<?php

return [
    'actions' => [
        'add' => 'Добавлять',
        'back' => 'Назад',
        'clear' => 'Прозрачный',
        'create' => 'Создавать',
        'delete' => 'Удалить',
        'edit' => 'Редактировать',
        'filter' => 'Фильтр',
        'update' => 'Обновлять',
        'view' => 'Вид',
    ],
    'descriptions' => [
        'index' => 'Индекс',
    ],
    'empty' => [
        'users' => 'Пользователи',
    ],
    'empty_state' => [
        'description' => 'Описание',
        'heading' => 'Заголовок',
    ],
    'errors' => [
        'has_readings' => 'Есть чтения',
    ],
    'filters' => [
        'active_only' => 'Только активный',
        'all_users' => 'Все пользователи',
        'inactive_only' => 'Только неактивный',
        'is_active' => 'Активен',
        'role' => 'Роль',
    ],
    'headings' => [
        'create' => 'Создавать',
        'edit' => 'Редактировать',
        'index' => 'Индекс',
        'information' => 'Информация',
        'quick_actions' => 'Быстрые действия',
        'show' => 'Показывать',
    ],
    'helper_text' => [
        'is_active' => 'Активен',
        'password' => 'Пароль',
        'role' => 'Роль',
        'tenant' => 'Жилец',
    ],
    'labels' => [
        'activity_hint' => 'Подсказка по активности',
        'activity_history' => 'История активности',
        'created' => 'Созданный',
        'created_at' => 'Создано в',
        'email' => 'Электронная почта',
        'is_active' => 'Активен',
        'last_login_at' => 'Последний вход в систему',
        'meter_readings_entered' => 'Введены показания счетчика',
        'name' => 'Имя',
        'no_activity' => 'Нет активности',
        'password' => 'Пароль',
        'password_confirmation' => 'Подтверждение пароля',
        'role' => 'Роль',
        'tenant' => 'Жилец',
        'updated_at' => 'Обновлено в',
        'user' => 'Пользователь',
        'users' => 'Пользователи',
    ],
    'placeholders' => [
        'email' => 'Электронная почта',
        'name' => 'Имя',
        'password' => 'Пароль',
        'password_confirmation' => 'Подтверждение пароля',
    ],
    'sections' => [
        'role_and_access' => 'Роль и доступ',
        'role_and_access_description' => 'Описание роли и доступа',
        'user_details' => 'Данные пользователя',
        'user_details_description' => 'Информация о пользователе Описание',
    ],
    'tables' => [
        'actions' => 'Действия',
        'email' => 'Электронная почта',
        'name' => 'Имя',
        'role' => 'Роль',
        'tenant' => 'Жилец',
    ],
    'tooltips' => [
        'copy_email' => 'Копировать электронную почту',
    ],
    'validation' => [
        'current_password' => [
            'current_password' => 'Текущий пароль',
            'required' => 'Необходимый',
            'required_with' => 'Требуется с',
            'string' => 'Нить',
        ],
        'email' => [
            'email' => 'Электронная почта',
            'max' => 'Макс',
            'required' => 'Необходимый',
            'string' => 'Нить',
            'unique' => 'Уникальный',
        ],
        'name' => [
            'max' => 'Макс',
            'required' => 'Необходимый',
            'string' => 'Нить',
        ],
        'organization_name' => [
            'max' => 'Макс',
            'string' => 'Нить',
        ],
        'password' => [
            'confirmed' => 'Подтвержденный',
            'min' => 'Мин',
            'required' => 'Необходимый',
            'string' => 'Нить',
        ],
        'role' => [
            'enum' => 'Перечисление',
            'required' => 'Необходимый',
        ],
        'tenant_id' => [
            'exists' => 'Существует',
            'integer' => 'Целое число',
            'required' => 'Необходимый',
        ],
    ],
];
