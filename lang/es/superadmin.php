<?php

return [
    'organizations' => [
        'singular' => 'Organización',
        'plural' => 'Organizaciones',
        'sections' => [
            'profile' => 'Perfil de la organización',
            'activity' => 'Resumen de actividad',
        ],
        'columns' => [
            'name' => 'Nombre',
            'slug' => 'Slug',
            'status' => 'Estado',
            'owner' => 'Propietario',
            'owner_email' => 'Correo del propietario',
            'users_count' => 'Usuarios',
            'properties_count' => 'Propiedades',
            'subscriptions_count' => 'Suscripciones',
            'created_at' => 'Creado',
            'updated_at' => 'Actualizado',
        ],
        'empty' => [
            'owner' => 'Sin propietario asignado',
        ],
        'status' => [
            'active' => 'Activa',
            'suspended' => 'Suspendida',
        ],
    ],
];
