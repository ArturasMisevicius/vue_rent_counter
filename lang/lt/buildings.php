<?php

declare(strict_types=1);

return [
    'labels' => [
        'building' => 'Pastatas',
        'buildings' => 'Pastatai',
        'name' => 'Pastato pavadinimas',
        'address' => 'Adresas',
        'total_apartments' => 'Bendras butų skaičius',
        'total_area' => 'Bendras plotas',
        'property_count' => 'Objektų skaičius',
        'created_at' => 'Sukurta',
    ],

    'validation' => [
        'tenant_id' => [
            'required' => 'Nuomininkas yra privalomas.',
            'integer' => 'Nuomininko identifikatorius turi būti skaičius.',
        ],
        'address' => [
            'required' => 'Pastato adresas yra privalomas.',
            'string' => 'Pastato adresas turi būti tekstas.',
            'max' => 'Pastato adresas negali būti ilgesnis nei 255 simboliai.',
        ],
        'total_apartments' => [
            'required' => 'Būtina nurodyti butų skaičių.',
            'numeric' => 'Butų skaičius turi būti sveikas skaičius.',
            'integer' => 'Butų skaičius turi būti sveikas skaičius.',
            'min' => 'Pastatas turi turėti bent 1 butą.',
            'max' => 'Pastatas negali turėti daugiau nei 1 000 butų.',
        ],
        'total_area' => [
            'required' => 'Bendras plotas yra privalomas.',
            'numeric' => 'Bendras plotas turi būti skaičius.',
            'min' => 'Bendras plotas turi būti ne mažesnis kaip 0.',
        ],
        'gyvatukas' => [
            'start_date' => [
                'required' => 'Pradžios data reikalinga gyvatukas skaičiavimui.',
                'date' => 'Pradžios data turi būti tinkama data.',
            ],
            'end_date' => [
                'required' => 'Pabaigos data reikalinga gyvatukas skaičiavimui.',
                'date' => 'Pabaigos data turi būti tinkama data.',
                'after' => 'Pabaigos data turi būti po pradžios datos.',
            ],
        ],
    ],

    'errors' => [
        'has_properties' => 'Negalima ištrinti pastato, turinčio susietų objektų.',
    ],

    'pages' => [
        'show' => [
            'title' => 'Pastato informacija',
            'heading' => 'Pastato informacija',
        ],
        'manager_index' => [
            'title' => 'Pastatai',
            'description' => 'Daugiabučiai pastatai su gyvatuko skaičiavimais',
            'add' => 'Pridėti pastatą',
            'table_caption' => 'Pastatų sąrašas',
            'headers' => [
                'building' => 'Pastatas',
                'total_apartments' => 'Butai',
                'properties' => 'Objektai',
                'gyvatukas' => 'Gyvatuko vidurkis',
                'last_calculated' => 'Paskutinis skaičiavimas',
                'actions' => 'Veiksmai',
            ],
            'not_calculated' => 'Neskaičiuota',
            'never' => 'Niekada',
            'empty' => 'Pastatų nerasta.',
            'create_now' => 'Sukurti dabar',
            'mobile' => [
                'apartments' => 'Butai:',
                'properties' => 'Objektai:',
                'gyvatukas' => 'Gyvatukas:',
                'last' => 'Paskutinis:',
                'view' => 'Peržiūrėti',
                'edit' => 'Redaguoti',
            ],
        ],
        'manager_show' => [
            'title' => 'Pastato detalės',
            'description' => 'Pastato informacija ir gyvatuko skaičiavimai',
            'info_title' => 'Pastato informacija',
            'labels' => [
                'name' => 'Pastato pavadinimas',
                'address' => 'Adresas',
                'total_apartments' => 'Butai',
                'properties_registered' => 'Registruoti objektai',
            ],
            'gyvatukas_title' => 'Gyvatukas (ciruliacijos mokestis)',
            'summer_average' => 'Vasaros vidurkis',
            'last_calculated' => 'Paskutinis skaičiavimas',
            'status' => 'Būsena',
            'calculated' => 'Apskaičiuota',
            'pending' => 'Laukiama',
            'not_calculated' => 'Neskaičiuota',
            'never' => 'Niekada',
            'form' => [
                'start_date' => 'Pradžios data',
                'end_date' => 'Pabaigos data',
                'submit' => 'Skaičiuoti vasaros vidurkį',
            ],
            'properties_title' => 'Objektai pastate',
            'add_property' => 'Pridėti objektą',
            'properties_headers' => [
                'address' => 'Adresas',
                'type' => 'Tipas',
                'area' => 'Plotas',
                'meters' => 'Skaitikliai',
                'tenant' => 'Nuomininkas',
                'actions' => 'Veiksmai',
            ],
            'vacant' => 'Laisvas',
            'view' => 'Peržiūrėti',
            'edit_building' => 'Redaguoti pastatą',
            'delete_building' => 'Ištrinti',
            'delete_confirm' => 'Ar tikrai norite ištrinti šį pastatą?',
            'empty_properties' => 'Šiame pastate nėra registruotų objektų.',
        ],

        'manager_form' => [
            'create_title' => 'Sukurti pastatą',
            'create_subtitle' => 'Pridėkite naują daugiabutį pastatą',
            'edit_title' => 'Redaguoti pastatą',
            'edit_subtitle' => 'Atnaujinkite pastato informaciją',
            'breadcrumb_create' => 'Sukurti',
            'breadcrumb_edit' => 'Redaguoti',
            'labels' => [
                'name' => 'Pastato pavadinimas',
                'address' => 'Adresas',
                'total_apartments' => 'Butai',
            ],
            'placeholders' => [
                'name' => 'Gedimino 15',
                'address' => 'Gatvė, miestas',
                'total_apartments' => '10',
            ],
            'actions' => [
                'cancel' => 'Atšaukti',
                'save_create' => 'Sukurti pastatą',
                'save_edit' => 'Atnaujinti pastatą',
            ],
        ],
    ],
];
