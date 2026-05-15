<?php

return [
    'subscription' => [
        'actions' => [
            'manage' => 'Manage suscripción',
        ],
        'limit_blocked' => [
            'properties' => [
                'title' => 'Propiedad limit reached',
                'body' => 'Este espacio usa :used de :limit propiedades del plan actual. Mejora la suscripción antes de crear otra propiedad.',
            ],
            'tenants' => [
                'title' => 'Inquilino limit reached',
                'body' => 'Este espacio usa :used de :limit inquilinos del plan actual. Mejora la suscripción antes de crear otro inquilino.',
            ],
        ],
        'grace_read_only' => [
            'title' => 'Suscripción renewal required',
            'body' => 'Esta organización está en periodo de gracia de renovación hasta :grace_ends_at. Los datos siguen visibles, pero las nuevas escrituras están bloqueadas hasta renovar la suscripción.',
        ],
        'post_grace_read_only' => [
            'title' => 'Suscripción vencida',
            'body' => 'El periodo de gracia de renovación terminó. Los datos de la organización siguen visibles, pero las acciones de escritura no estarán disponibles hasta renovar la suscripción.',
        ],
        'suspended' => [
            'title' => 'Suscripción suspendida',
            'body' => 'La suscripción de esta organización está suspendida. El acceso al espacio sigue bloqueado hasta restablecer la suscripción.',
        ],
    ],
];
