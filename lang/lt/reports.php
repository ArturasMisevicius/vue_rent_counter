<?php

declare(strict_types=1);

return [
    'validation' => [
        'report_type' => [
            'required' => 'Ataskaitos tipas yra privalomas.',
            'in' => 'Ataskaitos tipas turi būti consumption, revenue, outstanding arba meter-readings.',
        ],
        'format' => [
            'required' => 'Eksporto formatas yra privalomas.',
            'in' => 'Eksporto formatas turi būti csv, excel arba pdf.',
        ],
        'start_date' => [
            'date' => 'Pradžios data turi būti tinkama data.',
        ],
        'end_date' => [
            'date' => 'Pabaigos data turi būti tinkama data.',
            'after_or_equal' => 'Pabaigos data turi būti ne ankstesnė nei pradžios data.',
        ],
        'property_id' => [
            'exists' => 'Pasirinktas objektas neegzistuoja.',
        ],
        'month' => [
            'date_format' => 'Mėnuo turi būti YYYY-MM formatu.',
        ],
    ],

    'public' => [
        'title' => 'Ataskaitos',
        'links' => [
            'consumption' => 'Vartojimo ataskaita',
            'revenue' => 'Pajamų ataskaita',
            'outstanding' => 'Skolų ataskaita',
        ],
    ],

    'common' => [
        'title' => 'Ataskaitos',
        'export_csv' => 'Eksportuoti CSV',
        'generate_report' => 'Generuoti ataskaitą',
        'start_date' => 'Pradžios data',
        'end_date' => 'Pabaigos data',
        'building' => 'Pastatas',
        'property' => 'Objektas',
        'meter_type' => 'Skaitiklio tipas',
        'status' => 'Būsena',
        'month' => 'Mėnuo',
        'all_buildings' => 'Visi pastatai...',
        'all_properties' => 'Visi objektai...',
        'all_types' => 'Visi tipai...',
        'all_statuses' => 'Visos būsenos...',
        'na' => 'N/D',
        'invoices_count' => '{1} :count sąskaita|[2,*] :count sąskaitos',
        'readings_count' => '{1} :count rodmuo|[2,*] :count rodmenų',
    ],

    'manager' => [
        'index' => [
            'title' => 'Ataskaitos',
            'description' => 'Analitika ir įžvalgos jūsų valdomiems objektams',
            'breadcrumbs' => [
                'reports' => 'Ataskaitos',
            ],
            'stats' => [
                'properties' => 'Objektai',
                'meters' => 'Skaitikliai iš viso',
                'readings' => 'Šį mėnesį pateikta rodmenų',
                'invoices' => 'Šį mėnesį sąskaitų',
            ],
            'cards' => [
                'consumption' => [
                    'title' => 'Vartojimas',
                    'description' => 'Sekite vartojimo tendencijas pagal objektą, skaitiklio tipą ar datų intervalą',
                    'cta' => 'Žiūrėti analitiką',
                ],
                'revenue' => [
                    'title' => 'Pajamos',
                    'description' => 'Matykite išrašytas, apmokėtas ir neapmokėtas sumas laikui bėgant',
                    'cta' => 'Stebėti atsiskaitymus',
                ],
                'compliance' => [
                    'title' => 'Atitiktis',
                    'description' => 'Raskite objektus, kuriems trūksta rodmenų einamuoju laikotarpiu',
                    'cta' => 'Laikytis grafiko',
                ],
            ],
            'guide' => [
                'title' => 'Kaip naudoti šias ataskaitas',
                'items' => [
                    'consumption' => [
                        'title' => 'Vartojimas',
                        'body' => 'Lyginkite vartojimą mėnuo po mėnesio, filtruokite pagal objektą ar pastatą ir eksportuokite tendencijas tiekėjų peržiūroms. Įtraukta skaitiklių tipų skaidymas ir daugiausia vartojantys objektai.',
                    ],
                    'revenue' => [
                        'title' => 'Pajamos',
                        'body' => 'Patikrinkite sąskaitų progresą prieš uždarant laikotarpį ir pasirūpinkite, kad vėluojančios sumos būtų matomos. Stebėkite apmokėjimo rodiklius ir pajamas pagal pastatą.',
                    ],
                    'compliance' => [
                        'title' => 'Atitiktis',
                        'body' => 'Nustatykite skaitiklius be dabartinių rodmenų ir nukreipkite komandą į reikiamus objektus. Stebėkite atitikties rodiklius pagal pastatą ir skaitiklio tipą.',
                    ],
                ],
            ],
        ],

        'consumption' => [
            'title' => 'Vartojimo ataskaita',
            'breadcrumb' => 'Vartojimas',
            'description' => 'Komunalinių paslaugų vartojimas pagal objektą ir skaitiklio tipą',
            'export' => 'Eksportuoti CSV',
            'filters' => [
                'start_date' => 'Pradžios data',
                'end_date' => 'Pabaigos data',
                'building' => 'Pastatas',
                'property' => 'Objektas',
                'meter_type' => 'Skaitiklio tipas',
                'placeholders' => [
                    'building' => 'Visi pastatai...',
                    'property' => 'Visi objektai...',
                    'meter_type' => 'Visi tipai...',
                ],
                'submit' => 'Generuoti ataskaitą',
            ],
            'stats' => [
                'monthly_trend' => 'Mėnesio vartojimo dinamika',
                'top_properties' => 'Daugiausia vartojantys objektai',
                'top_caption' => 'Daugiausia vartojantys objektai',
                'total_consumption' => 'Bendras vartojimas',
                'readings' => 'Rodmenys',
                'consumption_label' => 'Vartojimas:',
                'readings_label' => 'Rodmenys:',
                'table' => [
                    'date' => 'Data',
                    'meter' => 'Skaitiklis',
                    'type' => 'Tipas',
                    'value' => 'Reikšmė',
                    'zone' => 'Zona',
                ],
                'property_caption' => 'Objekto :property rodmenys',
                'empty' => 'Pasirinktai datai duomenų nerasta.',
            ],
        ],

        'revenue' => [
            'title' => 'Pajamų ataskaita',
            'breadcrumb' => 'Pajamos',
            'description' => 'Sąskaitų pajamos pagal laikotarpį ir būseną',
            'export' => 'Eksportuoti CSV',
            'filters' => [
                'start_date' => 'Pradžios data',
                'end_date' => 'Pabaigos data',
                'building' => 'Pastatas',
                'status' => 'Būsena',
                'placeholders' => [
                    'building' => 'Visi pastatai...',
                    'status' => 'Visos būsenos...',
                ],
                'status_options' => [
                    'draft' => 'Juodraštis',
                    'finalized' => 'Užbaigta',
                    'paid' => 'Apmokėta',
                ],
                'submit' => 'Generuoti ataskaitą',
            ],
            'stats' => [
                'total' => 'Bendros pajamos',
                'paid' => 'Apmokėta',
                'payment_rate' => ':rate% apmokėjimo rodiklis',
                'finalized' => 'Užbaigta',
                'overdue' => 'Vėluojama',
            ],
            'monthly' => [
                'title' => 'Mėnesio pajamų dinamika',
                'invoices' => ':count sąskaitos',
                'paid' => '€:amount apmokėta',
            ],
            'by_building' => [
                'title' => 'Pajamos pagal pastatą',
                'caption' => 'Pajamų išskaidymas pagal pastatus',
                'headers' => [
                    'building' => 'Pastatas',
                    'revenue' => 'Bendros pajamos',
                    'invoices' => 'Sąskaitos',
                ],
                'mobile' => [
                    'revenue' => 'Pajamos:',
                    'invoices' => 'Sąskaitos:',
                ],
            ],
            'invoices' => [
                'title' => 'Sąskaitų detalizacija',
                'caption' => 'Sąskaitos šioje pajamų ataskaitoje',
                'headers' => [
                    'number' => 'Sąskaita #',
                    'property' => 'Objektas',
                    'period' => 'Laikotarpis',
                    'amount' => 'Suma',
                    'status' => 'Būsena',
                    'due' => 'Terminas',
                ],
                'empty' => 'Pasirinktam laikotarpiui sąskaitų nerasta.',
            ],
        ],

        'compliance' => [
            'title' => 'Skaitiklių rodmenų pateikimo ataskaita',
            'breadcrumb' => 'Rodmenų pateikimas',
            'description' => 'Stebėkite, kaip pateikiami rodmenys pagal objektą',
            'export' => 'Eksportuoti CSV',
            'filters' => [
                'month' => 'Mėnuo',
                'building' => 'Pastatas',
                'placeholders' => [
                    'building' => 'Visi pastatai...',
                ],
                'submit' => 'Generuoti ataskaitą',
            ],
            'summary' => [
                'title' => 'Atitikimo suvestinė',
                'complete' => [
                    'label' => 'Pateikta',
                    'description' => 'Visi skaitikliai nurašyti',
                ],
                'partial' => [
                    'label' => 'Dalinai',
                    'description' => 'Trūksta kai kurių skaitiklių',
                ],
                'none' => [
                    'label' => 'Nėra rodmenų',
                    'description' => 'Rodmenys nepateikti',
                ],
                'overall' => 'Bendras atitikimo rodiklis',
                'properties' => 'Objektai',
            ],
            'by_building' => [
                'title' => 'Atitikimas pagal pastatą',
                'properties' => ':complete / :total objektų',
            ],
            'by_meter_type' => [
                'title' => 'Atitikimas pagal skaitiklio tipą',
                'meters' => ':complete / :total skaitiklių',
            ],
            'details' => [
                'title' => 'Objektų detalės',
                'caption' => 'Objektų rodmenų atitikimas',
                'headers' => [
                    'property' => 'Objektas',
                    'building' => 'Pastatas',
                    'total_meters' => 'Iš viso skaitiklių',
                    'readings_submitted' => 'Pateikti rodmenys',
                    'status' => 'Būsena',
                    'actions' => 'Veiksmai',
                ],
                'add_readings' => 'Pridėti rodmenis',
                'mobile' => [
                    'meters' => 'Skaitikliai:',
                    'readings' => 'Rodmenys:',
                ],
            ],
        ],
    ],
];
