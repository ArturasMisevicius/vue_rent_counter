<?php

return [
    'meter_type' => [
        'electricity' => 'Electricity',
        'water_cold' => 'Cold Water',
        'water_hot' => 'Hot Water',
        'heating' => 'Heating',
    ],

    'property_type' => [
        'apartment' => 'Apartment',
        'house' => 'House',
    ],

    'service_type' => [
        'electricity' => 'Electricity',
        'water' => 'Water',
        'heating' => 'Heating',
    ],

    'invoice_status' => [
        'draft' => 'Draft',
        'finalized' => 'Finalized',
        'paid' => 'Paid',
    ],

    'user_role' => [
        'superadmin' => 'Super Admin',
        'admin' => 'Admin',
        'manager' => 'Manager',
        'tenant' => 'Tenant',
    ],

    'tariff_type' => [
        'flat' => 'Flat Rate',
        'time_of_use' => 'Time of Use',
    ],

    'tariff_zone' => [
        'day' => 'Day Rate',
        'night' => 'Night Rate',
        'weekend' => 'Weekend Rate',
    ],

    'weekend_logic' => [
        'apply_night_rate' => 'Apply Night Rate on Weekends',
        'apply_day_rate' => 'Apply Day Rate on Weekends',
        'apply_weekend_rate' => 'Apply Special Weekend Rate',
    ],
];
