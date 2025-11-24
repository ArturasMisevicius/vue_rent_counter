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

    'form_component' => [
        'title' => 'Įvesti skaitiklio rodmenį',
        'select_meter' => 'Pasirinkite skaitiklį',
        'meter_placeholder' => '-- Pasirinkite skaitiklį --',
        'select_provider' => 'Pasirinkite tiekėją',
        'provider_placeholder' => '-- Pasirinkite tiekėją --',
        'select_tariff' => 'Pasirinkite tarifą',
        'tariff_placeholder' => '-- Pasirinkite tarifą --',
        'previous' => 'Ankstesnis rodmuo',
        'date_label' => 'Data:',
        'value_label' => 'Reikšmė:',
        'reading_date' => 'Rodmens data',
        'reading_value' => 'Rodmens reikšmė',
        'day_zone' => 'Dienos zonos rodmuo',
        'night_zone' => 'Nakties zonos rodmuo',
        'consumption' => 'Suvartojimas',
        'units' => 'vnt.',
        'estimated_charge' => 'Numatoma suma',
        'rate' => 'Tarifas:',
        'per_unit' => 'už vienetą',
        'reset' => 'Atstatyti',
        'submit' => 'Pateikti rodmenį',
        'submitting' => 'Siunčiama...',
    ],

    'tables' => [
        'date' => 'Data',
        'meter' => 'Skaitiklis',
        'value' => 'Reikšmė',
        'zone' => 'Zona',
        'entered_by' => 'Įvedė',
        'actions' => 'Veiksmai',
        'property' => 'Objektas',
        'meter_type' => 'Skaitiklio tipas',
        'consumption' => 'Suvartojimas',
        'created_at' => 'Sukurta',
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
        'property' => 'Objektas',
        'meter' => 'Skaitiklis',
        'consumption' => 'Suvartojimas',
        'from_date' => 'Data nuo',
        'until_date' => 'Data iki',
        'meter_type' => 'Skaitiklio tipas',
        'reading_value' => 'Rodmens reikšmė',
        'reading_id' => 'Rodmuo Nr. :id',
    ],

    'empty' => [
        'readings' => 'Rodmenų nerasta.',
    ],

    'recent_empty' => 'Naujų rodmenų nėra',

    'na' => 'N/D',
    'units' => 'vnt.',

    'helper_text' => [
        'select_property_first' => 'Pirmiausia pasirinkite objektą',
        'zone_optional' => 'Pasirinktinai: kelių zonų skaitikliams (pvz., diena/naktis)',
    ],

    'filters' => [
        'from' => 'Data nuo',
        'until' => 'Data iki',
        'meter_type' => 'Skaitiklio tipas',
        'indicator_from' => 'Nuo: :date',
        'indicator_until' => 'Iki: :date',
    ],

    'modals' => [
        'bulk_delete' => [
            'title' => 'Ištrinti rodmenis',
            'description' => 'Ar tikrai norite ištrinti pasirinktus rodmenis? Šio veiksmo anuliuoti negalėsite.',
            'confirm' => 'Taip, ištrinti',
        ],
    ],

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
        'edit' => [
            'title' => 'Taisyti skaitiklio rodmenį',
            'subtitle' => 'Atnaujinkite rodmenį su audito įrašu',
            'breadcrumb' => 'Taisyti',
            'current' => [
                'title' => 'Dabartinis rodmuo',
                'meter' => 'Skaitiklis',
                'value' => 'Dabartinė reikšmė',
                'date' => 'Nuskaitymo data',
            ],
            'form' => [
                'title' => 'Naujos reikšmės',
                'reading_date' => 'Nuskaitymo data',
                'value' => 'Rodmens reikšmė',
                'placeholder' => '1234.56',
                'zone_label' => 'Dienos/nakties zona',
                'zone_options' => [
                    'day' => 'Dienos tarifas',
                    'night' => 'Nakties tarifas',
                ],
                'zone_placeholder' => 'Pasirinkite zoną...',
                'reason_label' => 'Koregavimo priežastis',
                'reason_placeholder' => 'Paaiškinkite, kodėl šis rodmuo taisomas...',
            ],
            'audit_notice' => [
                'title' => 'Audito žurnalas',
                'body' => 'Ši korekcija bus įrašyta audito žurnale. Pirminė reikšmė ir korekcijos priežastis bus išsaugotos.',
            ],
            'actions' => [
                'cancel' => 'Atšaukti',
                'save' => 'Išsaugoti korekciją',
            ],
        ],
        'mobile' => [
            'meter' => 'Skaitiklis:',
            'value' => 'Reikšmė:',
            'zone' => 'Zona:',
        ],
        'captions' => [
            'list' => 'Skaitiklių rodmenų sąrašas',
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
            'min' => 'Pakeitimo priežastis turi būti bent :min simbolių',
            'max' => 'Pakeitimo priežastis negali viršyti :max simbolių',
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
