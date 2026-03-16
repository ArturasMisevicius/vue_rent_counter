<?php

return [
    'actions' => [
        'add' => 'Pridėti',
        'deactivate' => 'Išjungti',
        'reactivate' => 'Iš naujo suaktyvinkite',
        'reassign' => 'Perskirti',
        'view' => 'Žiūrėti',
    ],
    'empty' => [
        'assignment_history' => 'Užduočių istorija',
        'list' => 'Sąrašas',
        'list_cta' => 'List Cta',
        'property' => 'Turtas',
        'recent_invoices' => 'Naujausios sąskaitos faktūros',
        'recent_readings' => 'Naujausi skaitiniai',
    ],
    'headings' => [
        'account' => 'sąskaita',
        'assignment_history' => 'Užduočių istorija',
        'current_property' => 'Dabartinis turtas',
        'index' => 'Rodyklė',
        'index_description' => 'Rodyklės aprašymas',
        'list' => 'Sąrašas',
        'recent_invoices' => 'Naujausios sąskaitos faktūros',
        'recent_readings' => 'Naujausi skaitiniai',
        'show' => 'Rodyti',
    ],
    'labels' => [
        'actions' => 'Veiksmai',
        'address' => 'Adresas',
        'area' => 'Plotas',
        'created' => 'Sukurta',
        'created_by' => 'Sukūrė',
        'email' => 'El. paštas',
        'invoice' => 'Sąskaita faktūra',
        'name' => 'Vardas',
        'property' => 'Turtas',
        'reading' => 'Skaitymas',
        'reason' => 'Priežastis',
        'status' => 'Būsena',
        'type' => 'Tipas',
    ],
    'pages' => [
        'admin_form' => [
            'actions' => [
                'cancel' => 'Atšaukti',
                'submit' => 'Pateikti',
            ],
            'errors_title' => 'Klaidos pavadinimas',
            'labels' => [
                'email' => 'El. paštas',
                'name' => 'Vardas',
                'password' => 'Slaptažodis',
                'password_confirmation' => 'Slaptažodžio patvirtinimas',
                'property' => 'Turtas',
            ],
            'notes' => [
                'credentials_sent' => 'Kredencialai išsiųsti',
                'no_properties' => 'Nėra savybių',
            ],
            'placeholders' => [
                'property' => 'Pasirinkite objektą',
            ],
            'subtitle' => 'Sukurkite nuomininko paskyrą ir priskirkite ją jūsų portfelio objektui.',
            'title' => 'Sukurti nuomininką',
        ],
        'reassign' => [
            'actions' => [
                'cancel' => 'Atšaukti',
                'submit' => 'Pateikti',
            ],
            'current_property' => [
                'empty' => 'Tuščia',
                'title' => 'Pavadinimas',
            ],
            'errors_title' => 'Klaidos pavadinimas',
            'history' => [
                'empty' => 'Tuščia',
                'title' => 'Pavadinimas',
            ],
            'new_property' => [
                'empty' => 'Tuščia',
                'label' => 'Etiketė',
                'note' => 'Pastaba',
                'placeholder' => 'Vietos rezervuaras',
            ],
            'subtitle' => 'Subtitrai',
            'title' => 'Pavadinimas',
            'warning' => [
                'items' => [
                    'audit' => 'Auditas',
                    'notify' => 'Pranešti',
                    'preserved' => 'Konservuota',
                ],
                'title' => 'Pavadinimas',
            ],
        ],
    ],
    'statuses' => [
        'active' => 'Aktyvus',
        'inactive' => 'Neaktyvus',
    ],
    'validation' => [
        'email' => [
            'email' => 'El. paštas',
            'max' => 'Maks',
            'required' => 'Reikalingas',
        ],
        'invoice_id' => [
            'exists' => 'Egzistuoja',
            'required' => 'Reikalingas',
        ],
        'lease_end' => [
            'after' => 'Po to',
            'date' => 'Data',
        ],
        'lease_start' => [
            'date' => 'Data',
            'required' => 'Reikalingas',
        ],
        'name' => [
            'max' => 'Maks',
            'required' => 'Reikalingas',
            'string' => 'Styga',
        ],
        'phone' => [
            'max' => 'Maks',
            'string' => 'Styga',
        ],
        'property_id' => [
            'exists' => 'Egzistuoja',
            'required' => 'Reikalingas',
        ],
        'tenant_id' => [
            'integer' => 'Sveikasis skaičius',
            'required' => 'Reikalingas',
        ],
    ],
];
