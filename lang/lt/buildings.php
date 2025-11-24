<?php

declare(strict_types=1);

return [
    'labels' => [
        'building' => 'Pastatas',
        'buildings' => 'Pastatai',
        'address' => 'Adresas',
        'total_apartments' => 'Bendras butų skaičius',
        'total_area' => 'Bendras plotas',
    ],

    'validation' => [
        'tenant_id' => [
            'required' => 'Nuomininkas yra privalomas.',
            'integer' => 'Nuomininko identifikatorius turi būti skaičius.',
        ],
        'address' => [
            'required' => 'Pastato adresas yra privalomas.',
            'string' => 'Pastato adresas turi būti tekstas.',
            'max' => 'Pastato adresas negali būti ilgesnis nei 255 simboliai.',
        ],
        'total_apartments' => [
            'required' => 'Būtina nurodyti butų skaičių.',
            'numeric' => 'Butų skaičius turi būti sveikas skaičius.',
            'integer' => 'Butų skaičius turi būti sveikas skaičius.',
            'min' => 'Pastatas turi turėti bent 1 butą.',
            'max' => 'Pastatas negali turėti daugiau nei 1 000 butų.',
        ],
        'total_area' => [
            'required' => 'Bendras plotas yra privalomas.',
            'numeric' => 'Bendras plotas turi būti skaičius.',
            'min' => 'Bendras plotas turi būti ne mažesnis kaip 0.',
        ],
        'gyvatukas' => [
            'start_date' => [
                'required' => 'Pradžios data reikalinga gyvatukas skaičiavimui.',
                'date' => 'Pradžios data turi būti tinkama data.',
            ],
            'end_date' => [
                'required' => 'Pabaigos data reikalinga gyvatukas skaičiavimui.',
                'date' => 'Pabaigos data turi būti tinkama data.',
                'after' => 'Pabaigos data turi būti po pradžios datos.',
            ],
        ],
    ],

    'errors' => [
        'has_properties' => 'Negalima ištrinti pastato, turinčio susietų objektų.',
    ],
];
