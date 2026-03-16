<?php

return [
    'actions' => [
        'add' => 'Pridėti',
        'back' => 'Atgal',
        'clear' => 'Aišku',
        'create' => 'Sukurti',
        'delete' => 'Ištrinti',
        'edit' => 'Redaguoti',
        'filter' => 'Filtruoti',
        'update' => 'Atnaujinti',
        'view' => 'Žiūrėti',
    ],
    'descriptions' => [
        'index' => 'Rodyklė',
    ],
    'empty' => [
        'users' => 'Vartotojai',
    ],
    'empty_state' => [
        'description' => 'Aprašymas',
        'heading' => 'Antraštė',
    ],
    'errors' => [
        'has_readings' => 'Has Readings',
    ],
    'filters' => [
        'active_only' => 'Tik aktyvus',
        'all_users' => 'Visi vartotojai',
        'inactive_only' => 'Tik neaktyvus',
        'is_active' => 'Yra Aktyvus',
        'role' => 'Vaidmuo',
    ],
    'headings' => [
        'create' => 'Sukurti',
        'edit' => 'Redaguoti',
        'index' => 'Rodyklė',
        'information' => 'Informacija',
        'quick_actions' => 'Greiti veiksmai',
        'show' => 'Rodyti',
    ],
    'helper_text' => [
        'is_active' => 'Yra Aktyvus',
        'password' => 'Slaptažodis',
        'role' => 'Vaidmuo',
        'tenant' => 'Nuomininkas',
    ],
    'labels' => [
        'activity_hint' => 'Veiklos patarimas',
        'activity_history' => 'Veiklos istorija',
        'created' => 'Sukurta',
        'created_at' => 'Sukurta',
        'email' => 'El. paštas',
        'is_active' => 'Yra Aktyvus',
        'last_login_at' => 'Paskutinis prisijungimas At',
        'meter_readings_entered' => 'Įvesti skaitiklio rodmenys',
        'name' => 'Vardas',
        'no_activity' => 'Nėra veiklos',
        'password' => 'Slaptažodis',
        'password_confirmation' => 'Slaptažodžio patvirtinimas',
        'role' => 'Vaidmuo',
        'tenant' => 'Nuomininkas',
        'updated_at' => 'Atnaujinta',
        'user' => 'Vartotojas',
        'users' => 'Vartotojai',
    ],
    'placeholders' => [
        'email' => 'El. paštas',
        'name' => 'Vardas',
        'password' => 'Slaptažodis',
        'password_confirmation' => 'Slaptažodžio patvirtinimas',
    ],
    'sections' => [
        'role_and_access' => 'Vaidmuo ir prieiga',
        'role_and_access_description' => 'Vaidmuo ir prieigos aprašymas',
        'user_details' => 'Vartotojo informacija',
        'user_details_description' => 'Informacija apie naudotoją Aprašymas',
    ],
    'tables' => [
        'actions' => 'Veiksmai',
        'email' => 'El. paštas',
        'name' => 'Vardas',
        'role' => 'Vaidmuo',
        'tenant' => 'Nuomininkas',
    ],
    'tooltips' => [
        'copy_email' => 'Kopijuoti el',
    ],
    'validation' => [
        'current_password' => [
            'current_password' => 'Dabartinis slaptažodis',
            'required' => 'Reikalingas',
            'required_with' => 'Reikalingas Su',
            'string' => 'Styga',
        ],
        'email' => [
            'email' => 'El. paštas',
            'max' => 'Maks',
            'required' => 'Reikalingas',
            'string' => 'Styga',
            'unique' => 'Unikali',
        ],
        'name' => [
            'max' => 'Maks',
            'required' => 'Reikalingas',
            'string' => 'Styga',
        ],
        'organization_name' => [
            'max' => 'Maks',
            'string' => 'Styga',
        ],
        'password' => [
            'confirmed' => 'Patvirtinta',
            'min' => 'Min',
            'required' => 'Reikalingas',
            'string' => 'Styga',
        ],
        'role' => [
            'enum' => 'Enum',
            'required' => 'Reikalingas',
        ],
        'tenant_id' => [
            'exists' => 'Egzistuoja',
            'integer' => 'Sveikasis skaičius',
            'required' => 'Reikalingas',
        ],
    ],
];
