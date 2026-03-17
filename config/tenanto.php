<?php

return [
    'locales' => [
        'en' => [
            'abbreviation' => 'EN',
            'native_name' => 'English',
            'flag' => 'GB',
        ],
        'lt' => [
            'abbreviation' => 'LT',
            'native_name' => 'Lietuvių',
            'flag' => 'LT',
        ],
        'ru' => [
            'abbreviation' => 'RU',
            'native_name' => 'Русский',
            'flag' => 'RU',
        ],
        'es' => [
            'abbreviation' => 'ES',
            'native_name' => 'Español',
            'flag' => 'ES',
        ],
    ],
    'polling' => [
        'notifications' => 30,
        'tenant_home' => 120,
    ],
    'notifications' => [
        'panel_limit' => 20,
    ],
    'search' => [
        'debounce' => 300,
        'labels' => [
            'organizations' => 'shell.search_groups.organizations',
            'users' => 'shell.search_groups.users',
            'buildings' => 'shell.search_groups.buildings',
            'properties' => 'shell.search_groups.properties',
            'tenants' => 'shell.search_groups.tenants',
            'meters' => 'shell.search_groups.meters',
            'meter_readings' => 'shell.search_groups.meter_readings',
            'invoices' => 'shell.search_groups.invoices',
        ],
        'groups' => [
            'superadmin' => ['organizations', 'users', 'buildings', 'properties', 'tenants', 'meters', 'invoices'],
            'admin' => ['buildings', 'properties', 'tenants', 'meters', 'meter_readings', 'invoices'],
            'manager' => ['buildings', 'properties', 'tenants', 'meters', 'meter_readings', 'invoices'],
            'tenant' => ['invoices', 'meter_readings'],
        ],
    ],
    'routes' => [
        'tenant_navigation' => [
            'home' => 'tenant.home',
            'readings' => 'tenant.readings.index',
            'invoices' => 'tenant.invoices.index',
            'profile' => 'profile.edit',
        ],
        'account' => [
            'profile' => 'profile.edit',
        ],
        'search' => [
            'organizations' => [
                'view' => 'filament.admin.resources.organizations.view',
            ],
            'users' => [
                'view' => 'filament.admin.resources.users.view',
            ],
        ],
    ],
];
