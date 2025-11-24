<?php

declare(strict_types=1);

return [
    'navigation' => 'Организации',

    'sections' => [
        'details' => 'Данные организации',
        'subscription' => 'Подписка и лимиты',
        'regional' => 'Региональные настройки',
        'status' => 'Статус',
    ],

    'labels' => [
        'name' => 'Название',
        'slug' => 'Слаг',
        'email' => 'Email',
        'phone' => 'Телефон',
        'domain' => 'Домен',
        'plan' => 'План',
        'max_properties' => 'Макс. объектов',
        'max_users' => 'Макс. пользователей',
        'trial_end' => 'Окончание триала',
        'subscription_end' => 'Окончание подписки',
        'timezone' => 'Часовой пояс',
        'locale' => 'Язык',
        'currency' => 'Валюта',
        'is_active' => 'Активна',
        'suspended_at' => 'С момента приостановки',
        'suspension_reason' => 'Причина приостановки',
        'users' => 'Пользователи',
        'properties' => 'Объекты',
        'subscription_status' => 'Статус подписки',
        'expired_subscriptions' => 'Истекшие подписки',
        'expiring_soon' => 'Скоро истекают (14 дней)',
        'analytics' => 'Аналитика',
        'new_plan' => 'Новый план',
        'reason' => 'Причина',
        'created_at' => 'Создано',
        'max_users' => 'Макс. пользователей',
        'timezone' => 'Часовой пояс',
        'locale' => 'Язык',
        'currency' => 'Валюта',
        'total_users' => 'Всего пользователей',
        'total_properties' => 'Всего объектов',
        'total_buildings' => 'Всего зданий',
        'total_invoices' => 'Всего счетов',
        'remaining_properties' => 'Оставшиеся объекты',
        'remaining_users' => 'Оставшиеся пользователи',
        'not_on_trial' => 'Без пробного периода',
        'not_suspended' => 'Не приостановлена',
    ],

    'helper_text' => [
        'slug' => 'Генерируется автоматически из названия, можно изменить',
        'domain' => 'Пользовательский домен для этой организации (необязательно)',
        'trial' => 'Оставьте пустым, если нет пробного периода',
        'subscription_end' => 'Дата окончания подписки',
        'inactive' => 'Неактивные организации не имеют доступа к системе',
        'suspended_at' => 'Устанавливается автоматически при приостановке',
        'suspension_reason' => 'Причина приостановки',
        'impersonation_reason' => 'Будет сохранено в журнале аудита',
        'change_plan' => 'Лимиты ресурсов будут обновлены автоматически',
    ],

    'filters' => [
        'active_placeholder' => 'Все организации',
        'active_only' => 'Только активные',
        'inactive_only' => 'Только неактивные',
    ],

    'actions' => [
        'suspend_selected' => 'Приостановить выбранные',
        'reactivate_selected' => 'Активировать выбранные',
        'export_selected' => 'Экспортировать выбранные',
        'change_plan' => 'Сменить план',
        'analytics' => 'Аналитика',
        'impersonate' => 'Войти как админ организации',
        'suspend' => 'Приостановить',
        'reactivate' => 'Активировать',
    ],

    'modals' => [
        'impersonate_heading' => 'Войти как администратор организации',
        'impersonate_description' => 'Вы войдете как админ этой организации. Все действия будут зафиксированы.',
        'impersonation_reason' => 'Причина входа от имени',
        'no_admin' => 'Администратор не найден',
        'impersonation_started' => 'Вход от имени начат',
    ],

    'relations' => [
        'properties' => [
            'building' => 'Здание',
            'area' => 'Площадь (м²)',
            'tenants' => 'Арендаторы',
            'meters' => 'Счетчики',
            'empty_heading' => 'Пока нет объектов',
            'empty_description' => 'Объекты появятся здесь после создания',
        ],
        'users' => [
            'active' => 'Активен',
            'empty_heading' => 'Пользователей нет',
            'empty_description' => 'Создайте пользователя для этой организации',
        ],
        'subscriptions' => [
            'plan' => 'План',
            'start' => 'Дата начала',
            'expiry' => 'Дата окончания',
            'properties_limit' => 'Лимит объектов',
            'tenants_limit' => 'Лимит арендаторов',
            'empty_heading' => 'Истории подписок нет',
            'empty_description' => 'Записи подписок появятся здесь',
        ],
        'activity_logs' => [
            'time' => 'Время',
            'user' => 'Пользователь',
            'resource' => 'Ресурс',
            'id' => 'ID',
            'ip' => 'IP',
            'details' => 'Детали',
            'modal_heading' => 'Детали активности',
            'empty_heading' => 'Журналов активности нет',
            'empty_description' => 'Здесь появятся журналы активности',
        ],
    ],

    'notifications' => [
        'bulk_suspended' => 'Приостановлено :count организаций',
        'bulk_reactivated' => 'Активировано :count организаций',
        'bulk_updated' => 'Обновлено :count организаций',
        'bulk_failed_suffix' => ', :count не удалось',
    ],
    'validation' => [
        'name' => [
            'required' => 'Имя обязательно.',
            'string' => 'Имя должно быть текстом.',
            'max' => 'Имя не может превышать 255 символов.',
        ],
        'email' => [
            'required' => 'Email обязателен.',
            'string' => 'Email должен быть текстом.',
            'email' => 'Email должен быть корректным адресом.',
            'max' => 'Email не может превышать 255 символов.',
            'unique' => 'Этот email уже используется.',
        ],
        'password' => [
            'required' => 'Пароль обязателен.',
            'string' => 'Пароль должен быть текстом.',
            'min' => 'Пароль должен содержать не менее 8 символов.',
        ],
        'organization_name' => [
            'required' => 'Название организации обязательно.',
            'string' => 'Название организации должно быть текстом.',
            'max' => 'Название организации не может превышать 255 символов.',
        ],
        'plan_type' => [
            'required' => 'Тип плана обязателен.',
            'in' => 'Тип плана должен быть basic, professional или enterprise.',
        ],
        'expires_at' => [
            'required' => 'Дата окончания обязательна.',
            'date' => 'Дата окончания должна быть корректной.',
            'after' => 'Дата окончания должна быть позже сегодняшнего дня.',
        ],
        'is_active' => [
            'boolean' => 'Поле активности должно быть истинным или ложным.',
        ],
    ],
];
