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
        'set_default' => [
            'heading' => 'Set Default Language',
            'description' => 'Are you sure you want to set this language as the default? The current default language will be unset.',
        ],
    ],

    'validation' => [
        'locale' => [
            'required' => 'Locale is required.',
            'string' => 'Locale must be text.',
            'max' => 'Locale may not exceed 5 characters.',
        ],
        'code_format' => 'The language code must be in ISO 639-1 format (e.g., en, en-US)',
    ],

    'actions' => [
        'activate' => 'Activate',
        'deactivate' => 'Deactivate',
        'bulk_activate' => 'Activate Selected',
        'bulk_deactivate' => 'Deactivate Selected',
        'set_default' => 'Set as Default',
    ],

    'messages' => [
        'code_copied' => 'Language code copied to clipboard',
    ],

    'notifications' => [
        'default_set' => 'Default language updated successfully',
    ],

    'errors' => [
        'cannot_delete_default' => 'Cannot delete the default language',
        'cannot_delete_last_active' => 'Cannot delete the last active language',
        'cannot_deactivate_default' => 'Cannot deactivate the default language',
    ],
];
