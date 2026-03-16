<?php

return [
    'admin' => [
        'title' => 'Administratoriaus profilis',
        'org_title' => 'Organizacijos profilis',
        'org_description' => 'Tvarkykite organizacijos paskyrą, prenumeratą ir saugos nustatymus.',
        'profile_title' => 'Profilis',
        'profile_description' => 'Tvarkykite savo asmeninės paskyros duomenis.',
        'alerts' => [
            'errors' => 'Pataisykite paryškintus laukus ir bandykite dar kartą.',
            'expired_body' => 'Jūsų prenumerata baigėsi :date. Atnaujinkite, kad išlaikytumėte visą prieigą.',
            'expired_title' => 'Prenumerata pasibaigė',
            'expiring_body' => 'Jūsų prenumerata baigiasi :days (:date). Apsvarstykite galimybę netrukus atnaujinti.',
            'expiring_title' => 'Prenumerata greitai baigsis',
        ],
        'days' => '{1}:count diena|[2,*]:count d',
        'language_form' => [
            'description' => 'Pasirinkite pageidaujamą administratoriaus darbo srities kalbą.',
            'title' => 'Kalba',
        ],
        'password_form' => [
            'confirm' => 'Patvirtinkite naują slaptažodį',
            'current' => 'Dabartinis slaptažodis',
            'description' => 'Naudokite tvirtą slaptažodį, kad apsaugotumėte paskyrą.',
            'new' => 'Naujas slaptažodis',
            'submit' => 'Atnaujinti slaptažodį',
            'title' => 'Slaptažodis',
        ],
        'profile_form' => [
            'description' => 'Atnaujinkite savo pagrindinę kontaktinę informaciją, naudojamą platformoje.',
            'currency' => 'Valiuta',
            'email' => 'El. paštas',
            'name' => 'Vardas',
            'organization' => 'Organizacijos pavadinimas',
            'submit' => 'Išsaugoti pakeitimus',
            'title' => 'Profilio informacija',
        ],
        'subscription' => [
            'approaching_limit' => 'Artėjate prie savo plano ribos.',
            'card_title' => 'Prenumerata',
            'days_remaining' => '(liko :days)',
            'description' => 'Dabartinė plano būsena, galiojimo pabaigos informacija ir naudojimo apribojimai.',
            'expiry_date' => 'Galiojimo pabaigos data',
            'plan_type' => 'Planuoti',
            'properties' => 'Turtai',
            'start_date' => 'Pradžios data',
            'status' => 'Būsena',
            'tenants' => 'Nuomininkai',
            'usage_limits' => 'Naudojimo ribos',
        ],
    ],
    'superadmin' => [
        'title' => 'Superadmin profilis',
        'heading' => 'Superadmin profilis',
        'description' => 'Tvarkykite savo platformos lygio paskyros nustatymus ir kalbos nuostatas.',
        'actions' => [
            'update_profile' => 'Atnaujinti profilį',
        ],
        'alerts' => [
            'errors' => 'Pataisykite paryškintus laukus ir bandykite dar kartą.',
        ],
        'language_form' => [
            'description' => 'Pasirinkite norimą sąsajos kalbą superadmin darbo srityje.',
            'title' => 'Kalba',
        ],
        'profile_form' => [
            'description' => 'Atnaujinkite savo pagrindinės paskyros tapatybę ir pasirenkamą slaptažodį vienoje vietoje.',
            'currency' => 'Valiuta',
            'email' => 'El. paštas',
            'name' => 'Vardas',
            'password' => 'Naujas slaptažodis',
            'password_confirmation' => 'Patvirtinkite naują slaptažodį',
            'password_hint' => 'Palikite slaptažodžio laukus tuščius, jei nenorite jo keisti.',
            'title' => 'Išsami sąskaitos informacija',
        ],
    ],
];
