<?php

declare(strict_types=1);

return [
    'navigation' => 'Translations',
    'sections' => [
        'key' => 'Translation Key',
        'values' => 'Translation Values',
    ],
    'labels' => [
        'group' => 'Group',
        'key' => 'Key',
        'value' => 'Value',
        'last_updated' => 'Last Updated',
    ],
    'placeholders' => [
        'group' => 'app',
        'key' => 'nav.dashboard',
        'value' => '—',
    ],
    'helper_text' => [
        'key' => 'Define the group and key for this translation',
        'group' => 'PHP file name in lang/{locale}/ directory (e.g., "app" for app.php)',
        'key_full' => 'Translation key with dot notation support (e.g., "nav.dashboard")',
        'values' => 'Provide translations for each active language. Values are written to PHP lang files.',
        'default_language' => 'Default language',
    ],
    'empty' => [
        'heading' => 'No translations yet',
        'description' => 'Create translation entries to manage multi-language content.',
        'action' => 'Add First Translation',
    ],
    'modals' => [
        'delete' => [
            'heading' => 'Delete Translations',
            'description' => 'Are you sure you want to delete these translations? This will affect the application UI.',
        ],
    ],
    'table' => [
        'value_label' => ':locale Value',
        'language_label' => ':language (:code)',
    ],
];
