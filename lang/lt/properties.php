<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Properties Language Lines (Lithuanian)
    |--------------------------------------------------------------------------
    */

    'labels' => [
        'property' => 'Nuosavybė',
        'properties' => 'Nuosavybės',
        'address' => 'Adresas',
        'type' => 'Nuosavybės tipas',
        'area' => 'Plotas (m²)',
        'current_tenant' => 'Dabartinis nuomininkas',
        'building' => 'Pastatas',
        'installed_meters' => 'Įrengti skaitikliai',
        'meters' => 'Skaitikliai',
        'created' => 'Sukurta',
    ],

    'placeholders' => [
        'address' => 'Įveskite nuosavybės adresą',
        'area' => 'Įveskite plotą kvadratiniais metrais',
    ],

    'helper_text' => [
        'address' => 'Pilnas gatvės adresas, įskaitant pastato ir buto numerį',
        'type' => 'Pasirinkite butą arba namą',
        'area' => 'Nuosavybės plotas kvadratiniais metrais (maks. 2 skaitmenys po kablelio)',
        'tenant_available' => 'Pasirinkite prieinamą nuomininką, kurį priskirsite šiai nuosavybei',
        'tenant_reassign' => 'Pasirinkite naują nuomininką arba palikite tuščią, kad išlaisvintumėte nuosavybę',
    ],

    'sections' => [
        'property_details' => 'Nuosavybės informacija',
        'property_details_description' => 'Pagrindinė informacija apie nuosavybę',
        'additional_info' => 'Papildoma informacija',
        'additional_info_description' => 'Pastato, nuomininko ir skaitiklių informacija',
    ],

    'badges' => [
        'vacant' => 'Laisva',
    ],

    'tooltips' => [
        'copy_address' => 'Spustelėkite, kad nukopijuotumėte adresą',
        'occupied_by' => 'Užimta :name',
        'no_tenant' => 'Nepriskirtas nuomininkas',
        'meters_count' => 'Įrengtų skaitiklių skaičius',
    ],

    'filters' => [
        'type' => 'Nuosavybės tipas',
        'building' => 'Pastatas',
        'occupancy' => 'Užimtumo būsena',
        'all_properties' => 'Visos nuosavybės',
        'occupied' => 'Užimta',
        'vacant' => 'Laisva',
        'large_properties' => 'Didelės nuosavybės (>100 m²)',
    ],

    'actions' => [
        'manage_tenant' => 'Valdyti nuomininką',
        'assign_tenant' => 'Priskirti nuomininką',
        'reassign_tenant' => 'Pakeisti nuomininką',
        'export_selected' => 'Eksportuoti pasirinktus',
        'add_first_property' => 'Pridėti pirmą nuosavybę',
        'add' => 'Pridėti objektą',
        'view' => 'Peržiūrėti',
        'edit' => 'Redaguoti',
    ],

    'notifications' => [
        'created' => [
            'title' => 'Nuosavybė sukurta',
            'body' => 'Nuosavybė sėkmingai sukurta.',
        ],
        'updated' => [
            'title' => 'Nuosavybė atnaujinta',
            'body' => 'Nuosavybė sėkmingai atnaujinta.',
        ],
        'deleted' => [
            'title' => 'Nuosavybė ištrinta',
            'body' => 'Nuosavybė sėkmingai ištrinta.',
        ],
        'bulk_deleted' => [
            'title' => 'Nuosavybės ištrintos',
            'body' => ':count nuosavybės sėkmingai ištrintos.',
        ],
        'tenant_assigned' => [
            'title' => 'Nuomininkas priskirtas',
            'body' => 'Nuomininkas sėkmingai priskirtas nuosavybei.',
        ],
        'tenant_removed' => [
            'title' => 'Nuomininkas pašalintas',
            'body' => 'Nuomininkas sėkmingai pašalintas iš nuosavybės.',
        ],
        'export_started' => [
            'title' => 'Eksportavimas pradėtas',
            'body' => 'Jūsų eksportavimas apdorojamas. Būsite informuotas, kai jis bus paruoštas.',
        ],
    ],

    'modals' => [
        'delete_confirmation' => 'Ar tikrai norite ištrinti šią nuosavybę? Šio veiksmo negalima atšaukti.',
    ],

    'empty_state' => [
        'heading' => 'Nėra nuosavybių',
        'description' => 'Pradėkite sukurdami savo pirmą nuosavybę.',
    ],

    'validation' => [
        'address' => [
            'required' => 'Nuosavybės adresas yra privalomas.',
            'string' => 'Nuosavybės adresas turi būti tekstas.',
            'max' => 'Nuosavybės adresas negali būti ilgesnis nei 255 simboliai.',
            'invalid_characters' => 'Adrese yra netinkamų simbolių.',
            'prohibited_content' => 'Adrese yra draudžiamo turinio.',
            'format' => 'Adrese gali būti tik raidės, skaičiai, tarpai ir įprasta skyrybos ženklai (.,#/-()).',
        ],
        'type' => [
            'required' => 'Nuosavybės tipas yra privalomas.',
            'enum' => 'Nuosavybės tipas turi būti butas arba namas.',
        ],
        'area_sqm' => [
            'required' => 'Nuosavybės plotas yra privalomas.',
            'numeric' => 'Nuosavybės plotas turi būti skaičius.',
            'min' => 'Nuosavybės plotas turi būti bent 0 kvadratinių metrų.',
            'max' => 'Nuosavybės plotas negali viršyti 10 000 kvadratinių metrų.',
            'format' => 'Plotas turi būti standartinis dešimtainis skaičius.',
            'negative' => 'Plotas negali būti neigiamas.',
            'precision' => 'Plotas gali turėti daugiausiai 2 skaitmenis po kablelio.',
        ],
        'building_id' => [
            'exists' => 'Pasirinktas pastatas neegzistuoja.',
        ],
        'tenant_id' => [
            'required' => 'Nuomininkas yra privalomas.',
            'integer' => 'Nuomininko identifikatorius turi būti skaičius.',
        ],
        'property_id' => [
            'required' => 'Reikia pasirinkti objektą.',
            'exists' => 'Pasirinktas objektas neegzistuoja.',
        ],
    ],

    'errors' => [
        'has_relations' => 'Negalima ištrinti objekto, turinčio susietų skaitiklių ar nuomininkų.',
    ],

    'manager' => [
        'index' => [
            'title' => 'Objektai',
            'description' => 'Visų jūsų portfelio objektų sąrašas.',
            'caption' => 'Objektų sąrašas',
            'filters' => [
                'search' => 'Paieška',
                'search_placeholder' => 'Ieškokite pagal adresą...',
                'type' => 'Tipas',
                'building' => 'Pastatas',
                'all_types' => 'Visi tipai',
                'all_buildings' => 'Visi pastatai',
                'filter' => 'Filtruoti',
                'clear' => 'Išvalyti',
            ],
            'headers' => [
                'address' => 'Adresas',
                'type' => 'Tipas',
                'area' => 'Plotas',
                'building' => 'Pastatas',
                'meters' => 'Skaitikliai',
                'tenants' => 'Nuomininkai',
                'actions' => 'Veiksmai',
            ],
            'empty' => [
                'text' => 'Objektų nerasta.',
                'cta' => 'Sukurkite dabar',
            ],
        ],
        'show' => [
            'title' => 'Objekto detalės',
            'description' => 'Objekto informacija ir susiję duomenys',
            'information' => 'Objekto informacija',
            'building_missing' => 'Nepriskirta pastatui',
            'current_tenant' => 'Dabartinis nuomininkas',
            'phone' => 'Telefonas',
            'no_tenant' => 'Dabartinio nuomininko nėra',
            'meters' => 'Skaitikliai',
            'add_meter' => 'Pridėti skaitiklį',
            'latest_reading' => 'Naujausias rodmuo',
            'no_meters' => 'Šiam objektui skaitiklių nėra.',
        ],
    ],

    'pages' => [
        'manager_form' => [
            'create_title' => 'Sukurti objektą',
            'create_subtitle' => 'Pridėkite naują objektą į savo portfelį',
            'breadcrumb_create' => 'Sukurti',
            'edit_title' => 'Redaguoti objektą',
            'edit_subtitle' => 'Atnaujinkite objekto informaciją',
            'breadcrumb_edit' => 'Redaguoti',
            'labels' => [
                'address' => 'Adresas',
                'type' => 'Objekto tipas',
                'area' => 'Plotas (m²)',
                'building' => 'Pastatas (nebūtina)',
            ],
            'placeholders' => [
                'address' => 'Gatvė, miestas',
                'area' => '50.00',
                'building' => 'Pasirinkite pastatą...',
            ],
            'actions' => [
                'cancel' => 'Atšaukti',
                'save_create' => 'Sukurti objektą',
                'save_edit' => 'Atnaujinti objektą',
            ],
        ],
        'manager_show' => [
            'title' => 'Objekto detalės',
            'description' => 'Objekto informacija ir susiję duomenys',
            'info_title' => 'Objekto informacija',
            'labels' => [
                'address' => 'Adresas',
                'type' => 'Tipas',
                'area' => 'Plotas',
                'building' => 'Pastatas',
            ],
            'building_missing' => 'Nepriskirta pastatui',
            'current_tenant_title' => 'Dabartinis nuomininkas',
            'tenant_labels' => [
                'name' => 'Vardas',
                'email' => 'El. paštas',
                'phone' => 'Telefonas',
            ],
            'tenant_na' => 'N/D',
            'no_tenant' => 'Dabartinio nuomininko nėra',
            'meters_title' => 'Skaitikliai',
            'add_meter' => 'Pridėti skaitiklį',
            'meters_headers' => [
                'serial' => 'Serijos numeris',
                'type' => 'Tipas',
                'installation' => 'Įrengimo data',
                'latest' => 'Naujausias rodmuo',
                'actions' => 'Veiksmai',
            ],
            'latest_none' => 'Rodmenų nėra',
            'view' => 'Peržiūrėti',
            'edit_property' => 'Redaguoti objektą',
            'delete_property' => 'Ištrinti',
            'delete_confirm' => 'Ar tikrai norite ištrinti šį objektą?',
            'no_meters_installed' => 'Šiam objektui skaitiklių nėra.',
        ],
    ],

];
