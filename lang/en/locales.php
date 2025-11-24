<?php

declare(strict_types=1);

return [
    'navigation' => 'Languages',

    'labels' => [
        'locale' => 'Locale',
        'code' => 'Locale Code',
        'name' => 'Language Name',
        'native_name' => 'Native Name',
        'active' => 'Active',
        'default' => 'Default Language',
        'order' => 'Display Order',
        'created' => 'Created',
    ],

    'sections' => [
        'details' => 'Language Details',
        'settings' => 'Settings',
    ],

    'placeholders' => [
        'code' => 'en',
        'name' => 'English',
        'native_name' => 'English',
    ],

    'helper_text' => [
        'details' => 'Configure language settings for the application',
        'code' => 'ISO 639-1 language code (e.g., en, lt, ru)',
        'name' => 'Display name in English',
        'native_name' => 'Display name in the native language',
        'active' => 'Only active languages are available for selection',
        'default' => 'Only one language should be set as default',
        'order' => 'Lower numbers appear first in language selectors',
    ],

    'filters' => [
        'active_placeholder' => 'All languages',
        'active_only' => 'Active only',
        'inactive_only' => 'Inactive only',
        'default_placeholder' => 'All languages',
        'default_only' => 'Default only',
        'non_default_only' => 'Non-default only',
    ],

    'empty' => [
        'heading' => 'No languages configured',
        'description' => 'Add languages to enable multi-language support.',
        'action' => 'Add First Language',
    ],

    'modals' => [
        'delete' => [
            'heading' => 'Delete Languages',
            'description' => 'Are you sure you want to delete these languages? This may affect translations.',
        ],
    ],

    'validation' => [
        'locale' => [
            'required' => 'Locale is required.',
            'string' => 'Locale must be text.',
            'max' => 'Locale may not exceed 5 characters.',
        ],
    ],
];
