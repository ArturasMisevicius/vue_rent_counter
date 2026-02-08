<?php

return array (
  'dashboard' => 
  array (
    'expiring_subscriptions' => 
    array (
      'alert' => 'Предупреждение',
      'days' => '{1} :count день|[2,4] :count дня|[5,*] :count дней',
      'expires' => 'Истекает',
      'title' => 'Истекающие подписки',
    ),
    'organization_show' => 
    array (
      'actions' => 
      array (
        'back' => 'Назад',
        'deactivate' => 'Деактивировать',
        'edit' => 'Редактировать',
        'reactivate' => 'Реактивировать',
      ),
      'confirm_deactivate' => 'Подтвердить деактивацию',
      'contact_name' => 'Имя контакта',
      'created' => 'Создано',
      'email' => 'Эл. почта',
      'expiry_date' => 'Дата истечения',
      'limit_values' => 'Значения лимитов',
      'limits' => 'Лимиты',
      'manage_subscription' => 'Управлять подпиской',
      'no_subscription' => 'Нет подписки',
      'organization_info' => 'Информация об организации',
      'organization_name' => 'Название организации',
      'plan_type' => 'Тип плана',
      'start_date' => 'Дата начала',
      'stats' => 
      array (
        'active_tenants' => 'Активные арендаторы',
        'buildings' => 'Здания',
        'invoices' => 'Счета',
        'properties' => 'Недвижимость',
        'tenants' => 'Арендаторы',
      ),
      'status' => 'Статус',
      'subscription_details' => 'Детали подписки',
      'subtitle' => 'Панель управления организацией',
      'table' => 
      array (
        'created' => 'Создано',
        'email' => 'Эл. почта',
        'id' => 'ID',
        'name' => 'Имя',
        'property' => 'Недвижимость',
        'status' => 'Статус',
        'actions' => 'Actions',
      ),
      'tenant_id' => 'ID арендатора',
      'tenants_title' => 'Арендаторы',
      'relationship_insights' => 
      array (
        'title' => 'Relationship Insights',
        'occupied' => 'Occupied Properties',
        'vacant' => 'Vacant Properties',
        'metered' => 'Metered Properties',
        'draft_invoices' => 'Draft Invoices',
        'finalized_invoices' => 'Finalized Invoices',
        'paid_invoices' => 'Paid Invoices',
      ),
      'resources' => 
      array (
        'headline' => 'Related Resources',
        'action_view' => 'View',
        'buildings' => 
        array (
          'title' => 'Buildings',
          'subtitle' => 'Building portfolio linked to this organization',
          'empty' => 'No buildings found',
          'table' => 
          array (
            'name' => 'Name',
            'address' => 'Address',
            'properties' => 'Properties',
            'occupied' => 'Occupied',
            'vacant' => 'Vacant',
            'sample_properties' => 'Sample Properties',
            'actions' => 'Actions',
          ),
        ),
        'properties' => 
        array (
          'title' => 'Properties',
          'subtitle' => 'Properties with building, tenants, and invoice links',
          'empty' => 'No properties found',
          'table' => 
          array (
            'address' => 'Address',
            'type' => 'Type',
            'building' => 'Building',
            'area' => 'Area',
            'meters' => 'Meters',
            'tenants' => 'Current Tenants',
            'history' => 'History',
            'invoices' => 'Invoices',
            'latest_invoice' => 'Latest Invoice',
            'actions' => 'Actions',
          ),
          'open_admin' => 'Open in Admin',
          'vacant' => 'Vacant',
        ),
        'invoices' => 
        array (
          'title' => 'Invoices',
          'subtitle' => 'Invoice activity for this organization',
          'empty' => 'No invoices found',
          'table' => 
          array (
            'invoice' => 'Invoice',
            'tenant' => 'Tenant',
            'property' => 'Property',
            'period' => 'Period',
            'status' => 'Status',
            'total' => 'Total',
            'due' => 'Due',
            'actions' => 'Actions',
          ),
        ),
      ),
    ),
    'organizations' => 
    array (
      'active' => 'Активные',
      'inactive' => 'Неактивные',
      'no_organizations' => 'Нет организаций',
      'properties_count' => 'Количество недвижимости',
      'title' => 'Организации',
      'top_by_properties' => 'По количеству недвижимости',
      'total' => 'Всего',
      'view_all' => 'Посмотреть все',
    ),
    'organizations_create' => 
    array (
      'actions' => 
      array (
        'cancel' => 'Отменить',
        'create' => 'Создать',
      ),
      'admin_contact' => 'Контакт администратора',
      'contact_name' => 'Имя контакта',
      'email' => 'Эл. почта',
      'expiry_date' => 'Дата истечения',
      'organization_info' => 'Информация об организации',
      'organization_name' => 'Название организации',
      'password' => 'Пароль',
      'password_hint' => 'Минимум 8 символов',
      'plan_limits' => 
      array (
        'basic' => 'Базовый',
        'enterprise' => 'Корпоративный',
        'professional' => 'Профессиональный',
      ),
      'plan_type' => 'Тип плана',
      'select_plan' => 'Выбрать план',
      'subscription_details' => 'Детали подписки',
      'subtitle' => 'Создать новую организацию в системе',
      'title' => 'Создать организацию',
    ),
    'organizations_index' => 
    array (
      'create' => 'Создать',
      'filters' => 
      array (
        'all' => 'Все',
        'filter' => 'Фильтр',
        'search' => 'Поиск',
        'search_placeholder' => 'Поиск организаций...',
        'status' => 'Статус',
        'subscription_status' => 'Статус подписки',
      ),
      'subtitle' => 'Управление всеми организациями системы',
      'table' => 
      array (
        'actions' => 'Действия',
        'contact' => 'Контакт',
        'created' => 'Создано',
        'organization' => 'Организация',
        'status' => 'Статус',
        'subscription' => 'Подписка',
        'tenant_id' => 'ID арендатора',
      ),
      'title' => 'Список организаций',
    ),
    'quick_actions' => 
    array (
      'create_organization' => 'Создать организацию',
      'create_organization_desc' => 'Зарегистрировать новую организацию и владельца',
      'manage_organizations' => 'Управлять организациями',
      'manage_organizations_desc' => 'Открыть список организаций и фильтры',
      'manage_subscriptions' => 'Управлять подписками',
      'manage_subscriptions_desc' => 'Проверять продления, статусы и сроки',
      'title' => 'Быстрые действия',
      'create_subscription' => 'Создать подписку',
      'create_subscription_desc' => 'Проверить или назначить тарифные планы',
      'view_all_activity' => 'Показать всю активность',
      'view_all_activity_desc' => 'Перейти к последним событиям платформы',
    ),
    'recent_activity' => 
    array (
      'last_activity' => 'Последняя активность',
      'no_activity' => 'Нет активности',
      'title' => 'Недавняя активность',
      'occurred' => 'Occurred',
      'system' => 'System',
    ),
    'stats' => 
    array (
      'active_subscriptions' => 'Активные подписки',
      'cancelled_subscriptions' => 'Отмененные подписки',
      'expired_subscriptions' => 'Истекшие подписки',
      'expiring_soon' => 'Скоро истекают',
      'suspended_subscriptions' => 'Приостановленные подписки',
      'total_buildings' => 'Всего зданий',
      'total_invoices' => 'Всего счетов',
      'total_properties' => 'Всего недвижимости',
      'total_subscriptions' => 'Всего подписок',
      'total_tenants' => 'Всего арендаторов',
    ),
    'stats_descriptions' => 
    array (
      'active_organizations' => 'Активные организации',
      'active_subscriptions' => 'Активные подписки',
      'cancelled_subscriptions' => 'Отмененные подписки',
      'expired_subscriptions' => 'Истекшие подписки',
      'expiring_soon' => 'Подписки скоро истекают',
      'inactive_organizations' => 'Неактивные организации',
      'suspended_subscriptions' => 'Приостановленные подписки',
      'total_organizations' => 'Всего организаций',
      'total_subscriptions' => 'Всего подписок',
    ),
    'subtitle' => 'Панель управления суперадминистратора',
    'title' => 'Панель суперадминистратора',
    'badges' =>
    array (
      'platform' => 'Рабочее пространство суперадминистратора',
    ),
    'system_health' => 
    array (
      'title' => 'System Health',
      'description' => 'Latest status checks for critical services',
      'actions' => 
      array (
        'run_check' => 'Run Health Check',
      ),
      'empty' => 'No data available',
    ),
    'analytics' => 
    array (
      'title' => 'Analytics',
      'empty' => 'No data available',
    ),
    'organizations_list' => 
    array (
      'actions' => 
      array (
        'edit' => 'Edit',
        'view' => 'View',
      ),
      'empty' => 'Empty',
      'expires' => 'Expires',
      'no_subscription' => 'No Subscription',
      'status_active' => 'Status Active',
      'status_inactive' => 'Status Inactive',
    ),
    'organizations_widget' => 
    array (
      'active' => 'Active',
      'growth_down' => 'Growth Down',
      'growth_up' => 'Growth Up',
      'inactive' => 'Inactive',
      'new_this_month' => 'New This Month',
      'total' => 'Total',
    ),
    'overview' => 
    array (
      'organizations' => 
      array (
        'description' => 'Description',
        'empty' => 'Empty',
        'headers' => 
        array (
          'created' => 'Created',
          'manage' => 'Manage',
          'organization' => 'Organization',
          'status' => 'Status',
          'subscription' => 'Subscription',
        ),
        'no_subscription' => 'No Subscription',
        'open' => 'Open',
        'status_active' => 'Status Active',
        'status_inactive' => 'Status Inactive',
        'title' => 'Title',
      ),
      'resources' => 
      array (
        'buildings' => 
        array (
          'address' => 'Address',
          'empty' => 'Empty',
          'open_owners' => 'Open Owners',
          'organization' => 'Organization',
          'title' => 'Title',
        ),
        'description' => 'Description',
        'invoices' => 
        array (
          'amount' => 'Amount',
          'empty' => 'Empty',
          'manage' => 'Manage',
          'open_owners' => 'Open Owners',
          'organization' => 'Organization',
          'status' => 'Status',
          'title' => 'Title',
        ),
        'manage_orgs' => 'Manage Orgs',
        'properties' => 
        array (
          'building' => 'Building',
          'empty' => 'Empty',
          'open_owners' => 'Open Owners',
          'organization' => 'Organization',
          'title' => 'Title',
          'unknown_org' => 'Unknown Org',
        ),
        'tenants' => 
        array (
          'empty' => 'Empty',
          'not_assigned' => 'Not Assigned',
          'open_owners' => 'Open Owners',
          'organization' => 'Organization',
          'property' => 'Property',
          'status_active' => 'Status Active',
          'status_inactive' => 'Status Inactive',
          'title' => 'Title',
        ),
        'title' => 'Title',
      ),
      'subscriptions' => 
      array (
        'description' => 'Description',
        'empty' => 'Empty',
        'headers' => 
        array (
          'expires' => 'Expires',
          'manage' => 'Manage',
          'organization' => 'Organization',
          'plan' => 'Plan',
          'status' => 'Status',
        ),
        'open' => 'Open',
        'title' => 'Title',
      ),
    ),
    'recent_activity_widget' => 
    array (
      'columns' => 
      array (
        'action' => 'Action',
        'details' => 'Details',
        'id' => 'Id',
        'organization' => 'Organization',
        'resource' => 'Resource',
        'time' => 'Time',
        'user' => 'User',
      ),
      'default_system' => 'Default System',
      'description' => 'Description',
      'empty_description' => 'Empty Description',
      'empty_heading' => 'Empty Heading',
      'heading' => 'Heading',
      'modal_heading' => 'Modal Heading',
    ),
    'subscription_plans' => 
    array (
      'basic' => 'Basic',
      'enterprise' => 'Enterprise',
      'professional' => 'Professional',
      'title' => 'Subscriptions by Plan',
      'view_all' => 'View All',
    ),
    'widgets' => 
    array (
      'recent_activity' => 
      array (
        'no_activity' => 'No Activity',
        'title' => 'Title',
      ),
      'system_metrics' => 
      array (
        'active_sessions' => 'Active Sessions',
        'api_calls_today' => 'Api Calls Today',
        'storage_used' => 'Storage Used',
        'total_users' => 'Total Users',
      ),
      'tenant_overview' => 
      array (
        'active_tenants' => 'Active Tenants',
        'suspended_tenants' => 'Suspended Tenants',
        'total_tenants' => 'Total Tenants',
        'trial_tenants' => 'Trial Tenants',
      ),
    ),
  ),
  'navigation' => 
  array (
    'audit_logs' => 'Журналы аудита',
    'cluster' => 'Кластер',
    'group' => 'Группа',
    'system_config' => 'Конфигурация системы',
    'tenants' => 'Арендаторы',
    'users' => 'Пользователи',
  ),
  'subscription' => 
  array (
    'plan' => 
    array (
      'basic' => 'Базовый',
      'custom' => 'Пользовательский',
      'enterprise' => 'Корпоративный',
      'professional' => 'Профессиональный',
    ),
  ),
  'common' => 
  array (
    'status' => 
    array (
      'active' => 'Активный',
      'inactive' => 'Неактивный',
    ),
  ),
  'health' => 
  array (
    'critical' => 'Критический',
    'excellent' => 'Отличный',
    'good' => 'Хороший',
    'warning' => 'Предупреждение',
  ),
  'audit' => 
  array (
    'action' => 
    array (
      'backup_created' => 'Backup Created',
      'backup_restored' => 'Backup Restored',
      'billing_updated' => 'Billing Updated',
      'bulk_operation' => 'Bulk Operation',
      'feature_flag_changed' => 'Feature Flag Changed',
      'impersonation_ended' => 'Impersonation Ended',
      'notification_sent' => 'Notification Sent',
      'resource_quota_changed' => 'Resource Quota Changed',
      'system_config_changed' => 'System Config Changed',
      'system_config_created' => 'System Config Created',
      'system_config_deleted' => 'System Config Deleted',
      'system_config_updated' => 'System Config Updated',
      'tenant_activated' => 'Tenant Activated',
      'tenant_created' => 'Tenant Created',
      'tenant_deleted' => 'Tenant Deleted',
      'tenant_suspended' => 'Tenant Suspended',
      'tenant_updated' => 'Tenant Updated',
      'user_force_logout' => 'User Force Logout',
      'user_impersonated' => 'User Impersonated',
      'user_reactivated' => 'User Reactivated',
      'user_suspended' => 'User Suspended',
    ),
    'actions' => 
    array (
      'back_to_list' => 'Back To List',
      'cleanup' => 'Cleanup',
      'cleanup_old' => 'Cleanup Old',
      'export' => 'Export',
      'export_all' => 'Export All',
      'refresh' => 'Refresh',
      'view' => 'View',
      'view_admin' => 'View Admin',
      'view_details' => 'View Details',
      'view_target' => 'View Target',
      'view_tenant' => 'View Tenant',
    ),
    'bulk_actions' => 
    array (
      'export' => 'Export',
    ),
    'fields' => 
    array (
      'action' => 'Action',
      'admin' => 'Admin',
      'admin_id' => 'Admin Id',
      'basic_info' => 'Basic Info',
      'changes' => 'Changes',
      'cleanup_before_date' => 'Cleanup Before Date',
      'confirm_cleanup' => 'Confirm Cleanup',
      'created_at' => 'Created At',
      'from_date' => 'From Date',
      'impersonation' => 'Impersonation',
      'impersonation_session' => 'Impersonation Session',
      'impersonation_status' => 'Impersonation Status',
      'ip_address' => 'Ip Address',
      'session_id' => 'Session Id',
      'target' => 'Target',
      'target_id' => 'Target Id',
      'target_info' => 'Target Info',
      'target_type' => 'Target Type',
      'technical_info' => 'Technical Info',
      'tenant' => 'Tenant',
      'tenant_id' => 'Tenant Id',
      'timestamp' => 'Timestamp',
      'to_date' => 'To Date',
      'user_agent' => 'User Agent',
    ),
    'filters' => 
    array (
      'action' => 'Action',
      'admin' => 'Admin',
      'date_range' => 'Date Range',
      'has_changes' => 'Has Changes',
      'impersonation' => 'Impersonation',
      'target_type' => 'Target Type',
      'tenant' => 'Tenant',
    ),
    'modals' => 
    array (
      'cleanup' => 
      array (
        'description' => 'Description',
        'heading' => 'Heading',
      ),
      'details' => 
      array (
        'heading' => 'Heading',
      ),
      'export_all' => 
      array (
        'description' => 'Description',
        'heading' => 'Heading',
      ),
    ),
    'notifications' => 
    array (
      'cleanup_scheduled' => 'Cleanup Scheduled',
      'cleanup_scheduled_body' => 'Cleanup Scheduled Body',
      'export_all_started' => 'Export All Started',
      'export_started' => 'Export Started',
      'export_started_body' => 'Export Started Body',
    ),
    'pages' => 
    array (
      'list' => 
      array (
        'title' => 'Title',
      ),
    ),
    'plural' => 'Plural',
    'sections' => 
    array (
      'basic_info' => 'Basic Info',
      'details' => 'Details',
      'impersonation_info' => 'Impersonation Info',
      'target_info' => 'Target Info',
      'technical_details' => 'Technical Details',
    ),
    'singular' => 'Singular',
    'target_types' => 
    array (
      'organization' => 'Organization',
      'system_config' => 'System Config',
      'user' => 'User',
    ),
    'tooltips' => 
    array (
      'impersonated' => 'Impersonated',
    ),
    'values' => 
    array (
      'after' => 'After',
      'before' => 'Before',
      'direct_only' => 'Direct Only',
      'empty' => 'Empty',
      'impersonated' => 'Impersonated',
      'impersonated_only' => 'Impersonated Only',
      'no_changes' => 'No Changes',
      'system' => 'System',
      'unknown' => 'Unknown',
    ),
  ),
  'buildings' => 
  array (
    'title' => 'Buildings',
    'singular' => 'Building',
    'fields' => 
    array (
      'name' => 'Name',
      'address' => 'Address',
      'total_apartments' => 'Total Apartments',
      'properties' => 'Properties',
      'meters' => 'Meters',
      'tenants' => 'Tenants',
    ),
  ),
  'properties' => 
  array (
    'title' => 'Properties',
    'singular' => 'Property',
    'fields' => 
    array (
      'address' => 'Address',
      'type' => 'Type',
      'area' => 'Area (sqm)',
      'building' => 'Building',
      'meters' => 'Meters',
      'tenants' => 'Tenants',
    ),
  ),
  'config' => 
  array (
    'actions' => 
    array (
      'delete' => 'Delete',
      'duplicate' => 'Duplicate',
      'edit' => 'Edit',
      'view' => 'View',
    ),
    'bulk_actions' => 
    array (
      'update_category' => 'Update Category',
    ),
    'categories' => 
    array (
      'billing' => 'Billing',
      'features' => 'Features',
      'integrations' => 'Integrations',
      'maintenance' => 'Maintenance',
      'notifications' => 'Notifications',
      'security' => 'Security',
      'system' => 'System',
    ),
    'fields' => 
    array (
      'allowed_values' => 'Allowed Values',
      'category' => 'Category',
      'created_at' => 'Created At',
      'created_by' => 'Created By',
      'description' => 'Description',
      'encrypted' => 'Encrypted',
      'is_encrypted' => 'Is Encrypted',
      'is_public' => 'Is Public',
      'is_sensitive' => 'Is Sensitive',
      'key' => 'Key',
      'name' => 'Name',
      'new_key' => 'New Key',
      'public' => 'Public',
      'type' => 'Type',
      'updated_at' => 'Updated At',
      'updated_by' => 'Updated By',
      'validation_rules' => 'Validation Rules',
      'value' => 'Value',
    ),
    'filters' => 
    array (
      'category' => 'Category',
      'is_encrypted' => 'Is Encrypted',
      'is_public' => 'Is Public',
      'type' => 'Type',
    ),
    'help' => 
    array (
      'allowed_values' => 'Allowed Values',
      'array_value' => 'Array Value',
      'boolean_value' => 'Boolean Value',
      'category' => 'Category',
      'description' => 'Description',
      'is_encrypted' => 'Is Encrypted',
      'is_public' => 'Is Public',
      'json_value' => 'Json Value',
      'key' => 'Key',
      'name' => 'Name',
      'type' => 'Type',
      'validation_rules' => 'Validation Rules',
      'value' => 'Value',
    ),
    'modals' => 
    array (
      'delete' => 
      array (
        'confirm' => 'Confirm',
        'description' => 'Description',
        'heading' => 'Heading',
      ),
    ),
    'notifications' => 
    array (
      'bulk_updated' => 'Bulk Updated',
      'bulk_updated_body' => 'Bulk Updated Body',
      'created' => 'Created',
      'created_body' => 'Created Body',
      'deleted' => 'Deleted',
      'duplicated' => 'Duplicated',
      'updated' => 'Updated',
      'updated_body' => 'Updated Body',
    ),
    'pages' => 
    array (
      'create' => 
      array (
        'title' => 'Title',
      ),
      'edit' => 
      array (
        'title' => 'Title',
      ),
      'list' => 
      array (
        'title' => 'Title',
      ),
      'view' => 
      array (
        'title' => 'Title',
      ),
    ),
    'plural' => 'Plural',
    'sections' => 
    array (
      'basic_info' => 'Basic Info',
      'metadata' => 'Metadata',
      'validation' => 'Validation',
      'value' => 'Value',
      'value_settings' => 'Value Settings',
    ),
    'singular' => 'Singular',
    'tooltips' => 
    array (
      'encrypted' => 'Encrypted',
      'encrypted_value' => 'Encrypted Value',
      'not_encrypted' => 'Not Encrypted',
      'private' => 'Private',
      'public' => 'Public',
    ),
    'types' => 
    array (
      'array' => 'Array',
      'boolean' => 'Boolean',
      'float' => 'Float',
      'integer' => 'Integer',
      'json' => 'Json',
      'string' => 'String',
    ),
    'values' => 
    array (
      'all' => 'All',
      'encrypted_only' => 'Encrypted Only',
      'false' => 'False',
      'private_only' => 'Private Only',
      'public_only' => 'Public Only',
      'true' => 'True',
      'unencrypted_only' => 'Unencrypted Only',
    ),
  ),
  'tenant' => 
  array (
    'actions' => 
    array (
      'activate' => 'Activate',
      'create' => 'Create',
      'impersonate' => 'Impersonate',
      'suspend' => 'Suspend',
    ),
    'fields' => 
    array (
      'api_calls_today' => 'Api Calls Today',
      'created_at' => 'Created At',
      'domain' => 'Domain',
      'is_active' => 'Is Active',
      'last_activity' => 'Last Activity',
      'name' => 'Name',
      'plan' => 'Plan',
      'primary_contact_email' => 'Primary Contact Email',
      'properties_count' => 'Properties Count',
      'slug' => 'Slug',
      'storage_used' => 'Storage Used',
      'subscription_ends_at' => 'Subscription Ends At',
      'suspended_at' => 'Suspended At',
      'suspension_reason' => 'Suspension Reason',
      'trial_ends_at' => 'Trial Ends At',
      'updated_at' => 'Updated At',
      'users_count' => 'Users Count',
    ),
    'notifications' => 
    array (
      'activated' => 'Activated',
      'created' => 'Created',
      'created_body' => 'Created Body',
      'suspended' => 'Suspended',
      'updated' => 'Updated',
      'updated_body' => 'Updated Body',
    ),
    'placeholders' => 
    array (
      'no_subscription' => 'No Subscription',
      'no_trial' => 'No Trial',
    ),
    'plural' => 'Plural',
    'sections' => 
    array (
      'basic_info' => 'Basic Info',
      'metrics' => 'Metrics',
      'overview' => 'Overview',
      'status' => 'Status',
      'subscription' => 'Subscription',
      'suspension' => 'Suspension',
      'timestamps' => 'Timestamps',
    ),
    'singular' => 'Singular',
    'status' => 
    array (
      'active' => 'Active',
      'cancelled' => 'Cancelled',
      'pending' => 'Pending',
      'suspended' => 'Suspended',
    ),
    'tabs' => 
    array (
      'active' => 'Active',
      'all' => 'All',
      'expired' => 'Expired',
      'suspended' => 'Suspended',
      'trial' => 'Trial',
    ),
  ),
  'tenants' => 
  array (
    'actions' => 
    array (
      'activate' => 'Activate',
      'bulk_activate' => 'Bulk Activate',
      'bulk_suspend' => 'Bulk Suspend',
      'impersonate' => 'Impersonate',
      'suspend' => 'Suspend',
    ),
    'billing_cycles' => 
    array (
      'monthly' => 'Monthly',
      'quarterly' => 'Quarterly',
      'yearly' => 'Yearly',
    ),
    'fields' => 
    array (
      'allow_registration' => 'Allow Registration',
      'api_access_enabled' => 'Api Access Enabled',
      'auto_billing' => 'Auto Billing',
      'billing_address' => 'Billing Address',
      'billing_cycle' => 'Billing Cycle',
      'billing_email' => 'Billing Email',
      'billing_name' => 'Billing Name',
      'created_at' => 'Created At',
      'currency' => 'Currency',
      'current_api_calls' => 'Current Api Calls',
      'current_storage_gb' => 'Current Storage Gb',
      'current_users' => 'Current Users',
      'enforce_quotas' => 'Enforce Quotas',
      'locale' => 'Locale',
      'maintenance_mode' => 'Maintenance Mode',
      'max_api_calls_per_month' => 'Max Api Calls Per Month',
      'max_storage_gb' => 'Max Storage Gb',
      'max_users' => 'Max Users',
      'monthly_price' => 'Monthly Price',
      'name' => 'Name',
      'next_billing_date' => 'Next Billing Date',
      'quota_notifications' => 'Quota Notifications',
      'require_email_verification' => 'Require Email Verification',
      'setup_fee' => 'Setup Fee',
      'slug' => 'Slug',
      'status' => 'Status',
      'subscription_ends_at' => 'Subscription Ends At',
      'subscription_plan' => 'Subscription Plan',
      'suspension_reason' => 'Suspension Reason',
      'timezone' => 'Timezone',
      'trial_ends_at' => 'Trial Ends At',
    ),
    'help' => 
    array (
      'allow_registration' => 'Allow Registration',
      'api_access_enabled' => 'Api Access Enabled',
      'current_api_calls' => 'Current Api Calls',
      'current_storage' => 'Current Storage',
      'current_users' => 'Current Users',
      'enforce_quotas' => 'Enforce Quotas',
      'maintenance_mode' => 'Maintenance Mode',
      'quota_notifications' => 'Quota Notifications',
      'require_email_verification' => 'Require Email Verification',
    ),
    'modals' => 
    array (
      'activate' => 
      array (
        'description' => 'Description',
        'heading' => 'Heading',
      ),
      'bulk_suspend' => 
      array (
        'description' => 'Description',
        'heading' => 'Heading',
      ),
      'suspend' => 
      array (
        'description' => 'Description',
        'heading' => 'Heading',
      ),
    ),
    'notifications' => 
    array (
      'activated' => 'Activated',
      'bulk_activated' => 'Bulk Activated',
      'bulk_suspended' => 'Bulk Suspended',
      'suspended' => 'Suspended',
    ),
    'sections' => 
    array (
      'billing' => 'Billing',
      'quotas' => 'Quotas',
      'settings' => 'Settings',
      'subscription' => 'Subscription',
    ),
  ),
  'user' => 
  array (
    'actions' => 
    array (
      'activity_report' => 'Activity Report',
      'create' => 'Create',
      'impersonate' => 'Impersonate',
      'reactivate' => 'Reactivate',
      'suspend' => 'Suspend',
    ),
    'bulk_actions' => 
    array (
      'reactivate' => 'Reactivate',
      'suspend' => 'Suspend',
    ),
    'fields' => 
    array (
      'created_at' => 'Created At',
      'email' => 'Email',
      'email_verified' => 'Email Verified',
      'is_active' => 'Is Active',
      'last_login' => 'Last Login',
      'last_login_at' => 'Last Login At',
      'login_count' => 'Login Count',
      'name' => 'Name',
      'organization' => 'Organization',
      'phone' => 'Phone',
      'status' => 'Status',
      'suspended_at' => 'Suspended At',
      'suspension_reason' => 'Suspension Reason',
    ),
    'filters' => 
    array (
      'active' => 'Active',
      'all' => 'All',
      'email_verified' => 'Email Verified',
      'inactive' => 'Inactive',
      'is_active' => 'Is Active',
      'organization' => 'Organization',
      'recent_login' => 'Recent Login',
      'suspended' => 'Suspended',
      'unverified' => 'Unverified',
      'verified' => 'Verified',
    ),
    'notifications' => 
    array (
      'bulk_reactivated' => 'Bulk Reactivated',
      'bulk_suspended' => 'Bulk Suspended',
      'created' => 'Created',
      'created_body' => 'Created Body',
      'impersonation_started' => 'Impersonation Started',
      'impersonation_started_body' => 'Impersonation Started Body',
      'reactivated' => 'Reactivated',
      'suspended' => 'Suspended',
    ),
    'placeholders' => 
    array (
      'never_logged_in' => 'Never Logged In',
    ),
    'plural' => 'Plural',
    'sections' => 
    array (
      'activity' => 'Activity',
      'basic_info' => 'Basic Info',
      'status' => 'Status',
    ),
    'singular' => 'Singular',
    'tabs' => 
    array (
      'active' => 'Active',
      'all' => 'All',
      'recent' => 'Recent',
      'suspended' => 'Suspended',
      'unverified' => 'Unverified',
    ),
  ),
  'users' => 
  array (
    'actions' => 
    array (
      'back_to_user' => 'Back To User',
      'clear_sessions' => 'Clear Sessions',
      'disable_2fa' => 'Disable 2fa',
      'export_report' => 'Export Report',
      'impersonate' => 'Impersonate',
      'reactivate' => 'Reactivate',
      'refresh_report' => 'Refresh Report',
      'reset_password' => 'Reset Password',
      'start_impersonation' => 'Start Impersonation',
      'suspend' => 'Suspend',
      'view_activity' => 'View Activity',
    ),
    'default_suspension_reason' => 'Default Suspension Reason',
    'descriptions' => 
    array (
      'activity_timeline' => 'Activity Timeline',
    ),
    'fields' => 
    array (
      'audit_entries' => 'Audit Entries',
      'created_at' => 'Created At',
      'email' => 'Email',
      'email_verified' => 'Email Verified',
      'email_verified_at' => 'Email Verified At',
      'force_password_reset' => 'Force Password Reset',
      'is_active' => 'Is Active',
      'last_activity' => 'Last Activity',
      'last_login' => 'Last Login',
      'name' => 'Name',
      'organization' => 'Organization',
      'password' => 'Password',
      'password_confirmation' => 'Password Confirmation',
      'report_generated' => 'Report Generated',
      'roles' => 'Roles',
      'status' => 'Status',
      'suspended_at' => 'Suspended At',
      'suspension_reason' => 'Suspension Reason',
      'tenant_status' => 'Tenant Status',
      'total_sessions' => 'Total Sessions',
      'two_factor' => 'Two Factor',
      'updated_at' => 'Updated At',
    ),
    'help' => 
    array (
      'email_verified_at' => 'Email Verified At',
      'force_password_reset' => 'Force Password Reset',
      'is_active' => 'Is Active',
      'organization' => 'Organization',
      'password_leave_blank' => 'Password Leave Blank',
      'roles' => 'Roles',
      'suspended_at' => 'Suspended At',
    ),
    'messages' => 
    array (
      'email_copied' => 'Email Copied',
    ),
    'modals' => 
    array (
      'clear_sessions' => 
      array (
        'description' => 'Description',
        'heading' => 'Heading',
      ),
      'delete' => 
      array (
        'description' => 'Description',
        'heading' => 'Heading',
      ),
      'disable_2fa' => 
      array (
        'description' => 'Description',
        'heading' => 'Heading',
      ),
      'impersonate' => 
      array (
        'description' => 'Description',
        'heading' => 'Heading',
      ),
      'reactivate' => 
      array (
        'description' => 'Description',
        'heading' => 'Heading',
      ),
      'reset_password' => 
      array (
        'description' => 'Description',
        'heading' => 'Heading',
      ),
      'suspend' => 
      array (
        'description' => 'Description',
        'heading' => 'Heading',
      ),
    ),
    'notifications' => 
    array (
      '2fa_disabled' => '2fa Disabled',
      '2fa_disabled_body' => '2fa Disabled Body',
      'cannot_delete_super_admin' => 'Cannot Delete Super Admin',
      'export_started' => 'Export Started',
      'export_started_body' => 'Export Started Body',
      'impersonation_failed' => 'Impersonation Failed',
      'impersonation_started' => 'Impersonation Started',
      'impersonation_started_body' => 'Impersonation Started Body',
      'password_reset' => 'Password Reset',
      'password_reset_body' => 'Password Reset Body',
      'reactivation_failed' => 'Reactivation Failed',
      'sessions_cleared' => 'Sessions Cleared',
      'sessions_cleared_body' => 'Sessions Cleared Body',
      'suspension_failed' => 'Suspension Failed',
      'user_reactivated' => 'User Reactivated',
      'user_reactivated_body' => 'User Reactivated Body',
      'user_suspended' => 'User Suspended',
      'user_suspended_body' => 'User Suspended Body',
      'user_updated' => 'User Updated',
      'user_updated_body' => 'User Updated Body',
    ),
    'pages' => 
    array (
      'activity_report' => 
      array (
        'breadcrumb' => 'Breadcrumb',
        'title' => 'Title',
      ),
    ),
    'sections' => 
    array (
      'activity_overview' => 'Activity Overview',
      'activity_summary' => 'Activity Summary',
      'activity_timeline' => 'Activity Timeline',
      'basic_information' => 'Basic Information',
      'organization_activity' => 'Organization Activity',
      'recent_activity' => 'Recent Activity',
      'recent_sessions' => 'Recent Sessions',
      'role_management' => 'Role Management',
      'status_settings' => 'Status Settings',
      'suspension_info' => 'Suspension Info',
      'tenant_assignment' => 'Tenant Assignment',
      'tenant_information' => 'Tenant Information',
    ),
    'values' => 
    array (
      'activity_logged' => 'Activity Logged',
      'never' => 'Never',
    ),
  ),
);
