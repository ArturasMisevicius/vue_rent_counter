<?php

return [
    'actions' => [
        'add' => 'Pridėti',
        'deactivate' => 'Išjungti',
        'reactivate' => 'Iš naujo suaktyvinkite',
        'reassign' => 'Perskirti',
        'view' => 'Peržiūrėti',
    ],
    'empty' => [
        'assignment_history' => 'Užduočių istorija',
        'list' => 'Sąrašas',
        'list_cta' => 'Sąrašas Cta',
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
        'building' => 'Pastatas',
        'created' => 'Sukurta',
        'created_by' => 'Sukūrė',
        'email' => 'El. paštas',
        'invoice' => 'Sąskaita faktūra',
        'name' => 'Vardas',
        'phone' => 'Telefonas',
        'property' => 'Turtas',
        'reading' => 'Skaitymas',
        'reason' => 'Priežastis',
        'status' => 'Būsena',
        'type' => 'Tipas',
    ],
    'pages' => [
        'index' => [
            'subtitle' => 'Visi nuomininkai visose organizacijose',
            'title' => 'Nuomininkai',
        ],
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
                'property' => 'Pasirinkite nuosavybę',
            ],
            'subtitle' => 'Sukurkite nuomininko paskyrą ir priskirkite ją nuosavybei savo portfelyje.',
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
    'sections' => [
        'details' => 'Detalės',
        'invoices' => 'Sąskaitos faktūros',
        'stats' => 'Statistika',
    ],
    'validation' => [
        'email' => [
            'email' => 'El. paštas',
            'max' => 'Maks',
            'required' => 'Privaloma',
        ],
        'invoice_id' => [
            'exists' => 'Egzistuoja',
            'required' => 'Privaloma',
        ],
        'lease_end' => [
            'after' => 'Po to',
            'date' => 'Data',
        ],
        'lease_start' => [
            'date' => 'Data',
            'required' => 'Privaloma',
        ],
        'name' => [
            'max' => 'Maks',
            'required' => 'Privaloma',
            'string' => 'Styga',
        ],
        'phone' => [
            'max' => 'Maks',
            'string' => 'Styga',
        ],
        'property_id' => [
            'exists' => 'Egzistuoja',
            'required' => 'Privaloma',
        ],
        'tenant_id' => [
            'integer' => 'Sveikasis skaičius',
            'required' => 'Privaloma',
        ],
    ],
];
