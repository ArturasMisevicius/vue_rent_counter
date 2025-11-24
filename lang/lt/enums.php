<?php

return [
    'meter_type' => [
        'electricity' => 'Elektra',
        'water_cold' => 'Šaltas vanduo',
        'water_hot' => 'Karštas vanduo',
        'heating' => 'Šildymas',
    ],

    'property_type' => [
        'apartment' => 'Butas',
        'house' => 'Namas',
    ],

    'service_type' => [
        'electricity' => 'Elektra',
        'water' => 'Vanduo',
        'heating' => 'Šildymas',
    ],

    'invoice_status' => [
        'draft' => 'Juodraštis',
        'finalized' => 'Galutinis',
        'paid' => 'Apmokėtas',
    ],

    'user_role' => [
        'superadmin' => 'Super administratorius',
        'admin' => 'Administratorius',
        'manager' => 'Vadybininkas',
        'tenant' => 'Nuomininkas',
    ],

    'tariff_type' => [
        'flat' => 'Fiksuotas tarifas',
        'time_of_use' => 'Laiko zonų tarifas',
    ],

    'tariff_zone' => [
        'day' => 'Dieninis tarifas',
        'night' => 'Naktinis tarifas',
        'weekend' => 'Savaitgalio tarifas',
    ],

    'weekend_logic' => [
        'apply_night_rate' => 'Taikyti naktinį tarifą savaitgaliais',
        'apply_day_rate' => 'Taikyti dieninį tarifą savaitgaliais',
        'apply_weekend_rate' => 'Taikyti savaitgalio tarifą',
    ],

    'subscription_plan_type' => [
        'basic' => 'Pagrindinis',
        'professional' => 'Profesionalus',
        'enterprise' => 'Įmonės',
    ],

    'subscription_status' => [
        'active' => 'Aktyvi',
        'expired' => 'Pasibaigusi',
        'suspended' => 'Sustabdyta',
        'cancelled' => 'Atšaukta',
    ],

    'user_assignment_action' => [
        'created' => 'Sukurta',
        'assigned' => 'Priskirta',
        'reassigned' => 'Priskirta iš naujo',
        'deactivated' => 'Deaktyvuota',
        'reactivated' => 'Aktyvuota iš naujo',
    ],
];
