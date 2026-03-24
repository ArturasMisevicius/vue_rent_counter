<?php

$organizationNavigation = [
    [
        'route' => 'filament.admin.pages.dashboard',
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
];

$adminAccountNavigation = [
    [
        'route' => 'filament.admin.pages.profile',
        'label' => 'shell.navigation.items.profile',
    ],
    [
        'route' => 'filament.admin.pages.settings',
        'label' => 'shell.navigation.items.settings',
    ],
];

return [
    'auth' => [
        'session_history_cookie_name' => 'tenanto_authenticated_session',
        'session_history_cookie_minutes' => 10080,
    ],

    'locales' => [
        'en' => 'English',
        'lt' => 'Lietuvių',
        'ru' => 'Русский',
        'es' => 'Español',
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
            'limit' => 10,
            'preview_length' => 120,
        ],
        'navigation' => [
            'roles' => [
                'superadmin' => [
                    'platform' => [
                        [
                            'route' => 'filament.admin.resources.organizations.index',
                            'label' => 'superadmin.organizations.plural',
                        ],
                        [
                            'route' => 'filament.admin.resources.users.index',
                            'label' => 'shell.navigation.items.users',
                        ],
                        [
                            'route' => 'filament.admin.resources.subscriptions.index',
                            'label' => 'shell.navigation.items.subscriptions',
                        ],
                        [
                            'route' => 'filament.admin.pages.system-configuration',
                            'label' => 'shell.navigation.items.system_configuration',
                        ],
                        [
                            'route' => 'filament.admin.resources.audit-logs.index',
                            'label' => 'shell.navigation.items.audit_logs',
                        ],
                        [
                            'route' => 'filament.admin.pages.platform-notifications',
                            'label' => 'shell.navigation.items.platform_notifications',
                        ],
                        [
                            'route' => 'filament.admin.resources.languages.index',
                            'label' => 'shell.navigation.items.languages',
                        ],
                        [
                            'route' => 'filament.admin.pages.translation-management',
                            'label' => 'shell.navigation.items.translations',
                        ],
                        [
                            'route' => 'filament.admin.resources.security-violations.index',
                            'label' => 'shell.navigation.items.security_violations',
                        ],
                        [
                            'route' => 'filament.admin.pages.integration-health',
                            'label' => 'shell.navigation.items.integration_health',
                        ],
                    ],
                    'properties' => [
                        [
                            'route' => 'filament.admin.resources.buildings.index',
                            'label' => 'admin.buildings.plural',
                        ],
                        [
                            'route' => 'filament.admin.resources.properties.index',
                            'label' => 'admin.properties.plural',
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
                    ],
                    'billing' => [
                        [
                            'route' => 'filament.admin.resources.invoices.index',
                            'label' => 'admin.invoices.plural',
                        ],
                        [
                            'route' => 'filament.admin.resources.tariffs.index',
                            'label' => 'admin.tariffs.plural',
                        ],
                        [
                            'route' => 'filament.admin.resources.providers.index',
                            'label' => 'admin.providers.plural',
                        ],
                        [
                            'route' => 'filament.admin.resources.service-configurations.index',
                            'label' => 'admin.service_configurations.plural',
                        ],
                        [
                            'route' => 'filament.admin.resources.utility-services.index',
                            'label' => 'admin.utility_services.plural',
                        ],
                    ],
                    'reports' => [
                        [
                            'route' => 'filament.admin.pages.reports',
                            'label' => 'shell.navigation.items.reports',
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
                'admin' => [
                    'properties' => [
                        [
                            'route' => 'filament.admin.pages.dashboard',
                            'label' => 'dashboard.title',
                            'active_patterns' => [
                                'filament.admin.pages.dashboard',
                                'filament.admin.pages.organization-dashboard',
                            ],
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
                    ],
                    'billing' => [
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
                        [
                            'route' => 'filament.admin.resources.service-configurations.index',
                            'label' => 'admin.service_configurations.plural',
                        ],
                        [
                            'route' => 'filament.admin.resources.utility-services.index',
                            'label' => 'admin.utility_services.plural',
                        ],
                    ],
                    'reports' => [
                        [
                            'route' => 'filament.admin.pages.reports',
                            'label' => 'shell.navigation.items.reports',
                        ],
                    ],
                    'account' => $adminAccountNavigation,
                ],
                'tenant' => [
                    'my_home' => [
                        [
                            'route' => 'filament.admin.pages.dashboard',
                            'label' => 'tenant.navigation.home',
                            'active_patterns' => [
                                'filament.admin.pages.dashboard',
                                'filament.admin.pages.tenant-dashboard',
                                'filament.admin.pages.tenant-property-details',
                                'tenant.home',
                                'tenant.property.show',
                            ],
                        ],
                        [
                            'route' => 'filament.admin.pages.tenant-property-details',
                            'label' => 'tenant.pages.property.title',
                        ],
                        [
                            'route' => 'filament.admin.pages.tenant-submit-meter-reading',
                            'label' => 'tenant.navigation.readings',
                        ],
                        [
                            'route' => 'filament.admin.pages.tenant-invoice-history',
                            'label' => 'tenant.navigation.invoices',
                        ],
                    ],
                    'account' => [
                        [
                            'route' => 'filament.admin.pages.profile',
                            'label' => 'shell.navigation.items.profile',
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
            'superadmin' => ['organizations', 'buildings', 'properties', 'tenants', 'invoices', 'readings'],
            'admin' => ['buildings', 'properties', 'tenants', 'invoices', 'readings'],
            'tenant' => ['invoices', 'readings'],
        ],
        'group_labels' => [
            'organizations' => 'shell.search.groups.organizations',
            'buildings' => 'shell.search.groups.buildings',
            'properties' => 'shell.search.groups.properties',
            'tenants' => 'shell.search.groups.tenants',
            'invoices' => 'shell.search.groups.invoices',
            'readings' => 'shell.search.groups.readings',
        ],
        'providers' => [
            'organizations' => [
                'group' => 'organizations',
                'route' => 'filament.admin.resources.organizations.view',
            ],
            'buildings' => [
                'group' => 'buildings',
                'route' => 'filament.admin.resources.buildings.view',
                'superadmin_route' => 'filament.admin.resources.buildings.view',
            ],
            'properties' => [
                'group' => 'properties',
                'route' => 'filament.admin.resources.properties.view',
                'superadmin_route' => 'filament.admin.resources.properties.view',
            ],
            'tenants' => [
                'group' => 'tenants',
                'route' => 'filament.admin.resources.tenants.view',
                'superadmin_route' => 'filament.admin.resources.users.view',
            ],
            'invoices' => [
                'group' => 'invoices',
                'route' => 'filament.admin.resources.invoices.view',
                'superadmin_route' => 'filament.admin.resources.invoices.view',
                'tenant_route' => 'filament.admin.pages.tenant-invoice-history',
            ],
            'readings' => [
                'group' => 'readings',
                'route' => 'filament.admin.resources.meter-readings.view',
                'superadmin_route' => 'filament.admin.resources.meter-readings.view',
                'tenant_route' => 'filament.admin.pages.tenant-dashboard',
            ],
        ],
    ],
];
