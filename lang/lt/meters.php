<?php

return [
    'actions' => [
        'add' => 'Pridėti',
        'create' => 'Sukurti',
        'delete' => 'Ištrinti',
        'edit' => 'Redaguoti',
        'edit_meter' => 'Redaguoti matuoklį',
        'view' => 'Peržiūrėti',
        'view_readings' => 'Žiūrėti skaitymus',
    ],
    'confirmations' => [
        'delete' => 'Ištrinti',
    ],
    'empty_state' => [
        'description' => 'Aprašymas',
        'heading' => 'Antraštė',
    ],
    'errors' => [
        'has_readings' => 'Turi skaitymų',
    ],
    'filters' => [
        'no_readings' => 'Jokių skaitymų',
        'property' => 'Turtas',
        'supports_zones' => 'Palaiko zonas',
        'type' => 'Tipas',
    ],
    'headings' => [
        'information' => 'Informacija',
        'show' => 'Rodyti',
        'show_description' => 'Rodyti aprašą',
    ],
    'helper_text' => [
        'installation_date' => 'Įdiegimo data',
        'property' => 'Turtas',
        'serial_number' => 'Serijos numeris',
        'supports_zones' => 'Palaiko zonas',
        'type' => 'Tipas',
    ],
    'labels' => [
        'created' => 'Sukurta',
        'installation_date' => 'Įdiegimo data',
        'meter' => 'Skaitiklis',
        'meters' => 'Skaitikliai',
        'property' => 'Turtas',
        'readings' => 'Skaitymai',
        'readings_count' => 'Skaitymų skaičius',
        'serial_number' => 'Serijos numeris',
        'supports_zones' => 'Palaiko zonas',
        'type' => 'Tipas',
    ],
    'manager' => [
        'index' => [
            'caption' => 'Antraštė',
            'description' => 'Aprašymas',
            'empty' => [
                'cta' => 'Cta',
                'text' => 'Tekstas',
            ],
            'headers' => [
                'actions' => 'Veiksmai',
                'installation_date' => 'Įdiegimo data',
                'latest_reading' => 'Naujausias skaitymas',
                'property' => 'Turtas',
                'serial_number' => 'Serijos numeris',
                'type' => 'Tipas',
                'zones' => 'Zonos',
            ],
            'title' => 'Pavadinimas',
            'zones' => [
                'no' => 'Nr',
                'yes' => 'Taip',
            ],
        ],
    ],
    'modals' => [
        'bulk_delete' => [
            'confirm' => 'Patvirtinti',
            'description' => 'Aprašymas',
            'title' => 'Pavadinimas',
        ],
        'delete_confirm' => 'Ištrinti Patvirtinti',
        'delete_description' => 'Ištrinti aprašymą',
        'delete_heading' => 'Ištrinti antraštę',
    ],
    'notifications' => [
        'created' => 'Sukurta',
        'updated' => 'Atnaujinta',
    ],
    'placeholders' => [
        'serial_number' => 'Serijos numeris',
    ],
    'relation' => [
        'add_first' => 'Pridėti pirmiausia',
        'empty_description' => 'Tuščias aprašymas',
        'empty_heading' => 'Tuščia antraštė',
        'initial_reading' => 'Pradinis skaitymas',
        'installation_date' => 'Įdiegimo data',
        'installed' => 'Įdiegta',
        'meter_type' => 'Skaitiklio tipas',
        'readings' => 'Skaitymai',
        'serial_number' => 'Serijos numeris',
        'type' => 'Tipas',
    ],
    'sections' => [
        'meter_details' => 'Skaitiklio detalės',
        'meter_details_description' => 'Skaitiklio detalės aprašymas',
    ],
    'tooltips' => [
        'copy_serial' => 'Kopijuoti seriją',
        'property_address' => 'Nuosavybės adresas',
        'readings_count' => 'Skaitymų skaičius',
        'supports_zones_no' => 'Palaiko zonas Nr',
        'supports_zones_yes' => 'Palaiko zonas Taip',
    ],
    'units' => [
        'kwh' => 'Kwh',
    ],
    'validation' => [
        'installation_date' => [
            'before_or_equal' => 'Prieš arba lygus',
            'date' => 'Data',
            'required' => 'Privaloma',
        ],
        'property_id' => [
            'exists' => 'Egzistuoja',
            'required' => 'Privaloma',
        ],
        'serial_number' => [
            'max' => 'Maks',
            'required' => 'Privaloma',
            'string' => 'Styga',
            'unique' => 'Unikali',
        ],
        'supports_zones' => [
            'boolean' => 'Būlio',
        ],
        'tenant_id' => [
            'integer' => 'Sveikasis skaičius',
            'required' => 'Privaloma',
        ],
        'type' => [
            'enum_detail' => 'Enum detalė',
            'required' => 'Privaloma',
        ],
    ],
];
