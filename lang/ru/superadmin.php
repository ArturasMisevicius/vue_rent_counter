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
            'total_properties' => 'Всего объектов',
            'total_buildings' => 'Всего зданий',
            'total_tenants' => 'Всего арендаторов',
            'total_invoices' => 'Всего счетов',
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
        
        'recent_activity' => [
            'title' => 'Недавняя активность администраторов',
            'last_activity' => 'Последняя активность:',
            'no_activity' => 'Активности пока нет',
        ],
        
        'quick_actions' => [
            'title' => 'Быстрые действия',
            'create_organization' => 'Создать новую организацию',
            'manage_organizations' => 'Управление организациями',
            'manage_subscriptions' => 'Управление подписками',
        ],
    ],
];
