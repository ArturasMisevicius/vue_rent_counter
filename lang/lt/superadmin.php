<?php

declare(strict_types=1);

return [
    'dashboard' => [
        'title' => 'Superadministratoriaus skydelis',
        'subtitle' => 'Visos sistemos statistika ir organizacijų valdymas',
        
        'stats' => [
            'total_subscriptions' => 'Viso prenumeratų',
            'active_subscriptions' => 'Aktyvios prenumeratos',
            'expired_subscriptions' => 'Pasibaigusios prenumeratos',
            'suspended_subscriptions' => 'Sustabdytos prenumeratos',
            'cancelled_subscriptions' => 'Atšauktos prenumeratos',
            'total_properties' => 'Viso nuosavybių',
            'total_buildings' => 'Viso pastatų',
            'total_tenants' => 'Viso nuomininkų',
            'total_invoices' => 'Viso sąskaitų',
        ],
        'stats_descriptions' => [
            'total_subscriptions' => 'Visos sistemos prenumeratos',
            'active_subscriptions' => 'Šiuo metu aktyvios',
            'expired_subscriptions' => 'Reikalauja pratęsimo',
            'suspended_subscriptions' => 'Laikinai sustabdytos',
            'cancelled_subscriptions' => 'Visiškai atšauktos',
            'total_organizations' => 'Visos sistemos organizacijos',
            'active_organizations' => 'Šiuo metu aktyvios',
            'inactive_organizations' => 'Sustabdyta arba neaktyvi',
        ],
        
        'organizations' => [
            'title' => 'Organizacijos',
            'total' => 'Viso organizacijų',
            'active' => 'Aktyvios organizacijos',
            'inactive' => 'Neaktyvios organizacijos',
            'view_all' => 'Peržiūrėti visas organizacijas →',
            'top_by_properties' => 'Pagrindinės organizacijos pagal nuosavybes',
            'properties_count' => 'nuosavybės',
            'no_organizations' => 'Organizacijų dar nėra',
        ],
        
        'subscription_plans' => [
            'title' => 'Prenumeratos planai',
            'basic' => 'Pagrindinis',
            'professional' => 'Profesionalus',
            'enterprise' => 'Įmonės',
            'view_all' => 'Peržiūrėti visas prenumeratas →',
        ],
        
        'expiring_subscriptions' => [
            'title' => 'Besibaigančios prenumeratos',
            'alert' => ':count prenumerata(-os) baigiasi per 14 dienų',
            'expires' => 'Baigiasi:',
        ],

        'organizations_widget' => [
            'total' => 'Viso organizacijų',
            'active' => 'Aktyvios organizacijos',
            'inactive' => 'Neaktyvios organizacijos',
            'new_this_month' => 'Naujos šį mėnesį',
            'growth_up' => '↑ :value% lyginant su praėjusiu mėnesiu',
            'growth_down' => '↓ :value% lyginant su praėjusiu mėnesiu',
        ],
        
        'recent_activity' => [
            'title' => 'Paskutinė administratorių veikla',
            'last_activity' => 'Paskutinė veikla:',
            'no_activity' => 'Veiklos dar nėra',
            'created_header' => 'Sukurta',
        ],
        'recent_activity_widget' => [
            'heading' => 'Naujausia veikla',
            'description' => 'Paskutiniai 10 veiksmų visose organizacijose',
            'empty_heading' => 'Nėra naujausios veiklos',
            'empty_description' => 'Čia atsiras veiklos žurnalai',
            'default_system' => 'Sistema',
            'columns' => [
                'time' => 'Laikas',
                'user' => 'Vartotojas',
                'organization' => 'Organizacija',
                'action' => 'Veiksmas',
                'resource' => 'Išteklius',
                'id' => 'ID',
                'details' => 'Detalės',
            ],
            'modal_heading' => 'Veiklos detalės',
        ],
        
        'quick_actions' => [
            'title' => 'Greiti veiksmai',
            'create_organization' => 'Sukurti naują organizaciją',
            'manage_organizations' => 'Valdyti organizacijas',
            'manage_subscriptions' => 'Valdyti prenumeratas',
        ],

        'overview' => [
            'subscriptions' => [
                'title' => 'Prenumeratų apžvalga',
                'description' => 'Naujausios prenumeratos, sudarančios valdiklių skaičius',
                'open' => 'Atidaryti prenumeratas',
                'headers' => [
                    'organization' => 'Organizacija',
                    'plan' => 'Planas',
                    'status' => 'Būsena',
                    'expires' => 'Baigiasi',
                    'manage' => 'Valdyti',
                ],
                'empty' => 'Prenumeratų dar nėra',
            ],
            'organizations' => [
                'title' => 'Organizacijų apžvalga',
                'description' => 'Naujausios organizacijos, prisidedančios prie skaičių',
                'open' => 'Atidaryti organizacijas',
                'headers' => [
                    'organization' => 'Organizacija',
                    'subscription' => 'Prenumerata',
                    'status' => 'Būsena',
                    'created' => 'Sukurta',
                    'manage' => 'Valdyti',
                ],
                'no_subscription' => 'Nėra prenumeratos',
                'status_active' => 'Aktyvi',
                'status_inactive' => 'Neaktyvi',
                'empty' => 'Organizacijų dar nėra',
            ],
            'resources' => [
                'title' => 'Sistemos ištekliai',
                'description' => 'Naujausi įrašai, sudarantys išteklių valdiklius',
                'manage_orgs' => 'Valdyti organizacijas',
                'properties' => [
                    'title' => 'Objektai',
                    'open_owners' => 'Atidaryti savininkus',
                    'building' => 'Pastatas',
                    'organization' => 'Organizacija',
                    'unknown_org' => 'Nežinoma',
                    'empty' => 'Objektų nerasta',
                ],
                'buildings' => [
                    'title' => 'Pastatai',
                    'open_owners' => 'Atidaryti savininkus',
                    'address' => 'Adresas',
                    'organization' => 'Organizacija',
                    'manage' => 'Valdyti',
                    'empty' => 'Pastatų nerasta',
                ],
                'tenants' => [
                    'title' => 'Nuomininkai',
                    'open_owners' => 'Atidaryti savininkus',
                    'property' => 'Objektas',
                    'not_assigned' => 'Nepriskirta',
                    'organization' => 'Organizacija',
                    'status_active' => 'Aktyvus',
                    'status_inactive' => 'Neaktyvus',
                    'empty' => 'Nuomininkų nerasta',
                ],
                'invoices' => [
                    'title' => 'Sąskaitos',
                    'open_owners' => 'Atidaryti savininkus',
                    'amount' => 'Suma',
                    'status' => 'Būsena',
                    'organization' => 'Organizacija',
                    'manage' => 'Valdyti',
                    'empty' => 'Sąskaitų nerasta',
                ],
            ],
        ],

        'organizations_list' => [
            'expires' => 'Baigiasi:',
            'no_subscription' => 'Nėra prenumeratos',
            'status_active' => 'Aktyvi',
            'status_inactive' => 'Neaktyvi',
            'actions' => [
                'view' => 'Peržiūrėti',
                'edit' => 'Redaguoti',
            ],
            'empty' => 'Organizacijų nerasta',
        ],

        'organization_show' => [
            'status' => 'Būsena',
            'created' => 'Sukurta',
            'start_date' => 'Pradžios data',
            'expiry_date' => 'Pabaigos data',
            'limits' => 'Limitai',
            'limit_values' => ':properties objektų, :tenants nuomininkų',
            'manage_subscription' => 'Tvarkyti prenumeratą →',
            'no_subscription' => 'Prenumeratos nerasta',
            'stats' => [
                'properties' => 'Objektai',
                'buildings' => 'Pastatai',
                'tenants' => 'Nuomininkai',
                'active_tenants' => 'Aktyvūs nuomininkai',
                'invoices' => 'Sąskaitos',
                'meters' => 'Skaitikliai',
            ],
        ],
    ],
];
