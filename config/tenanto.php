<?php

return [
    'locales' => [
        'en' => 'English',
        'lt' => 'Lietuvių',
        'ru' => 'Русский',
        'es' => 'Español',
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
