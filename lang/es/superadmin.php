<?php

return [
    'organizations' => [
        'singular' => 'Organización',
        'plural' => 'Organizacións',
        'sections' => [
            'profile' => 'Organización profile',
            'activity' => 'Activity snapshot',
        ],
        'columns' => [
            'name' => 'Name',
            'slug' => 'Slug',
            'status' => 'Status',
            'owner' => 'Owner',
            'owner_email' => 'Owner email',
            'users_count' => 'Users',
            'properties_count' => 'Properties',
            'subscriptions_count' => 'Suscripcións',
            'created_at' => 'Created',
            'updated_at' => 'Updated',
        ],
        'empty' => [
            'owner' => 'No owner assigned',
        ],
        'status' => [
            'active' => 'Activo',
            'suspended' => 'Suspendido',
        ],
    ],
];
