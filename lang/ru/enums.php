<?php

declare(strict_types=1);

return [
    'area_type' => [
        'commercial_area' => 'Коммерческая площадь',
        'heated_area' => 'Отапливаемая зона',
        'total_area' => 'Общая площадь',
    ],
    'distribution_method' => [
        'area' => 'Область',
        'area_description' => 'Описание района',
        'by_consumption' => 'По потреблению',
        'by_consumption_description' => 'По описанию потребления',
        'custom_formula' => 'Пользовательская формула',
        'custom_formula_description' => 'Описание пользовательской формулы',
        'equal' => 'Равный',
        'equal_description' => 'Равное описание',
    ],
    'gyvatukas_calculation_type' => [
        'summer' => 'Лето',
        'winter' => 'Зима',
    ],
    'input_method' => [
        'api_integration_description' => 'Описание интеграции API',
        'csv_import_description' => 'Описание импорта CsV',
        'estimated_description' => 'Примерное описание',
        'manual_description' => 'Описание руководства',
        'photo_ocr_description' => 'Фото Окр Описание',
    ],
    'pricing_model' => [
        'consumption_based_description' => 'Описание на основе потребления',
        'custom_formula_description' => 'Описание пользовательской формулы',
        'fixed_monthly_description' => 'Фиксированное ежемесячное описание',
        'flat_description' => 'Описание квартиры',
        'hybrid_description' => 'Описание гибрида',
        'tiered_rates_description' => 'Описание многоуровневых тарифов',
        'time_of_use_description' => 'Время использования Описание',
    ],
    'service_type' => [
        'electricity' => 'Электричество',
        'heating' => 'Обогрев',
        'water' => 'Вода',
    ],
    'super_admin_audit_action' => [
        'backup_created' => 'Резервная копия создана',
        'backup_restored' => 'Резервная копия восстановлена',
        'bulk_operation' => 'Массовая операция',
        'feature_flag_changed' => 'Флаг функции изменен',
        'impersonation_ended' => 'Олицетворение завершено',
        'notification_sent' => 'Уведомление отправлено',
        'security_policy_changed' => 'Политика безопасности изменена',
        'system_config_changed' => 'Конфигурация системы изменена',
        'system_tenant_activated' => 'Системный клиент активирован',
        'system_tenant_created' => 'Системный клиент создан',
        'system_tenant_deleted' => 'Системный клиент удален',
        'system_tenant_suspended' => 'Клиент системы приостановлен',
        'system_tenant_updated' => 'Системный клиент обновлен',
        'user_impersonated' => 'Пользователь олицетворяет себя',
    ],
    'system_subscription_plan' => [
        'custom' => 'Обычай',
        'enterprise' => 'Предприятие',
        'professional' => 'Профессиональный',
        'starter' => 'Стартер',
    ],
    'system_tenant_status' => [
        'active' => 'Активный',
        'cancelled' => 'Отменено',
        'pending' => 'В ожидании',
        'suspended' => 'Приостановленный',
    ],
    'user_role' => [
        'admin' => 'Админ',
        'manager' => 'Менеджер',
        'tenant' => 'Жилец',
    ],
    'validation_status' => [
        'pending_description' => 'Ожидается описание',
        'rejected_description' => 'Отклонено Описание',
        'requires_review_description' => 'Требуется описание обзора',
        'validated_description' => 'Проверенное описание',
    ],
];
