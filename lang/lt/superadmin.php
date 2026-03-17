<?php

return [
    'organizations' => [
        'singular' => 'Organizacija',
        'plural' => 'Organizacijos',
        'sections' => [
            'profile' => 'Organizacijos profilis',
            'activity' => 'Veiklos santrauka',
        ],
        'columns' => [
            'name' => 'Pavadinimas',
            'slug' => 'Slug',
            'status' => 'Būsena',
            'owner' => 'Savininkas',
            'owner_email' => 'Savininko el. paštas',
            'users_count' => 'Naudotojai',
            'properties_count' => 'Objektai',
            'subscriptions_count' => 'Prenumeratos',
            'created_at' => 'Sukurta',
            'updated_at' => 'Atnaujinta',
        ],
        'empty' => [
            'owner' => 'Savininkas nepriskirtas',
        ],
        'status' => [
            'active' => 'Aktyvi',
            'suspended' => 'Sustabdyta',
        ],
    ],
];
