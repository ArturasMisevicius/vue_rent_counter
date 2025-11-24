<?php

declare(strict_types=1);

return [
    'navigation' => 'Veiklos žurnalai',
    'labels' => [
        'timestamp' => 'Laikas',
        'organization' => 'Organizacija',
        'user' => 'Vartotojas',
        'action' => 'Veiksmas',
        'resource' => 'Išteklius',
        'resource_type' => 'Ištekliaus tipas',
        'resource_id' => 'Ištekliaus ID',
        'ip_address' => 'IP adresas',
        'user_agent' => 'Vartotojo agentas',
        'details' => 'Detalės',
        'action_type' => 'Veiksmo tipas',
        'additional_data' => 'Papildomi duomenys',
    ],
    'filters' => [
        'from' => 'Nuo',
        'until' => 'Iki',
        'create' => 'Sukurta',
        'update' => 'Atnaujinta',
        'delete' => 'Ištrinta',
        'view' => 'Peržiūra',
    ],
    'sections' => [
        'activity_details' => 'Veiklos detalės',
        'request_information' => 'Užklausos informacija',
        'metadata' => 'Metaduomenys',
    ],
    'pages' => [
        'index' => [
            'title' => 'Audito žurnalas',
            'breadcrumb' => 'Audito žurnalas',
            'description' => 'Peržiūrėkite sistemos veiksmus ir skaitiklių rodmenų pakeitimus',
            'filters' => [
                'from_date' => 'Data nuo',
                'to_date' => 'Data iki',
                'meter_serial' => 'Skaitiklio numeris',
                'meter_placeholder' => 'Ieškoti pagal numerį...',
                'apply' => 'Taikyti filtrus',
                'clear' => 'Išvalyti',
            ],
            'table' => [
                'caption' => 'Audito žurnalas',
                'timestamp' => 'Laikas',
                'meter' => 'Skaitiklis',
                'reading_date' => 'Nuskaitymo data',
                'old_value' => 'Sena reikšmė',
                'new_value' => 'Nauja reikšmė',
                'changed_by' => 'Pakeitė',
                'reason' => 'Priežastis',
                'reading' => 'Rodmuo:',
            ],
            'states' => [
                'not_available' => 'N/D',
                'system' => 'Sistema',
                'empty' => 'Audito įrašų nerasta.',
                'clear_filters' => 'Išvalyti filtrus',
                'see_all' => 'norėdami matyti visus įrašus.',
                'by' => 'Pakeitė:',
                'old_short' => 'Sena:',
                'new_short' => 'Nauja:',
            ],
        ],
    ],
];
