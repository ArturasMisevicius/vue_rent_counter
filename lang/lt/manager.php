<?php

declare(strict_types=1);

return [
    'pages' => [
        'index' => [
            'title' => 'Vadybininkai',
            'subtitle' => 'Visi vadybininkai visose organizacijose',
        ],
    ],
    'fields' => [
        'id' => 'ID',
        'name' => 'Vardas',
        'email' => 'El. paštas',
        'properties' => 'Objektai',
        'buildings' => 'Pastatai',
        'invoices' => 'Sąskaitos',
        'actions' => 'Veiksmai',
    ],
    'profile' => [
        'title' => 'Vadybininko profilis',
        'description' => 'Atnaujinkite savo paskyros duomenis ir peržiūrėkite portfelio suvestinę.',
        'alerts' => [
            'errors' => 'Pataisykite pažymėtus laukus ir bandykite dar kartą.',
        ],
        'account_information' => 'Paskyros informacija',
        'account_information_description' => 'Tvarkykite savo vardą, el. paštą ir prisijungimo slaptažodį.',
        'labels' => [
            'currency' => 'Valiuta',
            'email' => 'El. paštas',
            'language' => 'Kalba',
            'name' => 'Vardas',
        ],
        'language_description' => 'Pasirinkite pageidaujamą sąsajos kalbą kasdieniam darbui.',
        'language_hint' => 'Kalbos pakeitimas pritaikomas iš karto po pasirinkimo.',
        'language_preference' => 'Kalbos nuostatos',
        'password' => [
            'confirmation' => 'Pakartokite slaptažodį',
            'hint' => 'Jei slaptažodžio keisti nenorite, palikite laukus tuščius.',
            'label' => 'Naujas slaptažodis',
        ],
        'portfolio' => [
            'title' => 'Portfelio suvestinė',
            'description' => 'Greita jums priskirtų objektų ir veiklų apžvalga.',
        ],
        'update_profile' => 'Atnaujinti profilį',
    ],
];
