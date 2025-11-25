<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Gyvatukas Language Lines (Lithuanian)
    |--------------------------------------------------------------------------
    */

    'attributes' => [
        'building' => 'pastatas',
        'billing_month' => 'apskaitos mėnuo',
        'distribution_method' => 'paskirstymo metodas',
    ],

    'validation' => [
        'building_required' => 'Pastatas yra privalomas.',
        'building_not_found' => 'Pastatas nerastas.',
        'no_properties' => 'Pastate turi būti bent vienas butas.',
        'unauthorized_building' => 'Jūs neturite teisės skaičiuoti šiam pastatui.',
        
        'billing_month_required' => 'Apskaitos mėnuo yra privalomas.',
        'billing_month_invalid' => 'Apskaitos mėnuo turi būti galiojanti data.',
        'billing_month_future' => 'Apskaitos mėnuo negali būti ateityje.',
        'billing_month_too_old' => 'Apskaitos mėnuo yra per senas.',
        
        'distribution_method_invalid' => 'Paskirstymo metodas turi būti "equal" arba "area".',
    ],

    'errors' => [
        'unauthorized' => 'Jūs neturite teisės atlikti šį skaičiavimą.',
        'rate_limit_exceeded' => 'Per daug skaičiavimų. Bandykite vėliau.',
        'invalid_configuration' => 'Neteisinga gyvatukas konfigūracija. Susisiekite su palaikymu.',
        'calculation_failed' => 'Skaičiavimas nepavyko. Bandykite dar kartą.',
    ],

    'messages' => [
        'calculation_complete' => 'Gyvatukas skaičiavimas sėkmingai baigtas.',
        'audit_created' => 'Skaičiavimo audito įrašas sukurtas.',
    ],
];
