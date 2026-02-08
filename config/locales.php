<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Default Locale
    |--------------------------------------------------------------------------
    |
    | The default locale for the application. Lithuanian ('lt') is the primary
    | language for this Vilnius-based utilities platform.
    |
    */
    'default' => 'lt',

    /*
    |--------------------------------------------------------------------------
    | Available Locales
    |--------------------------------------------------------------------------
    |
    | List of available locales for the application. Each locale should have
    | a corresponding directory in the lang folder with translation files.
    | Lithuanian is listed first as the primary locale.
    |
    */
    'available' => [
        'lt' => [
            'label' => 'common.lithuanian',
            'abbreviation' => 'LT',
        ],
        'en' => [
            'label' => 'common.english',
            'abbreviation' => 'EN',
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
    | English ('en') is used as fallback since it has the most complete translations.
    |
    */
    'fallback' => 'en',
];