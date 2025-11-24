<?php

declare(strict_types=1);

return [
    'admin' => [
        'title' => 'Organizacijos profilis',
        'breadcrumb' => 'Profilis',
        'org_title' => 'Organizacijos profilis',
        'profile_title' => 'Profilis',
        'org_description' => 'Tvarkykite savo organizacijos profilį ir prenumeratą',
        'profile_description' => 'Tvarkykite savo profilio informaciją',
        'alerts' => [
            'errors' => 'Pateiktoje formoje yra klaidų',
            'expired_title' => 'Prenumerata nebegalioja',
            'expired_body' => 'Jūsų prenumerata baigėsi :date. Susisiekite su pagalba, kad ją atnaujintumėte.',
            'expiring_title' => 'Prenumerata netrukus baigsis',
            'expiring_body' => 'Prenumerata baigsis po :days, :date. Susisiekite su pagalba, kad ją atnaujintumėte.',
        ],
        'subscription' => [
            'card_title' => 'Prenumeratos duomenys',
            'plan_type' => 'Plano tipas',
            'status' => 'Būsena',
            'start_date' => 'Pradžios data',
            'expiry_date' => 'Pabaigos data',
            'days_remaining' => '(:days liko)',
            'usage_limits' => 'Naudojimo limitai',
            'properties' => 'Objektai',
            'tenants' => 'Nuomininkai',
            'approaching_limit' => 'Artėjama prie limito – apsvarstykite plano atnaujinimą',
        ],
        'profile_form' => [
            'title' => 'Profilio informacija',
            'name' => 'Vardas ir pavardė',
            'email' => 'El. paštas',
            'organization' => 'Organizacijos pavadinimas',
            'submit' => 'Atnaujinti profilį',
        ],
        'password_form' => [
            'title' => 'Keisti slaptažodį',
            'current' => 'Dabartinis slaptažodis',
            'new' => 'Naujas slaptažodis',
            'confirm' => 'Pakartokite naują slaptažodį',
            'submit' => 'Atnaujinti slaptažodį',
        ],
        'days' => '{1}:count diena|{2}:count dienos|{3}:count dienos|[4,9]:count dienos|[10,*]:count dienų',
    ],
    'superadmin' => [
        'title' => 'Super administratoriaus profilis',
        'heading' => 'Profilis ir prisijungimo duomenys',
        'description' => 'Tvarkykite savo profilio informaciją ir prisijungimo duomenis.',
        'alerts' => [
            'errors' => 'Pateiktoje formoje yra klaidų',
        ],
        'profile_form' => [
            'title' => 'Profilio informacija',
            'name' => 'Vardas ir pavardė',
            'email' => 'El. pašto adresas',
            'password' => 'Naujas slaptažodis',
            'password_confirmation' => 'Patvirtinkite slaptažodį',
            'password_hint' => 'Palikite tuščią, jei nenorite keisti slaptažodžio.',
        ],
        'actions' => [
            'update_profile' => 'Išsaugoti pakeitimus',
        ],
    ],
];
