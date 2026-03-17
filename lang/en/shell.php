<?php

return [
    'search' => [
        'label' => 'Global search',
        'placeholder' => 'Search anything',
        'groups' => [
            'platform' => 'Platform',
            'organization' => 'Organization',
            'tenant' => 'Tenant',
        ],
        'empty' => [
            'heading' => 'No results yet',
            'body' => 'Search results will appear here when matching routes and records exist.',
        ],
    ],
    'navigation' => [
        'groups' => [
            'platform' => 'Platform',
            'organization' => 'Organization',
            'account' => 'Account',
        ],
        'items' => [
            'profile' => 'Profile',
        ],
    ],
    'roles' => [
        'superadmin' => 'Superadmin',
        'admin' => 'Admin',
        'manager' => 'Manager',
        'tenant' => 'Tenant',
    ],
    'profile' => [
        'title' => 'My Profile',
        'eyebrow' => 'Account Space',
        'heading' => 'My Profile',
        'description' => 'Review your account identity, preferred language, and signed-in context from one shared destination.',
    ],
    'actions' => [
        'back_to_dashboard' => 'Back to dashboard',
    ],
    'impersonation' => [
        'eyebrow' => 'Impersonation active',
        'heading' => 'You are impersonating this account',
        'actions' => [
            'stop' => 'Stop impersonating',
        ],
    ],
    'errors' => [
        'eyebrow' => 'Error :status',
        '403' => [
            'title' => 'You do not have permission to view this page',
            'description' => 'Your account does not currently have access to this area. If you believe this is a mistake, contact your administrator or return to the correct dashboard.',
        ],
        '404' => [
            'title' => 'The page you are looking for does not exist',
            'description' => 'The link may be outdated, incomplete, or no longer available. Return to your dashboard to continue working safely.',
        ],
        '500' => [
            'title' => 'Something went wrong on our side',
            'description' => 'We could not complete that request right now. Please try again in a moment or contact support if the problem continues.',
        ],
    ],
    'notifications' => [
        'heading' => 'Notifications',
        'unread_count' => '{0} No unread notifications|{1} :count unread notification|[2,*] :count unread notifications',
        'actions' => [
            'toggle' => 'Toggle notifications',
            'mark_all_read' => 'Mark all as read',
        ],
        'empty' => [
            'heading' => 'No notifications yet',
            'body' => 'New updates will appear here when the product has something to share.',
        ],
        'defaults' => [
            'title' => 'Notification',
            'body' => 'Notification details are available.',
            'just_now' => 'just now',
        ],
    ],
];
