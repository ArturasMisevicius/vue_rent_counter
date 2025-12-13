<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Available Locales
    |--------------------------------------------------------------------------
    |
    | List of available locales for the application. Each locale should have
    | a corresponding directory in the lang folder with translation files.
    |
    */
    'available' => [
        'en' => [
            'label' => 'common.english',
            'abbreviation' => 'EN',
        ],
        'lt' => [
            'label' => 'common.lithuanian', 
            'abbreviation' => 'LT',
        ],
        'ru' => [
            'label' => 'common.russian',
            'abbreviation' => 'RU',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Fallback Locale
    |--------------------------------------------------------------------------
    |
    | The fallback locale is used when the current locale is not available
    | or when a translation key is missing in the current locale.
    |
    */
    'fallback' => 'en',
];