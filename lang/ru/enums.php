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

    'distribution_method' => [
        'equal' => 'Равномерное распределение',
        'area' => 'Распределение по площади',
        'by_consumption' => 'Распределение по потреблению',
        'custom_formula' => 'Распределение по формуле',
        'equal_description' => 'Расходы распределяются равномерно между всеми объектами',
        'area_description' => 'Расходы распределяются пропорционально площади объекта',
        'by_consumption_description' => 'Расходы распределяются по соотношению фактического потребления',
        'custom_formula_description' => 'Используется индивидуальная математическая формула для распределения',
    ],

    'pricing_model' => [
        'fixed_monthly' => 'Фиксированная ежемесячная плата',
        'consumption_based' => 'Ценообразование по потреблению',
        'tiered_rates' => 'Ступенчатая структура тарифов',
        'hybrid' => 'Гибридная модель ценообразования',
        'custom_formula' => 'Индивидуальное ценообразование по формуле',
        'flat' => 'Фиксированный тариф',
        'time_of_use' => 'Ценообразование по времени суток',
        'fixed_monthly_description' => 'Фиксированная ежемесячная плата независимо от потребления',
        'consumption_based_description' => 'Ценообразование на основе фактического объема потребления',
        'tiered_rates_description' => 'Разные тарифы для разных уровней потребления',
        'hybrid_description' => 'Комбинация фиксированной платы и ценообразования по потреблению',
        'custom_formula_description' => 'Индивидуальная математическая формула для расчета цены',
        'flat_description' => 'Единый тариф для всего потребления',
        'time_of_use_description' => 'Разные тарифы в разное время суток',
    ],

    'input_method' => [
        'manual' => 'Ручной ввод',
        'photo_ocr' => 'Фото с OCR',
        'csv_import' => 'Импорт CSV',
        'api_integration' => 'Интеграция API',
        'estimated' => 'Расчетное показание',
        'manual_description' => 'Введено вручную пользователем',
        'photo_ocr_description' => 'Извлечено из фотографии счетчика с помощью OCR',
        'csv_import_description' => 'Импортировано из CSV файла',
        'api_integration_description' => 'Получено через интеграцию API',
        'estimated_description' => 'Рассчитано на основе исторических данных',
    ],

    'validation_status' => [
        'pending' => 'Ожидает проверки',
        'validated' => 'Проверено',
        'rejected' => 'Отклонено',
        'requires_review' => 'Требует проверки',
        'pending_description' => 'Ожидает проверки',
        'validated_description' => 'Одобрено и готово к расчету',
        'rejected_description' => 'Отклонено из-за ошибок или несоответствий',
        'requires_review_description' => 'Требуется ручная проверка перед одобрением',
    ],

    'area_type' => [
        'total_area' => 'Общая площадь',
        'heated_area' => 'Отапливаемая площадь',
        'commercial_area' => 'Коммерческая площадь',
    ],

    'gyvatukas_calculation_type' => [
        'summer' => 'Летний расчет',
        'winter' => 'Зимний расчет',
    ],

    'system_tenant_status' => [
        'active' => 'Активный',
        'suspended' => 'Приостановлен',
        'pending' => 'Ожидает',
        'cancelled' => 'Отменен',
    ],

    'system_subscription_plan' => [
        'starter' => 'Начальный',
        'professional' => 'Профессиональный',
        'enterprise' => 'Корпоративный',
        'custom' => 'Индивидуальный',
    ],

    'super_admin_audit_action' => [
        'system_tenant_created' => 'Системный арендатор создан',
        'system_tenant_updated' => 'Системный арендатор обновлен',
        'system_tenant_suspended' => 'Системный арендатор приостановлен',
        'system_tenant_activated' => 'Системный арендатор активирован',
        'system_tenant_deleted' => 'Системный арендатор удален',
        'user_impersonated' => 'Пользователь олицетворен',
        'impersonation_ended' => 'Олицетворение завершено',
        'bulk_operation' => 'Массовая операция',
        'system_config_changed' => 'Конфигурация системы изменена',
        'backup_created' => 'Резервная копия создана',
        'backup_restored' => 'Резервная копия восстановлена',
        'notification_sent' => 'Уведомление отправлено',
        'feature_flag_changed' => 'Флаг функции изменен',
        'security_policy_changed' => 'Политика безопасности изменена',
    ],
];
