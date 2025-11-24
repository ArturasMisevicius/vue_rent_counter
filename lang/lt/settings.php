<?php

declare(strict_types=1);

return [
    'validation' => [
        'app_name' => [
            'string' => 'Programos pavadinimas turi būti tekstas.',
            'max' => 'Programos pavadinimas negali viršyti 255 simbolių.',
        ],
        'timezone' => [
            'string' => 'Laiko juosta turi būti tekstas.',
            'in' => 'Laiko juosta turi būti Europe/Vilnius arba UTC.',
        ],
    ],
];
