<?php

return [
    'subscription' => [
        'actions' => [
            'manage' => 'Manage suscripción',
        ],
        'limit_blocked' => [
            'properties' => [
                'title' => 'Propiedad limit reached',
                'body' => 'This workspace is using :used of :limit properties on the current plan. Upgrade the suscripción before creating another propiedad.',
            ],
            'tenants' => [
                'title' => 'Inquilino limit reached',
                'body' => 'This workspace is using :used of :limit inquilinos on the current plan. Upgrade the suscripción before creating another inquilino.',
            ],
        ],
        'grace_read_only' => [
            'title' => 'Suscripción renewal required',
            'body' => 'This organización is inside its renewal grace period until :grace_ends_at. Data remains visible, but new writes are blocked until the suscripción is renewed.',
        ],
        'post_grace_read_only' => [
            'title' => 'Suscripción vencido',
            'body' => 'The renewal grace period has ended. Organización data is still visible, but write actions stay unavailable until the suscripción is renewed.',
        ],
        'suspended' => [
            'title' => 'Suscripción suspendido',
            'body' => 'This organización suscripción is suspendido. Workspace access remains blocked until the suscripción is reinstated.',
        ],
    ],
];
