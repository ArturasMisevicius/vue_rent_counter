<?php

declare(strict_types=1);

return [
    'headings' => [
        'index' => 'Nuomininkų paskyros',
        'index_description' => 'Tvarkykite nuomininkų paskyras ir objektų priskyrimus',
        'list' => 'Nuomininkų sąrašas',
        'show' => 'Nuomininko detalės',
        'account' => 'Paskyros informacija',
        'current_property' => 'Dabartinis objektas',
        'assignment_history' => 'Priskyrimų istorija',
        'recent_readings' => 'Naujausi rodmenys',
        'recent_invoices' => 'Naujausios sąskaitos',
    ],

    'actions' => [
        'deactivate' => 'Deaktyvuoti',
        'reactivate' => 'Aktyvuoti',
        'reassign' => 'Perkelti į kitą objektą',
        'add' => 'Pridėti nuomininką',
        'view' => 'Peržiūrėti',
    ],

    'labels' => [
        'name' => 'Vardas',
        'status' => 'Būsena',
        'email' => 'El. paštas',
        'created' => 'Sukurta',
        'created_by' => 'Sukūrė',
        'address' => 'Adresas',
        'type' => 'Tipas',
        'area' => 'Plotas',
        'reading' => 'Rodmuo',
        'invoice' => 'Sąskaita #:id',
        'reason' => 'Priežastis',
        'property' => 'Objektas',
        'actions' => 'Veiksmai',
    ],

    'statuses' => [
        'active' => 'Aktyvus',
        'inactive' => 'Neaktyvus',
    ],

    'empty' => [
        'property' => 'Objektas nepriskirtas',
        'assignment_history' => 'Nėra priskyrimų istorijos',
        'recent_readings' => 'Naujų rodmenų nėra',
        'recent_invoices' => 'Naujų sąskaitų nėra',
        'list' => 'Nuomininkų nerasta.',
        'list_cta' => 'Sukurkite pirmą nuomininką',
    ],

    'pages' => [
        'admin_form' => [
            'title' => 'Sukurti nuomininko paskyrą',
            'subtitle' => 'Pridėkite naują nuomininką ir priskirkite objektą',
            'breadcrumb' => 'Sukurti',
            'errors_title' => 'Yra klaidų jūsų pateiktyje',
            'labels' => [
                'name' => 'Vardas ir pavardė',
                'email' => 'El. pašto adresas',
                'password' => 'Slaptažodis',
                'password_confirmation' => 'Patvirtinti slaptažodį',
                'property' => 'Priskirti objektą',
            ],
            'placeholders' => [
                'property' => 'Pasirinkite objektą',
            ],
            'notes' => [
                'credentials_sent' => 'Prisijungimo duomenys bus išsiųsti šiuo el. paštu',
                'no_properties' => 'Nėra turimų objektų. Pirmiausia sukurkite objektą.',
            ],
            'actions' => [
                'cancel' => 'Atšaukti',
                'submit' => 'Sukurti nuomininką',
            ],
        ],
        'reassign' => [
            'title' => 'Perkelti nuomininką į kitą objektą',
            'subtitle' => 'Perkelkite :name į kitą savo portfelio objektą',
            'breadcrumb' => 'Perkelti',
            'errors_title' => 'Yra klaidų jūsų pateiktyje',
            'current_property' => [
                'title' => 'Dabartinis objektas',
                'empty' => 'Šiuo metu nepriskirtas joks objektas',
            ],
            'new_property' => [
                'label' => 'Naujas objektas',
                'placeholder' => 'Pasirinkite objektą',
                'empty' => 'Nėra kitų objektų perkėlimui.',
                'note' => 'Pasirinkite objektą, į kurį perkelsite šį nuomininką',
            ],
            'warning' => [
                'title' => 'Svarbi informacija',
                'items' => [
                    'preserved' => 'Visi ankstesni skaitiklių rodmenys ir sąskaitos bus išsaugoti',
                    'notify' => 'Nuomininkui apie perkėlimą bus pranešta el. paštu',
                    'audit' => 'Šis veiksmas bus įrašytas į audito žurnalą',
                ],
            ],
            'history' => [
                'title' => 'Perkėlimų istorija',
                'empty' => 'Ankstesni priskyrimai bus rodomi čia po perkėlimo',
            ],
            'actions' => [
                'cancel' => 'Atšaukti',
                'submit' => 'Perkelti nuomininką',
            ],
        ],
    ],
];
