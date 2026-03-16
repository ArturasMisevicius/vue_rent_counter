<?php

declare(strict_types=1);

$settingsPath = lang_path('locales.php');

if (is_file($settingsPath)) {
    /** @var array<string, mixed> $settings */
    $settings = require $settingsPath;

    return $settings;
}

return [
    'default' => 'lt',
    'fallback' => 'en',
    'available' => [
        'lt' => [
            'label' => 'common.lithuanian',
            'abbreviation' => 'LT',
            'native_name' => 'Lietuvių',
        ],
        'en' => [
            'label' => 'common.english',
            'abbreviation' => 'EN',
            'native_name' => 'English',
        ],
        'ru' => [
            'label' => 'common.russian',
            'abbreviation' => 'RU',
            'native_name' => 'Русский',
        ],
    ],
];
