<?php

declare(strict_types=1);

return [
    'manager' => [
        'title' => 'Vadybininko skydelis',
        'description' => 'Objektų valdymo apžvalga ir laukiančios užduotys',
        'stats' => [
            'total_properties' => 'Iš viso objektų',
            'meters_pending' => 'Skaitikliai be rodmenų',
            'draft_invoices' => 'Sąskaitų juodraščiai',
            'active_meters' => 'Aktyvūs skaitikliai',
            'active_tenants' => 'Aktyvūs nuomininkai',
            'overdue_invoices' => 'Vėluojančios sąskaitos',
        ],
        'pending_section' => 'Objektai, kuriems reikia rodmenų',
        'pending_meter_line' => '{1} :count skaitikliui reikia rodmens šį mėnesį|[2,*] :count skaitikliams reikia rodmenų šį mėnesį',
        'quick_actions' => [
            'view_buildings' => 'Peržiūrėti pastatus',
            'view_buildings_desc' => 'Valdyti daugiabučius pastatus',
            'view_meters' => 'Peržiūrėti skaitiklius',
            'view_meters_desc' => 'Valdyti komunalinius skaitiklius',
            'view_reports' => 'Peržiūrėti ataskaitas',
            'view_reports_desc' => 'Analitika ir įžvalgos',
            'enter_reading_desc' => 'Fiksuokite šio mėnesio suvartojimą.',
            'generate_invoice_desc' => 'Kurti ir patvirtinti sąskaitas.',
        ],
        'sections' => [
            'operations' => 'Operatyvinės užduotys',
            'drafts' => 'Sąskaitų juodraščiai',
            'recent' => 'Naujausios sąskaitos',
            'shortcuts' => 'Valdymo nuorodos',
        ],
        'hints' => [
            'operations' => 'Skaitikliai, kuriems dar reikia šio mėnesio rodmenų.',
            'drafts' => 'Patvirtinkite juodraščius prieš sąskaitų išsiuntimą.',
            'recent' => 'Naujausia sąskaitų veikla jūsų portfelyje.',
            'shortcuts' => 'Greitos nuorodos į dažniausiai naudojamus puslapius.',
        ],
        'empty' => [
            'operations' => 'Visi skaitikliai šį mėnesį atnaujinti.',
            'drafts' => 'Šiuo metu nėra juodraščių.',
            'recent' => 'Šiam laikotarpiui sąskaitų nėra.',
        ],
    ],

    'admin' => [
        'title' => 'Administratoriaus skydelis',
        'portfolio_subtitle' => 'Portfelio apžvalga ir statistika',
        'system_subtitle' => 'Sistemos apžvalga ir statistika',
        'banner' => [
            'no_subscription_title' => 'Nėra aktyvios prenumeratos',
            'no_subscription_body' => 'Neturite aktyvios prenumeratos. Susisiekite su palaikymu, kad suaktyvintumėte paskyrą.',
            'expired_title' => 'Prenumerata baigėsi',
            'expired_body' => 'Jūsų prenumerata baigėsi :date. Atnaujinkite, kad galėtumėte toliau valdyti objektus.',
            'expiring_title' => 'Prenumerata netrukus baigsis',
            'expiring_body' => 'Prenumerata baigsis po :days, :date. Atnaujinkite, kad išvengtumėte paslaugų sutrikimų.',
            'renew_now' => 'Atnaujinti dabar',
            'renew' => 'Atnaujinti prenumeratą',
            'days' => '{1} :count dieną|{2} :count dienas|[3,9]:count dienų|[10,*]:count dienų',
        ],
        'subscription_card' => [
            'title' => 'Prenumeratos būsena',
            'plan_type' => 'Plano tipas',
            'expires' => 'Baigiasi',
            'properties' => 'Objektai',
            'approaching_limit' => 'Artėjate prie limito',
            'tenants' => 'Nuomininkai',
        ],
        'stats' => [
            'total_meter_readings' => 'Iš viso rodmenų',
            'total_users' => 'Iš viso vartotojų',
            'total_properties' => 'Iš viso objektų',
            'active_tenants' => 'Aktyvūs nuomininkai',
            'active_meters' => 'Aktyvūs skaitikliai',
            'unpaid_invoices' => 'Neapmokėtos sąskaitos',
        ],
        'breakdown' => [
            'users_title' => 'Vartotojų pasiskirstymas',
            'invoice_title' => 'Sąskaitų būsenos',
            'administrators' => 'Administratoriai',
            'managers' => 'Vadybininkai',
            'tenants' => 'Nuomininkai',
            'draft_invoices' => 'Sąskaitų juodraščiai',
            'finalized_invoices' => 'Patvirtintos sąskaitos',
            'paid_invoices' => 'Apmokėtos sąskaitos',
        ],
        'activity' => [
            'recent_portfolio' => 'Naujausia portfelio veikla',
            'recent_system' => 'Naujausia sistemos veikla',
            'recent_tenants' => 'Nauji nuomininkai',
            'recent_users' => 'Nauji vartotojai',
            'recent_invoices' => 'Naujos sąskaitos',
            'recent_readings' => 'Naujausi rodmenys',
            'no_users' => 'Naujų vartotojų nėra',
        ],
        'quick' => [
            'settings' => 'Sistemos nustatymai',
            'settings_desc' => 'Konfigūruoti sistemos nustatymus',
            'create_user' => 'Sukurti vartotoją',
            'create_user_desc' => 'Pridėti naują sistemos vartotoją',
        ],
        'quick_actions' => [
            'title' => 'Greiti veiksmai',
            'manage_tenants_title' => 'Valdyti nuomininkus',
            'manage_tenants_desc' => 'Peržiūrėkite ir valdykite nuomininkų paskyras',
            'organization_profile_title' => 'Organizacijos profilis',
            'organization_profile_desc' => 'Tvarkykite profilį ir prenumeratą',
            'create_tenant_title' => 'Sukurti nuomininką',
            'create_tenant_desc' => 'Pridėkite nuomininką į portfelį',
            'manage_users_title' => 'Valdyti vartotojus',
            'manage_users_desc' => 'Vartotojų paskyros ir rolės',
        ],
        'org_dashboard' => ':name skydelis',
    ],

    'tenant' => [
        'title' => 'Mano skydelis',
        'description' => 'Jūsų komunalinių mokėjimų apžvalga',
        'alerts' => [
            'no_property_title' => 'Objektas nepriskirtas',
            'no_property_body' => 'Jums dar nepriskirtas objektas. Susisiekite su administratoriumi.',
        ],
        'property' => [
            'title' => 'Mano objektas',
            'address' => 'Adresas',
            'type' => 'Objekto tipas',
            'area' => 'Plotas',
            'building' => 'Pastatas',
        ],
        'balance' => [
            'title' => 'Neapmokėta suma',
            'outstanding' => 'Neapmokėta suma:',
            'notice' => 'Turite :count neapmokėtų sąskaitų. Peržiūrėkite ir apmokėkite.',
            'cta' => 'Peržiūrėti sąskaitas',
        ],
        'stats' => [
            'total_invoices' => 'Viso sąskaitų',
            'unpaid_invoices' => 'Neapmokėtos sąskaitos',
            'active_meters' => 'Aktyvūs skaitikliai',
        ],
        'readings' => [
            'title' => 'Naujausi skaitiklių rodmenys',
            'meter_type' => 'Skaitiklio tipas',
            'serial' => 'Serijos numeris',
            'reading' => 'Rodmuo',
            'date' => 'Data',
            'serial_short' => 'Serija:',
            'units' => [
                'electricity' => 'kWh',
                'default' => 'm³',
            ],
        ],
        'consumption' => [
            'title' => 'Suvartojimo palyginimas',
            'description' => 'Paskutinių rodmenų pokyčiai palyginti su ankstesniais.',
            'need_more' => 'Reikia bent dviejų rodmenų kiekvienam skaitikliui.',
            'current' => 'dabartinis',
            'previous' => 'Ankstesnis: :value (:date)',
            'since_last' => 'nuo paskutinio rodmens',
            'missing_previous' => 'Reikia ankstesnio rodmens, kad būtų apskaičiuotas pokytis.',
        ],
        'quick_actions' => [
            'title' => 'Greiti veiksmai',
            'description' => 'Greitai pereikite į dažniausiai naudojamus puslapius.',
            'invoices_title' => 'Mano sąskaitos',
            'invoices_desc' => 'Peržiūrėkite komunalines sąskaitas',
            'meters_title' => 'Mano skaitikliai',
            'meters_desc' => 'Peržiūrėkite suvartojimo istoriją',
            'property_title' => 'Mano objektas',
            'property_desc' => 'Objekto detalės',
        ],
    ],

    'widgets' => [
        'admin' => [
            'total_properties' => [
                'label' => 'Viso objektų',
                'description' => 'Objektai jūsų portfelyje',
            ],
            'total_buildings' => [
                'label' => 'Viso pastatų',
                'description' => 'Tvarkomi pastatai',
            ],
            'active_tenants' => [
                'label' => 'Aktyvūs nuomininkai',
                'description' => 'Aktyvios nuomininkų paskyros',
            ],
            'draft_invoices' => [
                'label' => 'Juodraštinės sąskaitos',
                'description' => 'Sąskaitos laukia užbaigimo',
            ],
            'pending_readings' => [
                'label' => 'Laukiantys rodmenys',
                'description' => 'Patvirtintini skaitiklių rodmenys',
            ],
            'total_revenue' => [
                'label' => 'Bendra pajamų suma (šį mėn.)',
                'description' => 'Pajamos iš patvirtintų sąskaitų',
            ],
        ],
        'manager' => [
            'total_properties' => [
                'label' => 'Viso objektų',
                'description' => 'Tvarkomi objektai',
            ],
            'total_buildings' => [
                'label' => 'Viso pastatų',
                'description' => 'Tvarkomi pastatai',
            ],
            'pending_readings' => [
                'label' => 'Laukiantys rodmenys',
                'description' => 'Patvirtintini skaitiklių rodmenys',
            ],
            'draft_invoices' => [
                'label' => 'Juodraštinės sąskaitos',
                'description' => 'Sąskaitos laukia užbaigimo',
            ],
        ],
        'tenant' => [
            'property' => [
                'label' => 'Jūsų objektas',
                'description' => 'Priskirtas objektas',
            ],
            'invoices' => [
                'label' => 'Jūsų sąskaitos',
                'description' => 'Visos sąskaitos',
            ],
            'unpaid' => [
                'label' => 'Neapmokėtos sąskaitos',
                'description' => 'Sąskaitos, laukiančios apmokėjimo',
            ],
        ],
    ],
];
