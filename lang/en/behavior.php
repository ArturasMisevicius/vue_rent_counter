<?php

return [
    'subscription' => [
        'actions' => [
            'manage' => 'Manage subscription',
        ],
        'limit_blocked' => [
            'properties' => [
                'title' => 'Property limit reached',
                'body' => 'This workspace is using :used of :limit properties on the current plan. Upgrade the subscription before creating another property.',
            ],
            'tenants' => [
                'title' => 'Tenant limit reached',
                'body' => 'This workspace is using :used of :limit tenants on the current plan. Upgrade the subscription before creating another tenant.',
            ],
        ],
        'grace_read_only' => [
            'title' => 'Subscription renewal required',
            'body' => 'This organization is inside its renewal grace period until :grace_ends_at. Data remains visible, but new writes are blocked until the subscription is renewed.',
        ],
        'post_grace_read_only' => [
            'title' => 'Subscription expired',
            'body' => 'The renewal grace period has ended. Organization data is still visible, but write actions stay unavailable until the subscription is renewed.',
        ],
    ],
];
