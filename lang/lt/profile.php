<?php

declare(strict_types=1);

return [
    'admin' => [
        'title' => 'Administratoriaus profilis',
        'org_title' => 'Organizacijos profilis',
        'org_description' => 'Valdykite organizacijos paskyrą, prenumeratą ir saugos nustatymus.',
        'profile_title' => 'Profilis',
        'profile_description' => 'Valdykite savo asmeninius paskyros duomenis.',
        'alerts' => [
            'errors' => 'Pataisykite pažymėtus laukus ir bandykite dar kartą.',
            'expired_body' => 'Jūsų prenumerata baigėsi :date. Atnaujinkite ją, kad išlaikytumėte pilną prieigą.',
            'expired_title' => 'Prenumerata pasibaigė',
            'expiring_body' => 'Jūsų prenumerata baigsis po :days (data: :date). Rekomenduojame atnaujinti iš anksto.',
            'expiring_title' => 'Prenumerata netrukus baigsis',
        ],
        'days' => '{1}:count diena|[2,*]:count dienos',
        'language_form' => [
            'description' => 'Pasirinkite pageidaujamą administravimo erdvės kalbą.',
            'title' => 'Kalba',
        ],
        'password_form' => [
            'confirm' => 'Pakartokite naują slaptažodį',
            'current' => 'Dabartinis slaptažodis',
            'description' => 'Naudokite stiprų slaptažodį paskyros saugumui užtikrinti.',
            'new' => 'Naujas slaptažodis',
            'submit' => 'Atnaujinti slaptažodį',
            'title' => 'Slaptažodis',
        ],
        'profile_form' => [
            'description' => 'Atnaujinkite pagrindinius kontaktinius duomenis platformoje.',
            'currency' => 'Valiuta',
            'email' => 'El. paštas',
            'name' => 'Vardas',
            'organization' => 'Organizacijos pavadinimas',
            'submit' => 'Išsaugoti pakeitimus',
            'title' => 'Profilio duomenys',
        ],
        'subscription' => [
            'approaching_limit' => 'Artėjate prie plano limito.',
            'card_title' => 'Prenumerata',
            'days_remaining' => '(liko :days)',
            'description' => 'Dabartinio plano būsena, galiojimo data ir naudojimo limitai.',
            'expiry_date' => 'Galiojimo pabaiga',
            'plan_type' => 'Planas',
            'properties' => 'Objektai',
            'start_date' => 'Pradžios data',
            'status' => 'Būsena',
            'tenants' => 'Nuomininkai',
            'usage_limits' => 'Naudojimo limitai',
        ],
    ],
    'superadmin' => [
        'title' => 'Superadministratoriaus profilis',
        'heading' => 'Superadministratoriaus profilis',
        'description' => 'Valdykite platformos lygio paskyros nustatymus ir kalbos parinktis.',
        'actions' => [
            'update_profile' => 'Atnaujinti profilį',
        ],
        'alerts' => [
            'errors' => 'Pataisykite pažymėtus laukus ir bandykite dar kartą.',
        ],
        'language_form' => [
            'description' => 'Pasirinkite pageidaujamą superadministratoriaus erdvės kalbą.',
            'title' => 'Kalba',
        ],
        'profile_form' => [
            'description' => 'Vienoje vietoje atnaujinkite pagrindinius paskyros duomenis ir, jei reikia, slaptažodį.',
            'currency' => 'Valiuta',
            'email' => 'El. paštas',
            'name' => 'Vardas',
            'password' => 'Naujas slaptažodis',
            'password_confirmation' => 'Pakartokite naują slaptažodį',
            'password_hint' => 'Jei slaptažodžio keisti nenorite, palikite laukus tuščius.',
            'title' => 'Paskyros duomenys',
        ],
    ],
];
