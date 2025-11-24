<?php

declare(strict_types=1);

return [
    'labels' => [
        'provider' => 'Tiekėjas',
        'providers' => 'Tiekėjai',
        'name' => 'Tiekėjo pavadinimas',
        'service_type' => 'Paslaugos tipas',
        'contact_info' => 'Kontaktinė informacija',
        'tariffs' => 'Tarifai',
        'created' => 'Sukurta',
    ],

    'headings' => [
        'index' => 'Tiekėjų valdymas',
        'create' => 'Kurti tiekėją',
        'edit' => 'Redaguoti tiekėją',
        'show' => 'Tiekėjo informacija',
        'information' => 'Tiekėjo duomenys',
        'associated_tariffs' => 'Susiję tarifai',
        'quick_actions' => 'Greiti veiksmai',
    ],

    'descriptions' => [
        'index' => 'Tvarkykite komunalinių paslaugų tiekėjus',
        'create' => 'Pridėkite naują komunalinių paslaugų tiekėją',
        'edit' => 'Atnaujinkite tiekėjo informaciją',
        'show' => 'Peržiūrėkite tiekėjo informaciją ir susijusius tarifus',
    ],

    'actions' => [
        'add' => 'Pridėti tiekėją',
        'create' => 'Sukurti tiekėją',
        'edit' => 'Redaguoti tiekėją',
        'update' => 'Atnaujinti tiekėją',
        'delete' => 'Pašalinti tiekėją',
        'view' => 'Peržiūrėti',
        'back' => 'Grįžti į sąrašą',
        'cancel' => 'Atšaukti',
        'add_tariff' => 'Pridėti tarifą',
    ],

    'tables' => [
        'name' => 'Pavadinimas',
        'service_type' => 'Paslaugos tipas',
        'tariffs' => 'Tarifai',
        'contact_info' => 'Kontaktai',
        'actions' => 'Veiksmai',
        'active_from' => 'Galioja nuo',
        'active_until' => 'Galioja iki',
        'status' => 'Būsena',
    ],

    'statuses' => [
        'active' => 'Aktyvus',
        'inactive' => 'Neaktyvus',
        'present' => 'Dabar',
        'not_available' => 'N/D',
    ],

    'counts' => [
        'tariffs' => '{0} Tarifų nėra|{1} :count tarifas|[2,*] :count tarifai',
    ],

    'empty' => [
        'providers' => 'Tiekėjų nerasta.',
        'tariffs' => 'Nėra su šiuo tiekėju susietų tarifų.',
    ],

    'notifications' => [
        'created' => 'Tiekėjas sėkmingai sukurtas.',
        'updated' => 'Tiekėjo duomenys sėkmingai atnaujinti.',
        'deleted' => 'Tiekėjas sėkmingai pašalintas.',
        'cannot_delete' => 'Negalima pašalinti tiekėjo, turinčio susietų tarifų.',
    ],

    'confirmations' => [
        'delete' => 'Ar tikrai norite ištrinti šį tiekėją?',
    ],

    'validation' => [
        'name' => [
            'required' => 'Tiekėjo pavadinimas yra privalomas.',
            'string' => 'Tiekėjo pavadinimas turi būti tekstas.',
            'max' => 'Tiekėjo pavadinimas negali viršyti 255 simbolių.',
        ],
        'service_type' => [
            'required' => 'Paslaugos tipas yra privalomas.',
            'in' => 'Paslaugos tipas turi būti elektra, vanduo arba šildymas.',
        ],
        'contact_info' => [
            'string' => 'Kontaktinė informacija turi būti tekstas.',
        ],
    ],
];
