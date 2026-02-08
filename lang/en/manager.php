<?php

declare(strict_types=1);

return [
    'pages' => [
        'index' => [
            'title' => 'Managers',
            'subtitle' => 'All managers across all organizations',
        ],
    ],
    'fields' => [
        'id' => 'ID',
        'name' => 'Name',
        'email' => 'Email',
        'properties' => 'Properties',
        'buildings' => 'Buildings',
        'invoices' => 'Invoices',
        'actions' => 'Actions',
    ],
    'profile' => [
        'title' => 'Manager Profile',
        'description' => 'Update your account details and review key portfolio metrics.',
        'alerts' => [
            'errors' => 'Please fix the highlighted fields and try again.',
        ],
        'account_information' => 'Account Information',
        'account_information_description' => 'Keep your name, email, and password up to date.',
        'labels' => [
            'currency' => 'Currency',
            'email' => 'Email',
            'language' => 'Language',
            'name' => 'Name',
        ],
        'language_description' => 'Select your preferred interface language for daily work.',
        'language_hint' => 'Language changes are applied immediately after selection.',
        'language_preference' => 'Language Preference',
        'password' => [
            'confirmation' => 'Confirm Password',
            'hint' => 'Leave password fields blank if you do not want to change it.',
            'label' => 'New Password',
        ],
        'portfolio' => [
            'title' => 'Portfolio Snapshot',
            'description' => 'A quick overview of the resources currently assigned to you.',
        ],
        'update_profile' => 'Update Profile',
    ],
];
