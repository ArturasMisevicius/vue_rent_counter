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
        'assignment_history' => 'Priskyrimų istorijos dar nėra',
        'list' => 'Nuomininkų nerasta',
        'list_cta' => 'Sukurkite pirmą nuomininką',
        'property' => 'Turtas nepriskirtas',
        'recent_invoices' => 'Nėra naujausių sąskaitų',
        'recent_readings' => 'Nėra naujausių rodmenų',
    ],
    'headings' => [
        'account' => 'Nuomininko paskyra',
        'assignment_history' => 'Užduočių istorija',
        'current_property' => 'Dabartinis turtas',
        'index' => 'Nuomininkai',
        'index_description' => 'Valdykite nuomininkų paskyras ir turto priskyrimus',
        'list' => 'Nuomininkų sąrašas',
        'recent_invoices' => 'Naujausios sąskaitos faktūros',
        'recent_readings' => 'Naujausi skaitiniai',
        'show' => 'Nuomininko informacija',
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
            'errors_title' => 'Ištaisykite pažymėtas klaidas',
            'labels' => [
                'email' => 'El. paštas',
                'name' => 'Vardas',
                'password' => 'Slaptažodis',
                'password_confirmation' => 'Slaptažodžio patvirtinimas',
                'property' => 'Turtas',
            ],
            'notes' => [
                'credentials_sent' => 'Prisijungimo duomenis galima išsiųsti sukūrus paskyrą',
                'no_properties' => 'Prieš kurdami nuomininką, pridėkite turtą',
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
                'empty' => 'Šiuo metu turtas nepriskirtas',
                'title' => 'Dabartinis turtas',
            ],
            'errors_title' => 'Ištaisykite pažymėtas klaidas',
            'history' => [
                'empty' => 'Ankstesnių perkėlimų nerasta',
                'title' => 'Perkėlimo istorija',
            ],
            'new_property' => [
                'empty' => 'Nėra galimų turto vienetų',
                'label' => 'Naujas turtas',
                'note' => 'Pasirinkite turtą, kuris turi būti priskirtas šiam nuomininkui',
                'placeholder' => 'Pasirinkite turtą',
            ],
            'subtitle' => 'Perkelkite šį nuomininką į kitą turtą išsaugodami priskyrimų istoriją.',
            'title' => 'Perkelti nuomininką',
            'warning' => [
                'items' => [
                    'audit' => 'Šis pakeitimas bus įrašytas audito žurnale.',
                    'notify' => 'Nuomininką galima informuoti apie perkėlimą.',
                    'preserved' => 'Esama priskyrimų istorija bus išsaugota.',
                ],
                'title' => 'Prieš tęsdami',
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
