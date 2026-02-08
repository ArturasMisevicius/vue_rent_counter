<?php

declare(strict_types=1);

return [
    'resource' => [
        'label' => 'Žyma',
        'plural_label' => 'Žymos',
        'navigation_label' => 'Žymos',
    ],
    'fields' => [
        'name' => 'Pavadinimas',
        'slug' => 'Nuoroda',
        'color' => 'Spalva',
        'description' => 'Aprašymas',
        'type' => 'Tipas',
        'order' => 'Eilė',
    ],
    'types' => [
        'general' => 'Bendras',
        'property' => 'Turtas',
        'tenant' => 'Nuomininkas',
        'invoice' => 'Sąskaita faktūra',
    ],
    'actions' => [
        'create' => 'Sukurti žymą',
        'edit' => 'Redaguoti žymą',
        'delete' => 'Ištrinti žymą',
        'view' => 'Peržiūrėti žymą',
    ],
    'messages' => [
        'created' => 'Žyma sukurta sėkmingai.',
        'updated' => 'Žyma atnaujinta sėkmingai.',
        'deleted' => 'Žyma ištrinta sėkmingai.',
    ],
    'labels' => [
        'description' => 'Aprašymas',
        'name' => 'Pavadinimas',
        'color' => 'Spalva',
        'type' => 'Tipas',
    ],
];
