<?php

return [
    'actions' => [
        'add' => 'Pridėti',
        'add_first_property' => 'Pridėti pirmą turtą',
        'assign_tenant' => 'Priskirti nuomininką',
        'edit' => 'Redaguoti',
        'export_selected' => 'Eksportuoti pasirinktus',
        'manage_tenant' => 'Tvarkyti nuomininką',
        'reassign_tenant' => 'Perskirti nuomininką',
        'view' => 'Peržiūrėti',
    ],
    'badges' => [
        'vacant' => 'Laisvas',
    ],
    'empty_state' => [
        'description' => 'Aprašymas',
        'heading' => 'Antraštė',
    ],
    'errors' => [
        'has_relations' => 'Turi santykių',
    ],
    'filters' => [
        'all_properties' => 'Visi turtai',
        'building' => 'Pastatas',
        'large_properties' => 'Didelės Savybės',
        'occupancy' => 'Užimtumas',
        'occupied' => 'Užimtas',
        'type' => 'Tipas',
        'vacant' => 'Laisvas',
        'tags' => 'Žymos',
    ],
    'helper_text' => [
        'address' => 'Adresas',
        'area' => 'Plotas',
        'tenant_available' => 'Yra nuomininkas',
        'tenant_reassign' => 'Perskirti nuomininkui',
        'type' => 'Tipas',
        'tags' => 'Pasirinkite šio ištekliaus žymas',
    ],
    'labels' => [
        'address' => 'Adresas',
        'area' => 'Plotas',
        'building' => 'Pastatas',
        'created' => 'Sukurta',
        'current_tenant' => 'Dabartinis nuomininkas',
        'installed_meters' => 'Sumontuoti skaitikliai',
        'meters' => 'Skaitikliai',
        'properties' => 'Turtai',
        'property' => 'Turtas',
        'type' => 'Tipas',
        'tags' => 'Žymos',
    ],
    'manager' => [
        'index' => [
            'caption' => 'Antraštė',
            'description' => 'Aprašymas',
            'empty' => [
                'cta' => 'Cta',
                'text' => 'Tekstas',
            ],
            'filters' => [
                'all_buildings' => 'Visi pastatai',
                'all_types' => 'Visi tipai',
                'building' => 'Pastatas',
                'clear' => 'Išvalyti',
                'filter' => 'Filtruoti',
                'search' => 'Ieškoti',
                'search_placeholder' => 'Ieškoti vietos rezervavimo',
                'type' => 'Tipas',
            ],
            'headers' => [
                'actions' => 'Veiksmai',
                'address' => 'Adresas',
                'area' => 'Plotas',
                'building' => 'Pastatas',
                'meters' => 'Skaitikliai',
                'tenants' => 'Nuomininkai',
                'type' => 'Tipas',
            ],
            'title' => 'Pavadinimas',
        ],
    ],
    'modals' => [
        'bulk_delete' => [
            'confirm' => 'Patvirtinti',
            'description' => 'Aprašymas',
            'title' => 'Pavadinimas',
        ],
        'delete_confirmation' => 'Ištrinti patvirtinimą',
    ],
    'notifications' => [
        'bulk_deleted' => [
            'body' => 'Kūnas',
            'title' => 'Pavadinimas',
        ],
        'created' => [
            'body' => 'Kūnas',
            'title' => 'Pavadinimas',
        ],
        'deleted' => [
            'body' => 'Kūnas',
            'title' => 'Pavadinimas',
        ],
        'export_started' => [
            'body' => 'Kūnas',
            'title' => 'Pavadinimas',
        ],
        'updated' => [
            'body' => 'Kūnas',
            'title' => 'Pavadinimas',
        ],
        '{$action}' => [
            'body' => 'Kūnas',
            'title' => 'Pavadinimas',
        ],
    ],
    'pages' => [
        'superadmin_index' => [
            'description' => 'Visos nuosavybės visose organizacijose',
        ],
        'superadmin_show' => [
            'sections' => [
                'invoices_description' => 'Sąskaitos faktūros šio turto nuomininkams',
                'meters_description' => 'Šiam turtui priskirti skaitikliai',
                'property_details' => 'Informacija apie nuosavybę',
            ],
        ],
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
                'area' => 'Plotas',
                'building' => 'Pastatas',
                'type' => 'Tipas',
            ],
            'placeholders' => [
                'address' => 'Adresas',
                'area' => 'Plotas',
                'building' => 'Pastatas',
            ],
        ],
        'manager_show' => [
            'add_meter' => 'Pridėti matuoklį',
            'building_missing' => 'Trūksta pastato',
            'current_tenant_title' => 'Dabartinis nuomininko titulas',
            'delete_confirm' => 'Ištrinti Patvirtinti',
            'delete_property' => 'Ištrinti nuosavybę',
            'description' => 'Aprašymas',
            'edit_property' => 'Redaguoti nuosavybę',
            'info_title' => 'Informacijos pavadinimas',
            'labels' => [
                'address' => 'Adresas',
                'area' => 'Plotas',
                'building' => 'Pastatas',
                'type' => 'Tipas',
            ],
            'latest_none' => 'Naujausias Nėra',
            'meters_headers' => [
                'actions' => 'Veiksmai',
                'installation' => 'Montavimas',
                'latest' => 'Naujausias',
                'serial' => 'Serialas',
                'type' => 'Tipas',
            ],
            'meters_title' => 'Metrai Pavadinimas',
            'no_meters_installed' => 'Skaitikliai neįdiegti',
            'no_tenant' => 'Nėra nuomininko',
            'tenant_labels' => [
                'email' => 'El. paštas',
                'name' => 'Vardas',
                'phone' => 'Telefonas',
            ],
            'tenant_na' => 'Nuomininkas Na',
            'title' => 'Pavadinimas',
            'view' => 'Peržiūrėti',
        ],
    ],
    'placeholders' => [
        'address' => 'Adresas',
        'area' => 'Plotas',
    ],
    'sections' => [
        'additional_info' => 'Papildoma informacija',
        'additional_info_description' => 'Papildomos informacijos aprašymas',
        'property_details' => 'Informacija apie nuosavybę',
        'property_details_description' => 'Išsamios nuosavybės aprašymas',
    ],
    'tooltips' => [
        'copy_address' => 'Kopijuoti adresą',
        'meters_count' => 'Metrų skaičius',
        'no_tenant' => 'Nėra nuomininko',
        'occupied_by' => 'Užimtas',
    ],
    'validation' => [
        'address' => [
            'format' => 'Formatas',
            'invalid_characters' => 'Netinkami simboliai',
            'max' => 'Maks',
            'prohibited_content' => 'Draudžiamas turinys',
            'required' => 'Privaloma',
            'string' => 'Styga',
        ],
        'area_sqm' => [
            'format' => 'Formatas',
            'max' => 'Maks',
            'min' => 'Min',
            'negative' => 'Neigiamas',
            'numeric' => 'Skaitinis',
            'precision' => 'Tikslumas',
            'required' => 'Privaloma',
        ],
        'building_id' => [
            'exists' => 'Egzistuoja',
        ],
        'property_id' => [
            'exists' => 'Egzistuoja',
            'required' => 'Privaloma',
        ],
        'tenant_id' => [
            'integer' => 'Sveikasis skaičius',
            'required' => 'Privaloma',
        ],
        'type' => [
            'enum' => 'Enum',
            'required' => 'Privaloma',
        ],
    ],
];
