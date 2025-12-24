<?php

declare(strict_types=1);

return [
    'area_type' => [
        'commercial_area' => 'Коммерческая площадь',
        'heated_area' => 'Отапливаемая площадь',
        'total_area' => 'Общая площадь',
    ],
    'distribution_method' => [
        'area' => 'По площади',
        'area_description' => 'Распределение затрат пропорционально площади недвижимости (квадратные метры)',
        'by_consumption' => 'По потреблению',
        'by_consumption_description' => 'Распределение затрат на основе фактических коэффициентов потребления из исторических данных',
        'custom_formula' => 'Пользовательская формула',
        'custom_formula_description' => 'Использование пользовательских математических формул для гибких сценариев распределения',
        'equal' => 'Равномерное распределение',
        'equal_description' => 'Равномерное распределение затрат между всеми объектами недвижимости независимо от размера или потребления',
    ],
    'input_method' => [
        'api_integration_description' => 'Описание интеграции API',
        'csv_import_description' => 'Описание импорта CSV',
        'estimated_description' => 'Описание оценочного метода',
        'manual_description' => 'Описание ручного ввода',
        'photo_ocr_description' => 'Описание фото OCR',
    ],
    'pricing_model' => [
        'consumption_based_description' => 'Тарификация на основе фактического потребления коммунальных услуг с тарифами за единицу',
        'custom_formula_description' => 'Использует пользовательские математические формулы для сложных сценариев ценообразования',
        'fixed_monthly_description' => 'Фиксированная ежемесячная плата независимо от потребления',
        'flat_description' => 'Простое фиксированное ценообразование (совместимость с устаревшими версиями)',
        'hybrid_description' => 'Сочетает фиксированные ежемесячные платежи с тарифами на основе потребления',
        'tiered_rates_description' => 'Прогрессивные тарифы, которые увеличиваются с более высокими уровнями потребления',
        'time_of_use_description' => 'Различные тарифы в зависимости от времени суток, дня недели или сезона',
    ],
    'service_type' => [
        'electricity' => 'Электричество',
        'heating' => 'Отопление',
        'water' => 'Вода',
        'gas' => 'Газ',
    ],
    'tariff_type' => [
        'flat' => 'Фиксированный тариф',
        'time_of_use' => 'По времени использования',
    ],
    'tariff_zone' => [
        'day' => 'Дневной тариф',
        'night' => 'Ночной тариф',
        'weekend' => 'Выходной тариф',
    ],
    'super_admin_audit_action' => [
        'backup_created' => 'Резервная копия создана',
        'backup_restored' => 'Резервная копия восстановлена',
        'bulk_operation' => 'Массовая операция',
        'feature_flag_changed' => 'Флаг функции изменен',
        'impersonation_ended' => 'Имитация завершена',
        'notification_sent' => 'Уведомление отправлено',
        'security_policy_changed' => 'Политика безопасности изменена',
        'system_config_changed' => 'Конфигурация системы изменена',
        'system_tenant_activated' => 'Системный арендатор активирован',
        'system_tenant_created' => 'Системный арендатор создан',
        'system_tenant_deleted' => 'Системный арендатор удален',
        'system_tenant_suspended' => 'Системный арендатор приостановлен',
        'system_tenant_updated' => 'Системный арендатор обновлен',
        'user_impersonated' => 'Пользователь имитирован',
    ],
    'system_subscription_plan' => [
        'custom' => 'Пользовательский',
        'enterprise' => 'Корпоративный',
        'professional' => 'Профессиональный',
        'starter' => 'Стартовый',
    ],
    'system_tenant_status' => [
        'active' => 'Активный',
        'cancelled' => 'Отменен',
        'pending' => 'В ожидании',
        'suspended' => 'Приостановлен',
    ],
    'user_role' => [
        'superadmin' => 'Суперадминистратор',
        'admin' => 'Администратор',
        'manager' => 'Менеджер',
        'tenant' => 'Арендатор',
    ],
    'validation_status' => [
        'pending_description' => 'Описание ожидания',
        'rejected_description' => 'Описание отклонения',
        'requires_review_description' => 'Описание требует проверки',
        'validated_description' => 'Описание проверено',
    ],
];