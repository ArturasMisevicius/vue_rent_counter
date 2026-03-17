<?php

return [
    'organizations' => [
        'singular' => 'Organization',
        'plural' => 'Organizations',
        'sections' => [
            'profile' => 'Organization profile',
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
            'subscriptions_count' => 'Subscriptions',
            'created_at' => 'Created',
            'updated_at' => 'Updated',
        ],
        'empty' => [
            'owner' => 'No owner assigned',
        ],
        'status' => [
            'active' => 'Active',
            'suspended' => 'Suspended',
        ],
    ],
];
