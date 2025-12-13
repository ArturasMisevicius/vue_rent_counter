<?php

declare(strict_types=1);

return [
    'errors' => [
        'has_properties' => 'Turi savybių',
    ],
    'labels' => [
        'address' => 'Adresas',
        'building' => 'Pastatas',
        'buildings' => 'Pastatai',
        'created_at' => 'Sukurta',
        'name' => 'Vardas',
        'property_count' => 'Turto skaičius',
        'total_apartments' => 'Iš viso butų',
    ],
    'pages' => [
        'manager_form' => [
            'actions' => [
                'cancel' => 'Atšaukti',
                'save_create' => 'Išsaugoti Sukurti',
                'save_edit' => 'Išsaugoti Redaguoti',
            ],
            'create_subtitle' => 'Sukurti subtitrus',
            'create_title' => 'Sukurti pavadinimą',
            'edit_subtitle' => 'Redaguoti subtitrus',
            'edit_title' => 'Redaguoti pavadinimą',
            'labels' => [
                'address' => 'Adresas',
                'name' => 'Vardas',
                'total_apartments' => 'Iš viso butų',
            ],
            'placeholders' => [
                'address' => 'Adresas',
                'name' => 'Vardas',
                'total_apartments' => 'Iš viso butų',
            ],
        ],
        'manager_index' => [
            'add' => 'Pridėti',
            'create_now' => 'Sukurti dabar',
            'description' => 'Aprašymas',
            'empty' => 'Tuščia',
            'headers' => [
                'actions' => 'Veiksmai',
                'building' => 'Pastatas',
                'gyvatukas' => 'Gyvatukas',
                'last_calculated' => 'Paskutinis skaičiuotas',
                'properties' => 'Savybės',
                'total_apartments' => 'Iš viso butų',
            ],
            'mobile' => [
                'apartments' => 'Butai',
                'edit' => 'Redaguoti',
                'gyvatukas' => 'Gyvatukas',
                'last' => 'Paskutinis',
                'properties' => 'Savybės',
                'view' => 'Žiūrėti',
            ],
            'never' => 'Niekada',
            'not_calculated' => 'Neapskaičiuota',
            'table_caption' => 'Lentelės antraštė',
            'title' => 'Pavadinimas',
        ],
        'manager_show' => [
            'add_property' => 'Pridėti nuosavybę',
            'calculated' => 'Apskaičiuota',
            'delete_building' => 'Ištrinti pastatą',
            'delete_confirm' => 'Ištrinti Patvirtinti',
            'description' => 'Aprašymas',
            'edit_building' => 'Redaguoti pastatą',
            'empty_properties' => 'Tuščios savybės',
            'form' => [
                'end_date' => 'Pabaigos data',
                'start_date' => 'Pradžios data',
                'submit' => 'Pateikti',
            ],
            'gyvatukas_title' => 'Gyvatukas Titulas',
            'info_title' => 'Informacijos pavadinimas',
            'labels' => [
                'address' => 'Adresas',
                'name' => 'Vardas',
                'properties_registered' => 'Įregistruotos nuosavybės',
                'total_apartments' => 'Iš viso butų',
            ],
            'last_calculated' => 'Paskutinis skaičiuotas',
            'never' => 'Niekada',
            'not_calculated' => 'Neapskaičiuota',
            'pending' => 'Laukiama',
            'properties_headers' => [
                'actions' => 'Veiksmai',
                'address' => 'Adresas',
                'area' => 'Plotas',
                'meters' => 'Metrai',
                'tenant' => 'Nuomininkas',
                'type' => 'Tipas',
            ],
            'properties_title' => 'Savybių pavadinimas',
            'status' => 'Būsena',
            'summer_average' => 'Vasaros vidurkis',
            'title' => 'Pavadinimas',
            'vacant' => 'Laisva',
            'view' => 'Žiūrėti',
        ],
        'show' => [
            'heading' => 'Antraštė',
            'title' => 'Pavadinimas',
        ],
    ],
    'validation' => [
        'address' => [
            'max' => 'Maks',
            'required' => 'Privaloma',
            'string' => 'Styga',
        ],
        'name' => [
            'max' => 'Maks',
            'required' => 'Privaloma',
            'string' => 'Styga',
        ],
        'tenant_id' => [
            'integer' => 'Sveikasis skaičius',
            'required' => 'Privaloma',
        ],
        'total_apartments' => [
            'integer' => 'Sveikasis skaičius',
            'max' => 'Maks',
            'min' => 'Min',
            'required' => 'Privaloma',
        ],
    ],
];
