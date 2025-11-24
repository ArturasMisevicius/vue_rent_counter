<?php

declare(strict_types=1);

return [
    'headings' => [
        'index' => 'Skaitiklių rodmenys',
        'create' => 'Įvesti skaitiklio rodmenį',
        'show' => 'Rodmens detalės',
        'edit' => 'Redaguoti rodmenį',
    ],

    'actions' => [
        'back' => 'Grįžti į rodmenis',
        'enter_new' => 'Įvesti naują rodmenį',
        'view' => 'Peržiūrėti',
        'edit' => 'Redaguoti',
        'delete' => 'Ištrinti',
        'correct' => 'Taisyti rodmenį',
    ],

    'tables' => [
        'date' => 'Data',
        'meter' => 'Skaitiklis',
        'value' => 'Reikšmė',
        'zone' => 'Zona',
        'entered_by' => 'Įvedė',
        'actions' => 'Veiksmai',
    ],

    'labels' => [
        'meter_serial' => 'Skaitiklio numeris',
        'reading_date' => 'Rodmens data',
        'value' => 'Reikšmė',
        'zone' => 'Zona',
        'entered_by' => 'Įvedė',
        'created_at' => 'Sukurta',
        'history' => 'Taisymų istorija',
        'old_value' => 'Sena reikšmė',
        'new_value' => 'Nauja reikšmė',
        'reason' => 'Priežastis',
        'changed_by' => 'Pakeitė',
    ],

    'empty' => [
        'readings' => 'Rodmenų nerasta.',
    ],

    'recent_empty' => 'Naujų rodmenų nėra',

    'na' => 'N/D',

    'manager' => [
        'index' => [
            'title' => 'Skaitiklių rodmenys',
            'description' => 'Registruoti suvartojimo rodmenys visuose objektuose',
            'filters' => [
                'group_by' => 'Grupuoti pagal',
                'none' => 'Nebegrupuoti',
                'property' => 'Pagal objektą',
                'meter_type' => 'Pagal skaitiklio tipą',
                'property_label' => 'Objektas',
                'all_properties' => 'Visi objektai',
                'meter_type_label' => 'Skaitiklio tipas',
                'all_types' => 'Visi tipai',
                'apply' => 'Taikyti filtrus',
            ],
            'captions' => [
                'property' => 'Rodmenys sugrupuoti pagal objektą',
                'meter_type' => 'Rodmenys sugrupuoti pagal skaitiklio tipą',
            ],
            'count' => '{1} :count rodmuo|[2,*] :count rodmenys',
            'empty' => [
                'text' => 'Rodmenų nerasta.',
                'cta' => 'Sukurti rodmenį',
            ],
        ],
        'show' => [
            'description' => 'Skaitiklio detalės ir rodmenų istorija',
        ],
        'create' => [
            'select_meter' => 'Pasirinkite skaitiklį...',
            'zone_options' => [
                'day' => 'Dieninis tarifas',
                'night' => 'Naktinis tarifas',
            ],
        ],
    ],

    'tenant' => [
        'title' => 'Mano suvartojimo istorija',
        'description' => 'Peržiūrėkite skaitiklių rodmenis ir suvartojimo tendencijas',
        'filters' => [
            'title' => 'Filtrai',
            'description' => 'Susiaurinkite pagal skaitiklio tipą ar laikotarpį.',
            'meter_type' => 'Skaitiklio tipas',
            'all_types' => 'Visi tipai',
            'date_from' => 'Data nuo',
            'date_to' => 'Data iki',
        ],
        'submit' => [
            'title' => 'Pateikti rodmenį',
            'description' => 'Pridėkite naują savo skaitiklio rodmenį.',
            'no_property' => 'Reikia priskirto objekto su skaitikliu, kad pateiktumėte rodmenį.',
            'meter' => 'Skaitiklis',
            'reading_date' => 'Rodmens data',
            'value' => 'Reikšmė',
            'button' => 'Pateikti rodmenį',
        ],
        'table' => [
            'date' => 'Data',
            'reading' => 'Rodmuo',
            'consumption' => 'Suvartojimas',
            'zone' => 'Zona',
        ],
        'empty' => [
            'title' => 'Rodmenų nerasta',
            'description' => 'Pagal pasirinktus filtrus rodmenų nėra.',
        ],
    ],

    'validation' => [
        'meter_id' => [
            'required' => 'Skaitiklis yra privalomas',
            'exists' => 'Pasirinktas skaitiklis neegzistuoja',
        ],
        'reading_date' => [
            'required' => 'Rodmens data yra privaloma',
            'date' => 'Rodmens data turi būti tinkama data',
            'before_or_equal' => 'Rodmens data negali būti ateityje',
        ],
        'value' => [
            'required' => 'Skaitiklio rodmuo yra privalomas',
            'numeric' => 'Rodmuo turi būti skaičius',
            'min' => 'Rodmuo turi būti teigiamas skaičius',
        ],
        'change_reason' => [
            'required' => 'Reikalinga pakeitimo priežastis audito žurnalui',
            'min' => 'Pakeitimo priežastis turi būti bent 10 simbolių',
            'max' => 'Pakeitimo priežastis negali viršyti 500 simbolių',
        ],
        'zone' => [
            'string' => 'Zona turi būti tekstas.',
            'max' => 'Zona negali viršyti 50 simbolių.',
        ],
        'custom' => [
            'monotonicity_lower' => 'Rodmuo negali būti mažesnis nei ankstesnis rodmuo (:previous)',
            'monotonicity_higher' => 'Rodmuo negali būti didesnis nei kitas rodmuo (:next)',
            'zone' => [
                'unsupported' => 'Šis skaitiklis nepalaiko zoninių rodmenų',
                'required_for_multi_zone' => 'Skaitikliams su keliomis zonomis reikia nurodyti zoną',
            ],
        ],
        'bulk' => [
            'readings' => [
                'required' => 'Reikalingas bent vienas rodmuo.',
                'array' => 'Rodmenys turi būti pateikti masyvo formatu.',
            ],
        ],
    ],

    'errors' => [
        'export_pending' => 'Eksportas dar neįgyvendintas',
    ],
];
