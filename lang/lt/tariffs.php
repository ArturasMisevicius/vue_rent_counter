<?php

declare(strict_types=1);

return [
    'headings' => [
        'index' => 'Tarifų valdymas',
        'show' => 'Tarifo detalės',
        'information' => 'Tarifo informacija',
        'configuration' => 'Konfigūracija',
        'version_history' => 'Versijų istorija',
        'quick_actions' => 'Greiti veiksmai',
        'list' => 'Tarifų sąrašas',
    ],

    'descriptions' => [
        'index' => 'Konfigūruokite komunalinių paslaugų kainodarą ir laiko zonų tarifus',
        'show' => 'Peržiūrėkite tarifo konfigūraciją ir versijų istoriją',
    ],

    'labels' => [
        'name' => 'Pavadinimas',
        'provider' => 'Tiekėjas',
        'type' => 'Tipas',
        'active_period' => 'Galiojimo laikotarpis',
        'active_from' => 'Galioja nuo',
        'active_until' => 'Galioja iki',
        'present' => 'Dabar',
        'status' => 'Būsena',
        'actions' => 'Veiksmai',
        'service_type' => 'Paslaugos tipas',
    ],

    'actions' => [
        'add' => 'Pridėti tarifą',
        'edit' => 'Redaguoti tarifą',
        'view' => 'Peržiūrėti',
        'back' => 'Grįžti į sąrašą',
        'delete' => 'Ištrinti tarifą',
    ],

    'statuses' => [
        'active' => 'Aktyvus',
        'inactive' => 'Neaktyvus',
    ],

    'empty' => [
        'list' => 'Tarifų nerasta. Sukurkite pirmą tarifą.',
    ],

    'confirmations' => [
        'delete' => 'Ar tikrai norite ištrinti šį tarifą?',
    ],

    'validation' => [
        'provider_id' => [
            'required' => 'Tiekėjas yra privalomas',
            'exists' => 'Pasirinktas tiekėjas neegzistuoja',
        ],
        'name' => [
            'required' => 'Tarifo pavadinimas yra privalomas',
            'string' => 'Tarifo pavadinimas turi būti tekstas',
            'max' => 'Tarifo pavadinimas negali viršyti 255 simbolių',
        ],
        'configuration' => [
            'required' => 'Tarifo konfigūracija yra privaloma',
            'array' => 'Tarifo konfigūracija turi būti masyvas',
            'type' => [
                'required' => 'Tarifo tipas yra privalomas',
                'string' => 'Tarifo tipas turi būti tekstas',
                'in' => 'Tarifo tipas turi būti fiksuotas arba laiko zonų',
            ],
            'currency' => [
                'required' => 'Valiuta yra privaloma',
                'string' => 'Valiuta turi būti tekstas',
                'in' => 'Valiuta turi būti EUR',
            ],
            'rate' => [
                'required_if' => 'Fiksuotam tarifui reikia nurodyti kainą',
                'numeric' => 'Kaina turi būti skaičius',
                'min' => 'Kaina turi būti ne mažesnė nei 0',
            ],
            'zones' => [
                'required_if' => 'Laiko zonų tarifams būtinos zonos',
                'array' => 'Zonos turi būti pateiktos masyvu',
                'min' => 'Laiko zonų tarifams reikia bent vienos zonos',
                'id' => [
                    'required_with' => 'Zonos ID privalomas nurodžius zonas',
                    'string' => 'Zonos ID turi būti tekstas',
                ],
                'start' => [
                    'required_with' => 'Zonos pradžios laikas privalomas',
                    'string' => 'Zonos pradžios laikas turi būti tekstas',
                    'regex' => 'Zonos pradžia turi būti HH:MM formatu (24 val.)',
                ],
                'end' => [
                    'required_with' => 'Zonos pabaigos laikas privalomas',
                    'string' => 'Zonos pabaigos laikas turi būti tekstas',
                    'regex' => 'Zonos pabaiga turi būti HH:MM formatu (24 val.)',
                ],
                'rate' => [
                    'required_with' => 'Kiekvienai zonai reikia nurodyti kainą',
                    'numeric' => 'Zonos kaina turi būti skaičius',
                    'min' => 'Zonos kaina turi būti teigiamas skaičius',
                ],
                'errors' => [
                    'required' => 'Reikalinga bent viena zona',
                    'overlap' => 'Laiko zonos negali persidengti.',
                    'coverage' => 'Laiko zonos turi dengti visas 24 valandas. Tarpas aptiktas nuo :time.',
                ],
            ],
            'weekend_logic' => [
                'string' => 'Savaitgalio logika turi būti tekstas.',
                'in' => 'Savaitgalio logika turi būti apply_night_rate, apply_day_rate arba apply_weekend_rate.',
            ],
            'fixed_fee' => [
                'numeric' => 'Fiksuotas mokestis turi būti skaičius.',
                'min' => 'Fiksuotas mokestis turi būti ne mažesnis nei 0.',
            ],
        ],
        'active_from' => [
            'required' => 'Reikia nurodyti galiojimo pradžios datą',
            'date' => 'Galiojimo pradžios data turi būti tinkama data',
        ],
        'active_until' => [
            'after' => 'Galiojimo pabaiga turi būti po pradžios datos',
            'date' => 'Galiojimo pabaiga turi būti tinkama data',
        ],
        'create_new_version' => [
            'boolean' => 'Nauja versija turi būti tiesa arba netiesa.',
        ],
    ],
];
