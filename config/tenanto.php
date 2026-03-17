<?php

return [
    'auth' => [
        'session_history_cookie_name' => 'tenanto_authenticated_session',
        'session_history_cookie_minutes' => 10080,
    ],

    'locales' => [
        'en' => 'English',
        'lt' => 'Lietuvių',
        'ru' => 'Русский',
    ],

    'subscription' => [
        'grace_period_days' => 7,
    ],

    'shell' => [
        'polling' => [
            'notifications' => 30,
            'tenant_home' => 30,
        ],
        'search_debounce_ms' => 300,
        'notifications' => [
            'limit' => 8,
            'preview_length' => 120,
        ],
        'navigation' => [
            'roles' => [
                'superadmin' => [
                    'platform' => [
                        [
                            'route' => 'filament.admin.pages.platform-dashboard',
                            'label' => 'dashboard.title',
                        ],
                        [
                            'route' => 'filament.admin.resources.organizations.index',
                            'label' => 'shell.navigation.items.organizations',
                        ],
                        [
                            'route' => 'filament.admin.resources.users.index',
                            'label' => 'Users',
                        ],
                        [
                            'route' => 'filament.admin.resources.subscriptions.index',
                            'label' => 'Subscriptions',
                        ],
                        [
                            'route' => 'filament.admin.pages.system-configuration',
                            'label' => 'System Configuration',
                        ],
                        [
                            'route' => 'filament.admin.resources.audit-logs.index',
                            'label' => 'Audit Logs',
                        ],
                        [
                            'route' => 'filament.admin.resources.platform-notifications.index',
                            'label' => 'Platform Notifications',
                        ],
                        [
                            'route' => 'filament.admin.resources.languages.index',
                            'label' => 'Languages',
                        ],
                        [
                            'route' => 'filament.admin.pages.translation-management',
                            'label' => 'Translation Management',
                        ],
                        [
                            'route' => 'filament.admin.resources.security-violations.index',
                            'label' => 'Security Violations',
                        ],
                        [
                            'route' => 'filament.admin.pages.integration-health',
                            'label' => 'Integration Health',
                        ],
                    ],
                    'account' => [
                        [
                            'route' => 'filament.admin.pages.profile',
                            'label' => 'shell.navigation.items.profile',
                        ],
                    ],
                ],
                'admin' => [
                    'organization' => [
                        [
                            'route' => 'filament.admin.pages.organization-dashboard',
                            'label' => 'dashboard.title',
                        ],
                        [
                            'route' => 'filament.admin.resources.buildings.index',
                            'label' => 'admin.buildings.plural',
                        ],
                        [
                            'route' => 'filament.admin.resources.properties.index',
                            'label' => 'admin.properties.plural',
                        ],
                        [
                            'route' => 'filament.admin.pages.reports',
                            'label' => 'shell.navigation.items.reports',
                        ],
                        [
                            'route' => 'filament.admin.resources.tenants.index',
                            'label' => 'admin.tenants.plural',
                        ],
                        [
                            'route' => 'filament.admin.resources.meters.index',
                            'label' => 'admin.meters.plural',
                        ],
                        [
                            'route' => 'filament.admin.resources.meter-readings.index',
                            'label' => 'admin.meter_readings.plural',
                        ],
                        [
                            'route' => 'filament.admin.resources.invoices.index',
                            'label' => 'admin.invoices.plural',
                        ],
                        [
                            'route' => 'filament.admin.resources.providers.index',
                            'label' => 'admin.providers.plural',
                        ],
                        [
                            'route' => 'filament.admin.resources.tariffs.index',
                            'label' => 'admin.tariffs.plural',
                        ],
                    ],
                    'account' => [
                        [
                            'route' => 'filament.admin.pages.profile',
                            'label' => 'shell.navigation.items.profile',
                        ],
                        [
                            'route' => 'filament.admin.pages.settings',
                            'label' => 'shell.navigation.items.settings',
                        ],
                    ],
                ],
                'manager' => [
                    'organization' => [
                        [
                            'route' => 'filament.admin.pages.organization-dashboard',
                            'label' => 'dashboard.title',
                        ],
                        [
                            'route' => 'filament.admin.resources.buildings.index',
                            'label' => 'admin.buildings.plural',
                        ],
                        [
                            'route' => 'filament.admin.resources.properties.index',
                            'label' => 'admin.properties.plural',
                        ],
                        [
                            'route' => 'filament.admin.pages.reports',
                            'label' => 'shell.navigation.items.reports',
                        ],
                        [
                            'route' => 'filament.admin.resources.tenants.index',
                            'label' => 'admin.tenants.plural',
                        ],
                        [
                            'route' => 'filament.admin.resources.meters.index',
                            'label' => 'admin.meters.plural',
                        ],
                        [
                            'route' => 'filament.admin.resources.meter-readings.index',
                            'label' => 'admin.meter_readings.plural',
                        ],
                        [
                            'route' => 'filament.admin.resources.invoices.index',
                            'label' => 'admin.invoices.plural',
                        ],
                        [
                            'route' => 'filament.admin.resources.providers.index',
                            'label' => 'admin.providers.plural',
                        ],
                        [
                            'route' => 'filament.admin.resources.tariffs.index',
                            'label' => 'admin.tariffs.plural',
                        ],
                    ],
                    'account' => [
                        [
                            'route' => 'filament.admin.pages.profile',
                            'label' => 'shell.navigation.items.profile',
                        ],
                        [
                            'route' => 'filament.admin.pages.settings',
                            'label' => 'shell.navigation.items.settings',
                        ],
                    ],
                ],
            ],
        ],
        'locale_sources' => [
            'configured' => 'config.tenanto.locales',
            'managed' => 'App\\Models\\Language',
        ],
    ],

    'search' => [
        'min_query_length' => 2,
        'limit' => 5,
        'role_groups' => [
            'superadmin' => ['platform'],
            'admin' => ['organization'],
            'manager' => ['organization'],
            'tenant' => ['tenant'],
        ],
        'group_labels' => [
            'platform' => 'shell.search.groups.platform',
            'organization' => 'shell.search.groups.organization',
            'tenant' => 'shell.search.groups.tenant',
        ],
        'providers' => [
            'organizations' => [
                'group' => 'platform',
                'route' => 'filament.admin.resources.organizations.view',
            ],
            'users' => [
                'group' => 'organization',
                'route' => 'filament.admin.resources.users.view',
            ],
        ],
    ],
];
