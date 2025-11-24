<?php

declare(strict_types=1);

return [
    'headings' => [
        'index' => 'Vartotojų valdymas',
        'create' => 'Kurti vartotoją',
        'edit' => 'Redaguoti vartotoją',
        'show' => 'Vartotojo informacija',
        'information' => 'Vartotojo duomenys',
        'quick_actions' => 'Greiti veiksmai',
    ],

    'descriptions' => [
        'index' => 'Tvarkykite vartotojų paskyras ir roles',
    ],

    'actions' => [
        'add' => 'Pridėti vartotoją',
        'create' => 'Sukurti vartotoją',
        'edit' => 'Redaguoti vartotoją',
        'update' => 'Atnaujinti vartotoją',
        'delete' => 'Ištrinti',
        'view' => 'Peržiūrėti',
        'back' => 'Grįžti',
        'filter' => 'Filtruoti',
        'clear' => 'Išvalyti',
    ],

    'tables' => [
        'name' => 'Vardas',
        'email' => 'El. paštas',
        'role' => 'Rolė',
        'tenant' => 'Nuomininkas',
        'actions' => 'Veiksmai',
    ],

    'labels' => [
        'user' => 'Vartotojas',
        'users' => 'Vartotojai',
        'name' => 'Vardas',
        'email' => 'El. paštas',
        'password' => 'Slaptažodis',
        'password_confirmation' => 'Patvirtinti slaptažodį',
        'role' => 'Rolė',
        'organization_name' => 'Organizacijos pavadinimas',
        'properties' => 'Objektai',
        'is_active' => 'Aktyvus',
        'created_at' => 'Sukurta',
        'updated_at' => 'Atnaujinta',
        'activity_history' => 'Veiklos istorija',
        'meter_readings_entered' => 'Įvesti rodmenys',
        'no_activity' => 'Nėra užfiksuotos veiklos.',
        'activity_hint' => 'Šis vartotojas įvedė :count rodmenis.',
    ],

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
            'unique' => 'Šis el. paštas jau užregistruotas.',
            'max' => 'El. paštas negali viršyti 255 simbolių.',
        ],
        'password' => [
            'required' => 'Slaptažodis yra privalomas.',
            'string' => 'Slaptažodis turi būti tekstas.',
            'min' => 'Slaptažodis turi būti ne trumpesnis kaip 8 simboliai.',
            'confirmed' => 'Slaptažodžio patvirtinimas nesutampa.',
        ],
        'password_confirmation' => [
            'required' => 'Reikia patvirtinti slaptažodį.',
        ],
        'role' => [
            'required' => 'Rolė yra privaloma.',
            'enum' => 'Pasirinkta rolė yra neteisinga.',
        ],
        'organization_name' => [
            'required' => 'Administratoriui būtina nurodyti organizacijos pavadinimą.',
            'string' => 'Organizacijos pavadinimas turi būti tekstas.',
            'max' => 'Organizacijos pavadinimas negali viršyti 255 simbolių.',
        ],
        'properties' => [
            'required' => 'Nuomininkui būtina priskirti objektą.',
            'exists' => 'Pasirinktas objektas neegzistuoja.',
        ],
        'is_active' => [
            'boolean' => 'Paskyros būsena turi būti aktyvi arba neaktyvi.',
        ],
        'tenant_id' => [
            'required' => 'Nuomininkas yra privalomas.',
            'integer' => 'Nuomininko identifikatorius turi būti skaičius.',
            'exists' => 'Pasirinktas nuomininkas neegzistuoja.',
        ],
        'current_password' => [
            'required' => 'Dabartinis slaptažodis yra privalomas.',
            'required_with' => 'Keičiant slaptažodį būtina įvesti dabartinį slaptažodį.',
            'string' => 'Dabartinis slaptažodis turi būti tekstas.',
            'current_password' => 'Dabartinis slaptažodis neteisingas.',
        ],
    ],

    'errors' => [
        'has_readings' => 'Negalima ištrinti vartotojo, turinčio susietų rodmenų.',
    ],

    'empty' => [
        'users' => 'Vartotojų nerasta.',
    ],
];
