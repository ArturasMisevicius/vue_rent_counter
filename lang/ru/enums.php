<?php

return [
    'meter_type' => [
        'electricity' => 'Электричество',
        'water_cold' => 'Холодная вода',
        'water_hot' => 'Горячая вода',
        'heating' => 'Отопление',
    ],

    'property_type' => [
        'apartment' => 'Квартира',
        'house' => 'Дом',
    ],

    'service_type' => [
        'electricity' => 'Электричество',
        'water' => 'Вода',
        'heating' => 'Отопление',
    ],

    'invoice_status' => [
        'draft' => 'Черновик',
        'finalized' => 'Завершен',
        'paid' => 'Оплачен',
    ],

    'user_role' => [
        'superadmin' => 'Суперадмин',
        'admin' => 'Администратор',
        'manager' => 'Менеджер',
        'tenant' => 'Арендатор',
    ],

    'tariff_type' => [
        'flat' => 'Фиксированный тариф',
        'time_of_use' => 'Тариф по времени суток',
    ],

    'tariff_zone' => [
        'day' => 'Дневной тариф',
        'night' => 'Ночной тариф',
        'weekend' => 'Тариф выходного дня',
    ],

    'weekend_logic' => [
        'apply_night_rate' => 'Применять ночной тариф в выходные',
        'apply_day_rate' => 'Применять дневной тариф в выходные',
        'apply_weekend_rate' => 'Применять тариф выходного дня',
    ],

    'subscription_plan_type' => [
        'basic' => 'Базовый',
        'professional' => 'Профессиональный',
        'enterprise' => 'Корпоративный',
    ],

    'subscription_status' => [
        'active' => 'Активна',
        'expired' => 'Истекла',
        'suspended' => 'Приостановлена',
        'cancelled' => 'Отменена',
    ],

    'user_assignment_action' => [
        'created' => 'Создано',
        'assigned' => 'Назначено',
        'reassigned' => 'Переназначено',
        'deactivated' => 'Деактивировано',
        'reactivated' => 'Повторно активировано',
    ],
];
