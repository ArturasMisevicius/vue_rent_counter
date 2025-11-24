<?php

declare(strict_types=1);

return [
    'validation' => [
        'plan_type' => [
            'required' => 'Plano tipas yra privalomas.',
            'in' => 'Plano tipas turi būti basic, professional arba enterprise.',
        ],
        'status' => [
            'required' => 'Būsena yra privaloma.',
            'in' => 'Būsena turi būti active, expired, suspended arba cancelled.',
        ],
        'expires_at' => [
            'required' => 'Galiojimo data yra privaloma.',
            'date' => 'Galiojimo data turi būti tinkama data.',
            'after' => 'Galiojimo data turi būti po šiandienos.',
        ],
        'max_properties' => [
            'required' => 'Maksimalus objektų skaičius yra privalomas.',
            'integer' => 'Maksimalus objektų skaičius turi būti skaičius.',
            'min' => 'Maksimalus objektų skaičius turi būti ne mažesnis kaip 1.',
        ],
        'max_tenants' => [
            'required' => 'Maksimalus nuomininkų skaičius yra privalomas.',
            'integer' => 'Maksimalus nuomininkų skaičius turi būti skaičius.',
            'min' => 'Maksimalus nuomininkų skaičius turi būti ne mažesnis kaip 1.',
        ],
        'reason' => [
            'required' => 'Priežastis yra privaloma.',
            'string' => 'Priežastis turi būti tekstas.',
            'max' => 'Priežastis negali viršyti 500 simbolių.',
        ],
    ],
];
