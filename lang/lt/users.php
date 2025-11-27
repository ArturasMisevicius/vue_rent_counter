<?php

declare(strict_types=1);

return [
    'validation' => [
        'name' => [
            'required' => 'Vardas yra privalomas',
            'string' => 'Vardas turi būti tekstas',
            'max' => 'Vardas negali viršyti 255 simbolių',
        ],
        'email' => [
            'required' => 'El. paštas yra privalomas',
            'email' => 'Įveskite teisingą el. pašto adresą',
            'unique' => 'Šis el. paštas jau naudojamas',
        ],
        'current_password' => [
            'required_with' => 'Dabartinis slaptažodis reikalingas keičiant slaptažodį',
            'string' => 'Dabartinis slaptažodis turi būti tekstas',
            'current_password' => 'Dabartinis slaptažodis neteisingas',
        ],
        'password' => [
            'string' => 'Slaptažodis turi būti tekstas',
            'min' => 'Slaptažodis turi būti bent 8 simbolių',
            'confirmed' => 'Slaptažodžio patvirtinimas nesutampa',
        ],
    ],
];
