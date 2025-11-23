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
    ],

];
