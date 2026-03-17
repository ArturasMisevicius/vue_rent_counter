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
