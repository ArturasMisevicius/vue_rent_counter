<?php

declare(strict_types=1);

return [
    'labels' => [
        'meter' => 'Skaitiklis',
        'meters' => 'Skaitikliai',
        'property' => 'Objektas',
        'type' => 'Skaitiklio tipas',
        'serial_number' => 'Serijos numeris',
        'installation_date' => 'Įrengimo data',
        'last_reading' => 'Paskutinis rodmuo',
        'status' => 'Būsena',
        'supports_zones' => 'Palaiko zonas',
        'supports_time_of_use' => 'Palaiko laiko zonas',
        'zones' => 'Zonos',
        'created_at' => 'Sukurta',
        'initial_reading' => 'Pradinis rodmuo',
        'installed' => 'Įrengta',
        'readings' => 'Rodmenys',
    ],

    'headings' => [
        'show' => 'Skaitiklis :serial',
        'show_description' => 'Skaitiklio detalės ir rodmenų istorija',
        'information' => 'Skaitiklio informacija',
    ],

    'actions' => [
        'add' => 'Pridėti skaitiklį',
        'view' => 'Peržiūrėti',
        'edit' => 'Redaguoti',
        'edit_meter' => 'Redaguoti skaitiklį',
        'delete' => 'Ištrinti',
    ],

    'confirmations' => [
        'delete' => 'Ar tikrai norite ištrinti šį skaitiklį?',
    ],

    'manager' => [
        'index' => [
            'title' => 'Skaitikliai',
            'description' => 'Komunaliniai skaitikliai visuose objektuose',
            'caption' => 'Skaitiklių sąrašas',
            'headers' => [
                'serial_number' => 'Serijos numeris',
                'type' => 'Tipas',
                'property' => 'Objektas',
                'installation_date' => 'Įrengimo data',
                'latest_reading' => 'Naujausias rodmuo',
                'zones' => 'Zonos',
                'actions' => 'Veiksmai',
            ],
            'zones' => [
                'yes' => 'Taip',
                'no' => 'Ne',
            ],
            'empty' => [
                'text' => 'Skaitiklių nerasta.',
                'cta' => 'Sukurti naują',
            ],
        ],
    ],

    'helper_text' => [
        'supports_time_of_use' => 'Įjunkite elektros skaitikliams su dienos/nakties tarifu',
    ],

    'filters' => [
        'supports_zones' => 'Palaiko zonas',
        'all_meters' => 'Visi skaitikliai',
        'with_zones' => 'Su zonomis',
        'without_zones' => 'Be zonų',
    ],

    'relation' => [
        'meter_type' => 'Skaitiklio tipas',
        'serial_number' => 'Serijos numeris',
        'installation_date' => 'Įrengimo data',
        'initial_reading' => 'Pradinis rodmuo',
        'readings' => 'Rodmenys',
        'installed' => 'Įrengta',
        'type' => 'Tipas',
        'empty_heading' => 'Skaitiklių nėra',
        'empty_description' => 'Pridėkite skaitiklius, kad galėtumėte sekti suvartojimą šiame objekte.',
        'add_first' => 'Pridėti pirmą skaitiklį',
    ],

    'units' => [
        'kwh' => 'kWh',
    ],

    'validation' => [
        'tenant_id' => [
            'required' => 'Nuomininkas yra privalomas.',
            'integer' => 'Nuomininko identifikatorius turi būti skaičius.',
        ],
        'property_id' => [
            'required' => 'Objektas yra privalomas.',
            'exists' => 'Pasirinktas objektas neegzistuoja.',
        ],
        'type' => [
            'required' => 'Skaitiklio tipas yra privalomas.',
            'enum' => 'Skaitiklio tipas turi būti tinkamas.',
            'enum_detail' => 'Skaitiklio tipas turi būti: elektra, šaltas vanduo, karštas vanduo arba šildymas.',
        ],
        'serial_number' => [
            'required' => 'Skaitiklio serijos numeris yra privalomas.',
            'string' => 'Serijos numeris turi būti tekstas.',
            'unique' => 'Šis serijos numeris jau naudojamas.',
            'max' => 'Serijos numeris negali viršyti 255 simbolių.',
        ],
        'installation_date' => [
            'required' => 'Įrengimo data yra privaloma.',
            'date' => 'Įrengimo data turi būti tinkama data.',
            'before_or_equal' => 'Įrengimo data negali būti ateityje.',
        ],
        'supports_zones' => [
            'boolean' => 'Zonų palaikymas turi būti teisinga arba klaidinga reikšmė.',
        ],
    ],

    'errors' => [
        'has_readings' => 'Negalima ištrinti skaitiklio, turinčio susietų rodmenų.',
    ],
];
