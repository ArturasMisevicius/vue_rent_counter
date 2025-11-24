<?php

declare(strict_types=1);

return [
    'navigation' => 'Kalbos',

    'labels' => [
        'locale' => 'Lokalė',
        'code' => 'Lokalės kodas',
        'name' => 'Kalbos pavadinimas',
        'native_name' => 'Originalus pavadinimas',
        'active' => 'Aktyvi',
        'default' => 'Numatytoji kalba',
        'order' => 'Rikiavimo eilė',
        'created' => 'Sukurta',
    ],

    'placeholders' => [
        'code' => 'lt',
        'name' => 'Lithuanian',
        'native_name' => 'Lietuvių',
    ],

    'sections' => [
        'details' => 'Kalbos informacija',
        'settings' => 'Nustatymai',
    ],

    'helper_text' => [
        'details' => 'Konfigūruokite programos kalbos nustatymus',
        'code' => 'ISO 639-1 kalbos kodas (pvz., en, lt, ru)',
        'name' => 'Pavadinimas anglų kalba',
        'native_name' => 'Pavadinimas originalia kalba',
        'active' => 'Tik aktyvios kalbos prieinamos pasirinkime',
        'default' => 'Tik viena kalba gali būti numatytoji',
        'order' => 'Mažesni numeriai rodomi pirmiau kalbų sąrašuose',
    ],

    'filters' => [
        'active_placeholder' => 'Visos kalbos',
        'active_only' => 'Tik aktyvios',
        'inactive_only' => 'Tik neaktyvios',
        'default_placeholder' => 'Visos kalbos',
        'default_only' => 'Tik numatytosios',
        'non_default_only' => 'Tik ne numatytosios',
    ],

    'empty' => [
        'heading' => 'Kalbos nesukonfigūruotos',
        'description' => 'Pridėkite kalbas, kad įjungtumėte daugiakalbystę.',
        'action' => 'Pridėti pirmą kalbą',
    ],

    'modals' => [
        'delete' => [
            'heading' => 'Ištrinti kalbas',
            'description' => 'Ar tikrai norite ištrinti šias kalbas? Tai gali paveikti vertimus.',
        ],
    ],
    'validation' => [
        'locale' => [
            'required' => 'Kalba yra privaloma.',
            'string' => 'Kalbos kodas turi būti tekstas.',
            'max' => 'Kalbos kodas negali viršyti 5 simbolių.',
        ],
    ],
];
