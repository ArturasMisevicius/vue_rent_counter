<?php

declare(strict_types=1);

return [
    'labels' => [
        'organization' => 'Organizacija',
        'email' => 'El. paštas',
        'contact_name' => 'Kontaktinis asmuo',
        'plan_type' => 'Plano tipas',
        'status' => 'Būsena',
        'starts_at' => 'Pradžios data',
        'expires_at' => 'Pabaigos data',
        'days_left' => 'Likę dienos',
        'days_until_expiry' => 'Dienos iki pabaigos',
        'max_properties' => 'Maks. objektų',
        'max_tenants' => 'Maks. nuomininkų',
        'properties_limit' => 'Objektų limitas',
        'tenants_limit' => 'Nuomininkų limitas',
        'properties_used' => 'Panaudota objektų',
        'properties_remaining' => 'Liko objektų',
        'tenants_used' => 'Panaudota nuomininkų',
        'tenants_remaining' => 'Liko nuomininkų',
        'usage' => 'Naudojimas',
        'new_expiration_date' => 'Nauja galiojimo data',
        'renewal_duration' => 'Pratęsimo trukmė',
        'created_at' => 'Sukurta',
        'updated_at' => 'Atnaujinta',
    ],

    'sections' => [
        'details' => 'Prenumeratos detalės',
        'period' => 'Prenumeratos laikotarpis',
        'limits' => 'Limitai',
        'usage' => 'Naudojimo statistika',
        'timestamps' => 'Laiko žymos',
    ],

    'helper_text' => [
        'select_organization' => 'Pasirinkite organizaciją šiai prenumeratai',
        'max_properties' => 'Leidžiamas maksimalus objektų skaičius',
        'max_tenants' => 'Leidžiamas maksimalus nuomininkų skaičius',
    ],

    'filters' => [
        'plan_type' => 'Plano tipas',
        'status' => 'Būsena',
        'expiring_soon' => 'Baigiasi netrukus (14 dienų)',
        'expired' => 'Nebegalioja',
    ],

    'actions' => [
        'renew' => 'Pratęsti',
        'suspend' => 'Sustabdyti',
        'activate' => 'Aktyvuoti',
        'renew_selected' => 'Pratęsti pasirinktus',
        'suspend_selected' => 'Sustabdyti pasirinktus',
        'activate_selected' => 'Aktyvuoti pasirinktus',
        'export_selected' => 'Eksportuoti pasirinktus',
        'send_reminder' => 'Siųsti priminimą',
        'view_usage' => 'Naudojimas',
        'close' => 'Uždaryti',
        'subscription_usage' => 'Prenumeratos naudojimas',
        'view' => 'Peržiūrėti',
    ],

    'options' => [
        'duration' => [
            '1_month' => '1 mėnuo',
            '3_months' => '3 mėnesiai',
            '6_months' => '6 mėnesiai',
            '1_year' => '1 metai',
        ],
    ],

    'notifications' => [
        'renewed' => 'Prenumerata sėkmingai pratęsta',
        'reminder_sent' => 'Priminimas dėl pratęsimo išsiųstas',
        'suspended' => 'Prenumerata sėkmingai sustabdyta',
        'activated' => 'Prenumerata sėkmingai suaktyvinta',
        'bulk_renewed' => 'Pratęsta :count prenumeratų',
        'bulk_suspended' => 'Sustabdyta :count prenumeratų',
        'bulk_activated' => 'Suaktyvinta :count prenumeratų',
        'bulk_failed_suffix' => ', :count nepavyko',
    ],

    'widgets' => [
        'expiring_heading' => 'Netrukus pasibaigsiančios prenumeratos (14 dienų)',
        'expiring_description' => 'Aktyvios prenumeratos, kurios baigsis per artimiausias 14 dienų',
        'expiring_empty_heading' => 'Nėra pasibaigsiančių prenumeratų',
        'expiring_empty_description' => 'Visos prenumeratos galioja ilgiau nei 14 dienų',
    ],
    'validation' => [
        'plan_type' => [
            'required' => 'Plano tipas yra privalomas.',
            'in' => 'Plano tipas turi būti basic, professional arba enterprise.',
        ],
        'status' => [
            'required' => 'Būsena yra privaloma.',
            'in' => 'Būsena turi būti active, expired, suspended arba cancelled.',
        ],
        'expires_at' => [
            'required' => 'Galiojimo data yra privaloma.',
            'date' => 'Galiojimo data turi būti tinkama data.',
            'after' => 'Galiojimo data turi būti po šiandienos.',
        ],
        'max_properties' => [
            'required' => 'Maksimalus objektų skaičius yra privalomas.',
            'integer' => 'Maksimalus objektų skaičius turi būti skaičius.',
            'min' => 'Maksimalus objektų skaičius turi būti ne mažesnis kaip 1.',
        ],
        'max_tenants' => [
            'required' => 'Maksimalus nuomininkų skaičius yra privalomas.',
            'integer' => 'Maksimalus nuomininkų skaičius turi būti skaičius.',
            'min' => 'Maksimalus nuomininkų skaičius turi būti ne mažesnis kaip 1.',
        ],
        'reason' => [
            'required' => 'Priežastis yra privaloma.',
            'string' => 'Priežastis turi būti tekstas.',
            'max' => 'Priežastis negali viršyti 500 simbolių.',
        ],
    ],
];
