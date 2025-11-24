<?php

declare(strict_types=1);

return [
    'dashboard' => [
        'title' => 'Панель суперадминистратора',
        'subtitle' => 'Общесистемная статистика и управление организациями',
        
        'stats' => [
            'total_subscriptions' => 'Всего подписок',
            'active_subscriptions' => 'Активные подписки',
            'expired_subscriptions' => 'Истекшие подписки',
            'suspended_subscriptions' => 'Приостановленные подписки',
            'cancelled_subscriptions' => 'Отмененные подписки',
            'total_properties' => 'Всего объектов',
            'total_buildings' => 'Всего зданий',
            'total_tenants' => 'Всего арендаторов',
            'total_invoices' => 'Всего счетов',
        ],
        'stats_descriptions' => [
            'total_subscriptions' => 'Все подписки в системе',
            'active_subscriptions' => 'Текущие активные',
            'expired_subscriptions' => 'Требуют продления',
            'suspended_subscriptions' => 'Временно приостановлены',
            'cancelled_subscriptions' => 'Полностью отменены',
            'total_organizations' => 'Все организации в системе',
            'active_organizations' => 'Текущие активные',
            'inactive_organizations' => 'Приостановлены или неактивны',
        ],
        
        'organizations' => [
            'title' => 'Организации',
            'total' => 'Всего организаций',
            'active' => 'Активные организации',
            'inactive' => 'Неактивные организации',
            'view_all' => 'Просмотреть все организации →',
            'top_by_properties' => 'Топ организаций по объектам',
            'properties_count' => 'объектов',
            'no_organizations' => 'Организаций пока нет',
        ],
        
        'subscription_plans' => [
            'title' => 'Планы подписки',
            'basic' => 'Базовый',
            'professional' => 'Профессиональный',
            'enterprise' => 'Корпоративный',
            'view_all' => 'Просмотреть все подписки →',
        ],
        
        'expiring_subscriptions' => [
            'title' => 'Истекающие подписки',
            'alert' => ':count подписка(-и) истекает в течение 14 дней',
            'expires' => 'Истекает:',
        ],

        'organizations_widget' => [
            'total' => 'Всего организаций',
            'active' => 'Активные организации',
            'inactive' => 'Неактивные организации',
            'new_this_month' => 'Новые в этом месяце',
            'growth_up' => '↑ :value% по сравнению с прошлым месяцем',
            'growth_down' => '↓ :value% по сравнению с прошлым месяцем',
        ],
        
        'recent_activity' => [
            'title' => 'Недавняя активность администраторов',
            'last_activity' => 'Последняя активность:',
            'no_activity' => 'Активности пока нет',
            'created_header' => 'Создано',
        ],
        'recent_activity_widget' => [
            'heading' => 'Недавняя активность',
            'description' => 'Последние 10 действий во всех организациях',
            'empty_heading' => 'Активности нет',
            'empty_description' => 'Здесь появятся журналы активности',
            'default_system' => 'Система',
            'columns' => [
                'time' => 'Время',
                'user' => 'Пользователь',
                'organization' => 'Организация',
                'action' => 'Действие',
                'resource' => 'Ресурс',
                'id' => 'ID',
                'details' => 'Детали',
            ],
            'modal_heading' => 'Детали активности',
        ],
        
        'quick_actions' => [
            'title' => 'Быстрые действия',
            'create_organization' => 'Создать новую организацию',
            'manage_organizations' => 'Управление организациями',
            'manage_subscriptions' => 'Управление подписками',
        ],

        'overview' => [
            'subscriptions' => [
                'title' => 'Обзор подписок',
                'description' => 'Недавние подписки, формирующие показатели виджетов',
                'open' => 'Открыть подписки',
                'headers' => [
                    'organization' => 'Организация',
                    'plan' => 'План',
                    'status' => 'Статус',
                    'expires' => 'Окончание',
                    'manage' => 'Управлять',
                ],
                'empty' => 'Подписок пока нет',
            ],
            'organizations' => [
                'title' => 'Обзор организаций',
                'description' => 'Последние организации, влияющие на показатели',
                'open' => 'Открыть организации',
                'headers' => [
                    'organization' => 'Организация',
                    'subscription' => 'Подписка',
                    'status' => 'Статус',
                    'created' => 'Создана',
                    'manage' => 'Управлять',
                ],
                'no_subscription' => 'Нет подписки',
                'status_active' => 'Активна',
                'status_inactive' => 'Неактивна',
                'empty' => 'Организаций пока нет',
            ],
            'resources' => [
                'title' => 'Системные ресурсы',
                'description' => 'Последние записи, формирующие ресурсные виджеты',
                'manage_orgs' => 'Управлять организациями',
                'properties' => [
                    'title' => 'Объекты',
                    'open_owners' => 'Открыть владельцев',
                    'building' => 'Здание',
                    'organization' => 'Организация',
                    'unknown_org' => 'Неизвестно',
                    'empty' => 'Объекты не найдены',
                ],
                'buildings' => [
                    'title' => 'Здания',
                    'open_owners' => 'Открыть владельцев',
                    'address' => 'Адрес',
                    'organization' => 'Организация',
                    'manage' => 'Управлять',
                    'empty' => 'Здания не найдены',
                ],
                'tenants' => [
                    'title' => 'Арендаторы',
                    'open_owners' => 'Открыть владельцев',
                    'property' => 'Объект',
                    'not_assigned' => 'Не назначен',
                    'organization' => 'Организация',
                    'status_active' => 'Активен',
                    'status_inactive' => 'Неактивен',
                    'empty' => 'Арендаторы не найдены',
                ],
                'invoices' => [
                    'title' => 'Счета',
                    'open_owners' => 'Открыть владельцев',
                    'amount' => 'Сумма',
                    'status' => 'Статус',
                    'organization' => 'Организация',
                    'manage' => 'Управлять',
                    'empty' => 'Счетов не найдено',
                ],
            ],
        ],

        'organizations_list' => [
            'expires' => 'Окончание:',
            'no_subscription' => 'Нет подписки',
            'status_active' => 'Активна',
            'status_inactive' => 'Неактивна',
            'actions' => [
                'view' => 'Просмотр',
                'edit' => 'Редактировать',
            ],
            'empty' => 'Организации не найдены',
        ],

        'organization_show' => [
            'status' => 'Статус',
            'created' => 'Создана',
            'start_date' => 'Дата начала',
            'expiry_date' => 'Дата окончания',
            'limits' => 'Лимиты',
            'limit_values' => ':properties объектов, :tenants арендаторов',
            'manage_subscription' => 'Управлять подпиской →',
            'no_subscription' => 'Подписка не найдена',
            'stats' => [
                'properties' => 'Объекты',
                'buildings' => 'Здания',
                'tenants' => 'Арендаторы',
                'active_tenants' => 'Активные арендаторы',
                'invoices' => 'Счета',
                'meters' => 'Счетчики',
            ],
        ],
    ],
];
