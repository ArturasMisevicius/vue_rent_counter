<?php

declare(strict_types=1);

return [
    'validation' => [
        'name' => [
            'required' => 'Vardas yra privalomas.',
            'string' => 'Vardas turi būti tekstas.',
            'max' => 'Vardas negali viršyti 255 simbolių.',
        ],
        'email' => [
            'required' => 'El. paštas yra privalomas.',
            'string' => 'El. paštas turi būti tekstas.',
            'email' => 'El. paštas turi būti galiojantis adresas.',
            'max' => 'El. paštas negali viršyti 255 simbolių.',
            'unique' => 'Šis el. paštas jau naudojamas.',
        ],
        'password' => [
            'required' => 'Slaptažodis yra privalomas.',
            'string' => 'Slaptažodis turi būti tekstas.',
            'min' => 'Slaptažodis turi būti ne trumpesnis kaip 8 simboliai.',
        ],
        'organization_name' => [
            'required' => 'Organizacijos pavadinimas yra privalomas.',
            'string' => 'Organizacijos pavadinimas turi būti tekstas.',
            'max' => 'Organizacijos pavadinimas negali viršyti 255 simbolių.',
        ],
        'plan_type' => [
            'required' => 'Plano tipas yra privalomas.',
            'in' => 'Plano tipas turi būti basic, professional arba enterprise.',
        ],
        'expires_at' => [
            'required' => 'Galiojimo data yra privaloma.',
            'date' => 'Galiojimo data turi būti tinkama data.',
            'after' => 'Galiojimo data turi būti po šiandienos.',
        ],
        'is_active' => [
            'boolean' => 'Aktyvumo būsena turi būti tiesa arba netiesa.',
        ],
    ],
];
