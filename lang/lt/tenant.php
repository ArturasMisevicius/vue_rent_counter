<?php

declare(strict_types=1);

return [
    'profile' => [
        'title' => 'Mano profilis',
        'description' => 'Peržiūrėkite savo paskyros informaciją ir nustatymus',
        'account_information' => 'Paskyros informacija',
        'language_preference' => 'Kalbos pasirinkimas',
        'labels' => [
            'name' => 'Vardas',
            'email' => 'El. paštas',
            'role' => 'Rolė',
            'created' => 'Paskyra sukurta',
            'address' => 'Adresas',
            'type' => 'Objekto tipas',
            'area' => 'Plotas',
            'building' => 'Pastatas',
            'organization' => 'Organizacija',
            'contact_name' => 'Kontaktinis asmuo',
        ],
        'language' => [
            'select' => 'Pasirinkite kalbą',
            'note' => 'Jūsų pasirinkta kalba bus išsaugota automatiškai',
        ],
        'assigned_property' => 'Priskirtas objektas',
        'manager_contact' => [
            'title' => 'Objekto vadybininko kontaktai',
            'description' => 'Jei turite klausimų ar reikia pagalbos, susisiekite su objekto vadybininku.',
        ],
    ],

    'property' => [
        'title' => 'Mano objektas',
        'description' => 'Jums priskirto objekto ir jo skaitiklių informacija.',
        'no_property_title' => 'Objektas nepriskirtas',
        'no_property_body' => 'Jums dar nepriskirtas objektas. Susisiekite su administratoriumi.',
        'info_title' => 'Objekto informacija',
        'labels' => [
            'address' => 'Adresas',
            'type' => 'Objekto tipas',
            'area' => 'Plotas',
            'building' => 'Pastatas',
            'building_address' => 'Pastato adresas',
            'serial' => 'Serija:',
        ],
        'meters_title' => 'Komunaliniai skaitikliai',
        'meters_description' => 'Šiam objektui įdiegti skaitikliai.',
        'meter_status' => 'Aktyvus',
        'view_details' => 'Peržiūrėti detales',
        'no_meters' => 'Šiam objektui dar neįdiegta skaitiklių.',
    ],

    'meters' => [
        'index_title' => 'Mano skaitikliai',
        'index_description' => 'Skaitikliai, priskirti jūsų objektui',
        'empty_title' => 'Skaitiklių nėra',
        'empty_body' => 'Jūsų objektui nepriskirta jokių skaitiklių. Jei manote, kad tai klaida, susisiekite su vadybininku.',
        'list_title' => 'Skaitikliai',
        'list_description' => 'Pasirinkite skaitiklį norėdami matyti istoriją.',
        'labels' => [
            'type' => 'Tipas',
            'serial' => 'Serija',
            'latest' => 'Naujausias',
            'updated' => 'Atnaujinta',
            'not_recorded' => 'Nefiksuota',
            'day_night' => 'Dieninis ir naktinis',
            'single_zone' => 'Vienos zonos',
        ],
        'status_active' => 'Aktyvus',
        'view_history' => 'Peržiūrėti istoriją',
        'all_readings' => 'Visi rodmenys',
        'back' => 'Atgal į mano skaitiklius',
        'show_title' => 'Skaitiklis :serial',
        'show_description' => 'Stebėkite :type naudojimą objektui :property',
        'view_all_readings' => 'Peržiūrėti visus rodmenis',
        'overview' => [
            'title' => 'Skaitiklių būklė',
            'active' => 'Aktyvūs skaitikliai',
            'zones' => 'Dieninis/naktinis režimas',
            'zones_hint' => 'Skaitikliai, palaikantys dieninį ir naktinį tarifą.',
            'latest_update' => 'Naujausias atnaujinimas',
            'no_readings' => 'Laukiama pirmųjų rodmenų',
            'recency_hint' => 'Vėliausia užfiksuotų rodmenų data visuose skaitikliuose.',
        ],
    ],
];
