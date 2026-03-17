<?php

return [
    'subscription' => [
        'actions' => [
            'manage' => 'Gestionar suscripción',
        ],
        'limit_blocked' => [
            'properties' => [
                'title' => 'Se alcanzó el límite de propiedades',
                'body' => 'Este espacio de trabajo está usando :used de :limit propiedades del plan actual. Actualiza la suscripción antes de crear otra propiedad.',
            ],
            'tenants' => [
                'title' => 'Se alcanzó el límite de inquilinos',
                'body' => 'Este espacio de trabajo está usando :used de :limit inquilinos del plan actual. Actualiza la suscripción antes de crear otro inquilino.',
            ],
        ],
        'grace_read_only' => [
            'title' => 'Se requiere renovar la suscripción',
            'body' => 'Esta organización está dentro del período de gracia hasta :grace_ends_at. Los datos siguen visibles, pero los cambios nuevos están bloqueados hasta renovar la suscripción.',
        ],
        'post_grace_read_only' => [
            'title' => 'La suscripción expiró',
            'body' => 'El período de gracia terminó. Los datos de la organización siguen visibles, pero las acciones de escritura no estarán disponibles hasta renovar la suscripción.',
        ],
    ],
];
