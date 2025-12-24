<?php

declare(strict_types=1);

return [
    'actions' => [
        'activate' => 'Suaktyvinti',
        'bulk_activate' => 'Masinis aktyvinimas',
        'bulk_deactivate' => 'Masinis išjungimas',
        'deactivate' => 'Išjungti',
        'set_default' => 'Nustatyti numatytąjį',
    ],
    'empty' => [
        'action' => 'Veiksmas',
        'description' => 'Aprašymas',
        'heading' => 'Antraštė',
    ],
    'errors' => [
        'cannot_deactivate_default' => 'Negalima išjungti numatytojo',
        'cannot_delete_default' => 'Negalima ištrinti numatytojo',
        'cannot_delete_last_active' => 'Negalima ištrinti paskutinio aktyvumo',
    ],
    'filters' => [
        'active_only' => 'Tik aktyvus',
        'active_placeholder' => 'Aktyvus vietos rezervavimas',
        'default_only' => 'Tik numatytasis',
        'default_placeholder' => 'Numatytoji rezervuota vieta',
        'inactive_only' => 'Tik neaktyvus',
        'non_default_only' => 'Tik ne pagal nutylėjimą',
    ],
    'helper_text' => [
        'active' => 'Aktyvus',
        'code' => 'Kodas',
        'default' => 'Numatytoji',
        'details' => 'Detalės',
        'name' => 'Vardas',
        'native_name' => 'Gimtasis vardas',
        'order' => 'Užsakyti',
    ],
    'labels' => [
        'active' => 'Aktyvus',
        'code' => 'Kodas',
        'created' => 'Sukurta',
        'default' => 'Numatytoji',
        'locale' => 'Lokalė',
        'name' => 'Vardas',
        'native_name' => 'Gimtasis vardas',
        'order' => 'Užsakyti',
    ],
    'messages' => [
        'code_copied' => 'Kodas nukopijuotas',
    ],
    'modals' => [
        'delete' => [
            'description' => 'Aprašymas',
            'heading' => 'Antraštė',
        ],
        'set_default' => [
            'description' => 'Aprašymas',
            'heading' => 'Antraštė',
        ],
    ],
    'navigation' => 'Kalbos',
    'notifications' => [
        'default_set' => 'Numatytasis rinkinys',
    ],
    'placeholders' => [
        'code' => 'Kodas',
        'name' => 'Vardas',
        'native_name' => 'Gimtasis vardas',
    ],
    'sections' => [
        'details' => 'Detalės',
        'settings' => 'Nustatymai',
    ],
    'validation' => [
        'code_format' => 'Kodo formatas',
        'locale' => [
            'max' => 'Maks',
            'required' => 'Privaloma',
            'string' => 'Styga',
        ],
    ],
];
