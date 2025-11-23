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
            'total_properties' => 'Viso nuosavybių',
            'total_buildings' => 'Viso pastatų',
            'total_tenants' => 'Viso nuomininkų',
            'total_invoices' => 'Viso sąskaitų',
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
        
        'recent_activity' => [
            'title' => 'Paskutinė administratorių veikla',
            'last_activity' => 'Paskutinė veikla:',
            'no_activity' => 'Veiklos dar nėra',
        ],
        
        'quick_actions' => [
            'title' => 'Greiti veiksmai',
            'create_organization' => 'Sukurti naują organizaciją',
            'manage_organizations' => 'Valdyti organizacijas',
            'manage_subscriptions' => 'Valdyti prenumeratas',
        ],
    ],
];
