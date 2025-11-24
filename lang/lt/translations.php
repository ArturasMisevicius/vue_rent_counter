<?php

declare(strict_types=1);

return [
    'navigation' => 'Vertimai',
    'sections' => [
        'key' => 'Vertimo raktas',
        'values' => 'Vertimų reikšmės',
    ],
    'labels' => [
        'group' => 'Grupė',
        'key' => 'Raktas',
        'value' => 'Reikšmė',
        'last_updated' => 'Paskutinį kartą atnaujinta',
    ],
    'placeholders' => [
        'group' => 'app',
        'key' => 'nav.dashboard',
        'value' => '—',
    ],
    'helper_text' => [
        'key' => 'Nurodykite šio vertimo grupę ir raktą',
        'group' => 'PHP failo pavadinimas lang/{locale}/ kataloge (pvz., „app“ reiškia app.php)',
        'key_full' => 'Vertimo raktas su taškų notacija (pvz., „nav.dashboard“)',
        'values' => 'Pateikite vertimus kiekvienai aktyviai kalbai. Reikšmės rašomos į PHP kalbos failus.',
        'default_language' => 'Numatytoji kalba',
    ],
    'empty' => [
        'heading' => 'Vertimų nėra',
        'description' => 'Sukurkite vertimus, kad valdytumėte daugiakalbį turinį.',
        'action' => 'Pridėti pirmą vertimą',
    ],
    'modals' => [
        'delete' => [
            'heading' => 'Ištrinti vertimus',
            'description' => 'Ar tikrai norite ištrinti šiuos vertimus? Tai paveiks programos sąsają.',
        ],
    ],
    'table' => [
        'value_label' => ':locale reikšmė',
        'language_label' => ':language (:code)',
    ],
];
