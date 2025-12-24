<?php

declare(strict_types=1);

return [
    'admin' => [
        'activity' => [
            'no_users' => 'Нет пользователей',
            'recent_invoices' => 'Последние счета',
            'recent_portfolio' => 'Последнее портфолио',
            'recent_tenants' => 'Последние арендаторы',
            'recent_users' => 'Последние пользователи',
        ],
        'banner' => [
            'expired_body' => 'Ваша подписка истекла. Обновите подписку для продолжения использования всех функций.',
            'expired_title' => 'Подписка истекла',
            'expiring_body' => 'Ваша подписка истекает в ближайшее время. Обновите её, чтобы избежать прерывания обслуживания.',
            'expiring_title' => 'Подписка истекает',
            'no_subscription_body' => 'У вас нет активной подписки. Выберите план для начала работы.',
            'no_subscription_title' => 'Нет подписки',
            'renew' => 'Обновить',
            'renew_now' => 'Обновить сейчас',
        ],
        'breakdown' => [
            'administrators' => 'Администраторы',
            'draft_invoices' => 'Черновики счетов',
            'finalized_invoices' => 'Финализированные счета',
            'invoice_title' => 'Счета',
            'managers' => 'Менеджеры',
            'paid_invoices' => 'Оплаченные счета',
            'tenants' => 'Арендаторы',
            'users_title' => 'Пользователи',
        ],
        'org_dashboard' => 'Панель организации',
        'portfolio_subtitle' => 'Управление портфолио недвижимости',
        'quick' => [
            'create_user' => 'Создать пользователя',
            'create_user_desc' => 'Добавить нового пользователя в систему',
            'settings' => 'Настройки',
            'settings_desc' => 'Управление настройками системы',
        ],
        'quick_actions' => [
            'create_tenant_desc' => 'Добавить нового арендатора в систему',
            'create_tenant_title' => 'Создать арендатора',
            'manage_tenants_desc' => 'Просмотр и управление всеми арендаторами',
            'manage_tenants_title' => 'Управление арендаторами',
            'manage_users_desc' => 'Управление пользователями и их правами',
            'manage_users_title' => 'Управление пользователями',
            'organization_profile_desc' => 'Редактировать профиль организации',
            'organization_profile_title' => 'Профиль организации',
            'title' => 'Быстрые действия',
        ],
        'stats' => [
            'active_meters' => 'Активные счетчики',
            'active_tenants' => 'Активные арендаторы',
            'total_meter_readings' => 'Всего показаний счетчиков',
            'total_properties' => 'Всего объектов',
            'total_users' => 'Всего пользователей',
            'unpaid_invoices' => 'Неоплаченные счета',
        ],
        'subscription_card' => [
            'approaching_limit' => 'Приближается к лимиту',
            'expires' => 'Истекает',
            'plan_type' => 'Тип плана',
            'properties' => 'Объекты',
            'tenants' => 'Арендаторы',
            'title' => 'Подписка',
        ],
        'system_subtitle' => 'Системная аналитика и мониторинг',
        'title' => 'Панель администратора',
    ],
    'manager' => [
        'description' => 'Управление объектами и арендаторами',
        'empty' => [
            'drafts' => 'Нет черновиков счетов',
            'operations' => 'Нет текущих операций',
            'recent' => 'Нет недавней активности',
        ],
        'hints' => [
            'drafts' => 'Здесь отображаются неоплаченные счета',
            'operations' => 'Текущие операции и задачи',
            'recent' => 'Последние действия в системе',
            'shortcuts' => 'Быстрый доступ к основным функциям',
        ],
        'pending_section' => 'Ожидающие действия',
        'quick_actions' => [
            'enter_reading_desc' => 'Внести новые показания счетчиков',
            'generate_invoice_desc' => 'Создать новый счет для арендатора',
            'view_buildings' => 'Просмотр зданий',
            'view_buildings_desc' => 'Управление зданиями и их характеристиками',
            'view_meters' => 'Просмотр счетчиков',
            'view_meters_desc' => 'Мониторинг всех счетчиков в системе',
            'view_reports' => 'Просмотр отчетов',
            'view_reports_desc' => 'Аналитика и отчеты по потреблению',
        ],
        'sections' => [
            'drafts' => 'Черновики',
            'operations' => 'Операции',
            'recent' => 'Недавние',
            'shortcuts' => 'Быстрые действия',
        ],
        'stats' => [
            'active_meters' => 'Активные счетчики',
            'active_tenants' => 'Активные арендаторы',
            'draft_invoices' => 'Черновики счетов',
            'meters_pending' => 'Счетчики ожидают показаний',
            'overdue_invoices' => 'Просроченные счета',
            'total_properties' => 'Всего объектов',
        ],
        'title' => 'Панель менеджера',
    ],
    'tenant' => [
        'alerts' => [
            'no_property_body' => 'К вашему аккаунту не привязан объект недвижимости. Обратитесь к администратору.',
            'no_property_title' => 'Объект не назначен',
        ],
        'balance' => [
            'cta' => 'Оплатить',
            'outstanding' => 'К доплате',
            'title' => 'Баланс',
        ],
        'consumption' => [
            'current' => 'Текущий',
            'description' => 'Анализ потребления коммунальных услуг',
            'missing_previous' => 'Нет предыдущих данных',
            'need_more' => 'Нужно больше данных для анализа',
            'previous' => 'Предыдущий',
            'since_last' => 'С последнего показания',
            'title' => 'Потребление',
        ],
        'readings' => [
            'units' => [
                '' => '',
            ],
        ],
        'description' => 'Личный кабинет арендатора',
        'property' => [
            'address' => 'Адрес',
            'area' => 'Площадь',
            'building' => 'Здание',
            'title' => 'Объект недвижимости',
            'type' => 'Тип',
        ],
        'quick_actions' => [
            'description' => 'Быстрый доступ к основным функциям',
            'invoices_desc' => 'Просмотр истории счетов и платежей',
            'invoices_title' => 'Мои счета',
            'meters_desc' => 'Просмотр показаний счетчиков',
            'meters_title' => 'Счетчики',
            'property_desc' => 'Информация о вашем объекте',
            'property_title' => 'Мой объект',
            'title' => 'Быстрые действия',
        ],
        'readings' => [
            'date' => 'Дата',
            'meter_type' => 'Тип счетчика',
            'reading' => 'Показание',
            'serial' => 'Серийный номер',
            'serial_short' => 'Сер. №',
            'title' => 'Показания счетчиков',
            'units' => 'Единицы',
        ],
        'stats' => [
            'active_meters' => 'Активные счетчики',
            'total_invoices' => 'Всего счетов',
            'unpaid_invoices' => 'Неоплаченные счета',
        ],
        'title' => 'Панель арендатора',
    ],
    // Universal Utility Dashboard Translations
    'utility_analytics' => 'Аналитика коммунальных услуг',
    'efficiency_trends' => 'Тренды эффективности',
    'cost_predictions' => 'Прогноз расходов',
    'usage_patterns' => 'Паттерны потребления',
    'recommendations' => 'Рекомендации',
    'real_time_costs' => 'Расходы в реальном времени',
    'service_breakdown' => 'Разбивка по услугам',
    'utility_services_overview' => 'Обзор коммунальных услуг',
    'recent_activity' => 'Недавняя активность',
    
    // Stats and Metrics
    'stats' => [
        'total_properties' => 'Всего объектов',
        'active_meters' => 'Активные счетчики',
        'monthly_cost' => 'Месячные расходы',
        'pending_readings' => 'Ожидающие показания',
    ],
    
    // Filters
    'filters' => [
        'last_3_months' => 'Последние 3 месяца',
        'last_6_months' => 'Последние 6 месяцев',
        'last_12_months' => 'Последние 12 месяцев',
        'current_year' => 'Текущий год',
        'current_month' => 'Текущий месяц',
        'last_month' => 'Прошлый месяц',
    ],
    
    // Cost Tracking
    'current_month_cost' => 'Расходы текущего месяца',
    'year_to_date_cost' => 'Расходы с начала года',
    'average_monthly_cost' => 'Средние месячные расходы',
    'from_last_month' => 'с прошлого месяца',
    'total_this_year' => 'всего в этом году',
    'last_6_months_average' => 'среднее за последние 6 месяцев',
    
    // Real-Time Cost Widget
    'today_projection' => 'Прогноз на сегодня',
    'current' => 'Текущий',
    'projected' => 'Прогнозируемый',
    'complete' => 'завершено',
    'monthly_estimate' => 'Месячная оценка',
    'month' => 'месяц',
    'no_recent_readings' => 'Нет недавних показаний',
    'last_updated' => 'Последнее обновление',
    'never' => 'Никогда',
    
    // Chart Labels
    'consumption_units' => 'Потребление (единицы)',
    'months' => 'Месяцы',
    'meters' => 'счетчики',
    
    // Trends and Analysis
    'trend_increasing' => 'Растет',
    'trend_decreasing' => 'Снижается',
    'trend_stable' => 'Стабильно',
    'confidence_high' => 'Высокая достоверность',
    'confidence_medium' => 'Средняя достоверность',
    'confidence_low' => 'Низкая достоверность',
    'monthly_prediction' => 'Месячный прогноз',
    'yearly_prediction' => 'Годовой прогноз',
    'peak_usage' => 'Пиковое потребление',
    'weekly_pattern' => 'Недельный паттерн',
    'monthly_trend' => 'Месячный тренд',
    
    // Empty States
    'no_efficiency_data' => 'Нет данных об эффективности',
    'no_prediction_data' => 'Нет данных для прогноза',
    'no_pattern_data' => 'Нет данных о паттернах потребления',
    'no_recommendations' => 'Нет рекомендаций в данный момент',
    
    // Recommendations
    'missing_readings_title' => 'Отсутствуют показания для :service',
    'missing_readings_desc' => 'Не найдены недавние показания для :property',
    'add_reading' => 'Добавить показание',
    'high_usage_title' => 'Обнаружено высокое потребление :service',
    'high_usage_desc' => 'Потребление увеличилось на :percentage% для :property',
    'investigate_usage' => 'Исследовать потребление',
    'low_usage_title' => 'Обнаружено низкое потребление :service',
    'low_usage_desc' => 'Потребление снизилось на :percentage% для :property',
    'verify_readings' => 'Проверить показания',
    'efficiency_title' => 'Возможность повышения энергоэффективности',
    'efficiency_desc' => 'Рассмотрите энергосберегающие меры для :property',
    'consider_efficiency' => 'Рассмотреть меры эффективности',

    'widgets' => [
        'admin' => [
            'active_tenants' => [
                'description' => 'Количество активных арендаторов в системе',
                'label' => 'Активные арендаторы',
            ],
            'draft_invoices' => [
                'description' => 'Счета в статусе черновика',
                'label' => 'Черновики счетов',
            ],
            'pending_readings' => [
                'description' => 'Счетчики, ожидающие новых показаний',
                'label' => 'Ожидающие показания',
            ],
            'total_buildings' => [
                'description' => 'Общее количество зданий в управлении',
                'label' => 'Всего зданий',
            ],
            'total_properties' => [
                'description' => 'Общее количество объектов недвижимости',
                'label' => 'Всего объектов',
            ],
            'total_revenue' => [
                'description' => 'Общий доход от коммунальных услуг',
                'label' => 'Общий доход',
            ],
        ],
        'manager' => [
            'draft_invoices' => [
                'description' => 'Неоплаченные счета, требующие внимания',
                'label' => 'Черновики счетов',
            ],
            'pending_readings' => [
                'description' => 'Счетчики, требующие снятия показаний',
                'label' => 'Ожидающие показания',
            ],
            'total_buildings' => [
                'description' => 'Здания под вашим управлением',
                'label' => 'Мои здания',
            ],
            'total_properties' => [
                'description' => 'Объекты под вашим управлением',
                'label' => 'Мои объекты',
            ],
        ],
        'tenant' => [
            'invoices' => [
                'description' => 'Ваши счета за коммунальные услуги',
                'label' => 'Мои счета',
            ],
            'property' => [
                'description' => 'Информация о вашем объекте недвижимости',
                'label' => 'Мой объект',
            ],
            'unpaid' => [
                'description' => 'Неоплаченные счета, требующие оплаты',
                'label' => 'К оплате',
            ],
        ],
    ],

    // Audit System Translations
    'audit' => [
        // Widget Headings
        'overview' => 'Обзор аудита',
        'trends' => 'Тренды аудита',
        'trends_title' => 'Тренды аудита',
        'trends_description' => 'Отслеживание изменений конфигурации и системной активности во времени',
        'compliance_status' => 'Статус соответствия',
        'anomaly_detection' => 'Обнаружение аномалий',
        'change_history' => 'История изменений',
        'rollback_management' => 'Управление откатами',
        'rollback_history' => 'История откатов',
        
        // Stats and Metrics
        'total_changes' => 'Всего изменений',
        'user_changes' => 'Изменения пользователей',
        'system_changes' => 'Системные изменения',
        'compliance_score' => 'Оценка соответствия',
        'anomalies_detected' => 'Обнаружено аномалий',
        'performance_score' => 'Оценка производительности',
        'performance_grade' => 'Класс производительности',
        'system_performance' => 'Производительность системы',
        'critical_issues' => 'Критические проблемы',
        'requires_attention' => 'Требует внимания',
        'last_24_hours' => 'Последние 24 часа',
        'last_7_days' => 'Последние 7 дней',
        'last_30_days' => 'Последние 30 дней',
        'date' => 'Дата',
        'number_of_changes' => 'Количество изменений',
        
        // Status Messages
        'view_details' => 'Просмотр деталей',
        'no_anomalies' => 'Аномалии не обнаружены',
        'no_data_available' => 'Данные аудита недоступны',
        'no_rollbacks' => 'Откаты не найдены',
        'no_rollbacks_description' => 'Откаты конфигурации еще не выполнялись.',
        'excellent_compliance' => 'Отличное соответствие',
        'good_compliance' => 'Хорошее соответствие',
        'needs_attention' => 'Требует внимания',
        'critical_issues' => 'Критические проблемы',
        'fully_compliant' => 'Полное соответствие',
        'non_compliant' => 'Несоответствие',
        'unknown_status' => 'Неизвестный статус',
        
        // Modal Titles
        'change_details' => 'Детали изменения',
        'rollback_details' => 'Детали отката',
        'rollback_confirmation' => 'Подтвердить откат',
        'rollback_warning' => 'Это действие вернет конфигурацию к предыдущему состоянию. Это нельзя отменить.',
        'bulk_rollback_confirmation' => 'Подтвердить массовый откат',
        'bulk_rollback_warning' => 'Это откатит несколько конфигураций. Убедитесь, что это намеренно.',
        'revert_rollback_confirmation' => 'Подтвердить отмену отката',
        'revert_rollback_warning' => 'Это отменит операцию отката. Используйте с осторожностью.',
        
        // Labels
        'labels' => [
            'severity' => 'Серьезность',
            'details' => 'Детали',
            'average' => 'Среднее',
            'peak' => 'Пик',
            'threshold' => 'Порог',
            'anomalous' => 'Аномальный',
            'yes' => 'Да',
            'no' => 'Нет',
            'recommended_actions' => 'Рекомендуемые действия',
            'changed_at' => 'Изменено в',
            'model_type' => 'Тип модели',
            'event' => 'Событие',
            'user' => 'Пользователь',
            'system' => 'Система',
            'unknown_user' => 'Неизвестный пользователь',
            'changed_fields' => 'Измененные поля',
            'notes' => 'Заметки',
            'period' => 'Период',
            'performed_at' => 'Выполнено в',
            'performed_by' => 'Выполнено',
            'configuration' => 'Конфигурация',
            'reason' => 'Причина',
            'fields_rolled_back' => 'Откаченные поля',
            'original_change' => 'Исходное изменение',
            'not_available' => 'Недоступно',
            'rollback_reason' => 'Причина отката',
            'revert_reason' => 'Причина отмены',
            'select_changes' => 'Выбрать изменения',
        ],
        
        // Events
        'events' => [
            'created' => 'Создано',
            'updated' => 'Обновлено',
            'deleted' => 'Удалено',
            'rollback' => 'Откат',
        ],
        
        // Models
        'models' => [
            'utility_service' => 'Коммунальная услуга',
            'service_configuration' => 'Конфигурация услуги',
        ],
        
        // Time Periods
        'periods' => [
            'today' => 'Сегодня',
            'this_week' => 'На этой неделе',
            'this_month' => 'В этом месяце',
            'this_quarter' => 'В этом квартале',
            'this_year' => 'В этом году',
        ],
        
        // Actions
        'actions' => [
            'export_details' => 'Экспорт деталей',
            'mark_reviewed' => 'Отметить как просмотренное',
            'refresh' => 'Обновить',
            'view_details' => 'Просмотр деталей',
            'rollback' => 'Откат',
            'view_rollback_history' => 'Просмотр истории откатов',
            'bulk_rollback' => 'Массовый откат',
            'revert_rollback' => 'Отменить откат',
        ],
        
        // Placeholders
        'placeholders' => [
            'rollback_reason' => 'Объясните, почему эта конфигурация должна быть откачена...',
            'bulk_rollback_reason' => 'Объясните, почему эти конфигурации должны быть откачены...',
            'revert_reason' => 'Объясните, почему этот откат должен быть отменен...',
        ],
        
        // Notifications
        'notifications' => [
            'rollback_success' => 'Конфигурация успешно откачена',
            'rollback_failed' => 'Операция отката не удалась',
            'bulk_rollback_success' => 'Успешно откачено :count конфигураций',
            'bulk_rollback_partial' => 'Откачено :success конфигураций, :failed не удалось',
            'revert_success' => 'Откат успешно отменен',
            'revert_failed' => 'Не удалось отменить откат',
            'original_change_not_found' => 'Запись исходного изменения не найдена',
            'rollback_audit_not_found' => 'Запись аудита отката не найдена',
            'anomaly_detected' => [
                'subject' => ':Severity Аномалия аудита: :type',
                'greeting' => 'Предупреждение аудита',
                'title' => 'Обнаружена аномалия аудита',
                'intro' => 'Обнаружена аномалия :type серьезности :severity для арендатора :tenant_id.',
                'detected_at' => 'Обнаружено в: :time',
                'details_header' => 'Детали аномалии:',
                'action' => 'Просмотр панели аудита',
            ],
            'compliance_issue' => [
                'subject' => 'Предупреждение оценки соответствия: :score%',
                'greeting' => 'Предупреждение соответствия',
                'title' => 'Обнаружена проблема соответствия',
                'intro' => 'Оценка соответствия для арендатора :tenant_id упала до :score%.',
                'summary' => 'Оценка соответствия: :score%',
                'failing_categories' => 'Проблемные категории:',
                'recommendations' => 'Рекомендации:',
                'action' => 'Просмотр панели соответствия',
            ],
        ],
        
        // Anomaly Types
        'anomaly_types' => [
            'high_change_frequency' => 'Высокая частота изменений',
            'bulk_changes' => 'Массовые изменения',
            'configuration_rollbacks' => 'Откаты конфигурации',
            'unauthorized_access' => 'Несанкционированный доступ',
            'data_integrity_issue' => 'Проблема целостности данных',
            'performance_degradation' => 'Снижение производительности',
        ],
        
        // Compliance Categories
        'compliance_categories' => [
            'audit_trail' => 'Аудиторский след',
            'data_retention' => 'Хранение данных',
            'regulatory' => 'Нормативное соответствие',
            'security' => 'Соответствие безопасности',
            'data_quality' => 'Качество данных',
        ],
        
        // Recommendations
        'recommendations' => [
            'investigate_changes' => 'Исследовать недавние изменения',
            'investigate_changes_desc' => 'Просмотрите недавние изменения конфигурации для выявления паттернов или несанкционированных модификаций.',
            'review_permissions' => 'Проверить права пользователей',
            'review_permissions_desc' => 'Убедитесь, что пользователи имеют соответствующие уровни доступа и рассмотрите внедрение дополнительных рабочих процессов утверждения.',
            'verify_user_actions' => 'Проверить действия пользователя',
            'verify_user_actions_desc' => 'Свяжитесь с пользователем, чтобы подтвердить, что эти массовые изменения были намеренными и авторизованными.',
            'analyze_rollbacks' => 'Анализировать паттерны откатов',
            'analyze_rollbacks_desc' => 'Исследуйте, почему конфигурации откатываются, чтобы выявить основные проблемы.',
            'review_logs' => 'Просмотреть системные журналы',
            'review_logs_desc' => 'Изучите подробные системные журналы, чтобы понять контекст этой аномалии.',
        ],
    ],
];