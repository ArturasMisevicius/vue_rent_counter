<?php

declare(strict_types=1);

return [
    'validation' => [
        'report_type' => [
            'required' => 'Ataskaitos tipas yra privalomas.',
            'in' => 'Ataskaitos tipas turi būti consumption, revenue, outstanding arba meter-readings.',
        ],
        'format' => [
            'required' => 'Eksporto formatas yra privalomas.',
            'in' => 'Eksporto formatas turi būti csv, excel arba pdf.',
        ],
        'start_date' => [
            'date' => 'Pradžios data turi būti tinkama data.',
        ],
        'end_date' => [
            'date' => 'Pabaigos data turi būti tinkama data.',
            'after_or_equal' => 'Pabaigos data turi būti ne ankstesnė nei pradžios data.',
        ],
        'property_id' => [
            'exists' => 'Pasirinktas objektas neegzistuoja.',
        ],
        'month' => [
            'date_format' => 'Mėnuo turi būti YYYY-MM formatu.',
        ],
    ],
];
