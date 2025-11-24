<?php

declare(strict_types=1);

return [
    'profile' => [
        'title' => 'My Profile',
        'description' => 'View your account information and preferences',
        'account_information' => 'Account Information',
        'language_preference' => 'Language Preference',
        'labels' => [
            'name' => 'Name',
            'email' => 'Email',
            'role' => 'Role',
            'created' => 'Account Created',
            'address' => 'Address',
            'type' => 'Property Type',
            'area' => 'Area',
            'building' => 'Building',
            'organization' => 'Organization',
            'contact_name' => 'Contact Name',
        ],
        'language' => [
            'select' => 'Select Language',
            'note' => 'Your language preference will be saved automatically',
        ],
        'assigned_property' => 'Assigned Property',
        'manager_contact' => [
            'title' => 'Property Manager Contact',
            'description' => 'If you have questions or need assistance, please reach out to your property manager.',
        ],
    ],

    'property' => [
        'title' => 'My Property',
        'description' => 'Details about your assigned property and its utility meters.',
        'no_property_title' => 'No Property Assigned',
        'no_property_body' => 'You do not have a property assigned yet. Please contact your administrator.',
        'info_title' => 'Property Information',
        'labels' => [
            'address' => 'Address',
            'type' => 'Property Type',
            'area' => 'Area',
            'building' => 'Building',
            'building_address' => 'Building Address',
            'serial' => 'Serial:',
        ],
        'meters_title' => 'Utility Meters',
        'meters_description' => 'Meters installed for this property.',
        'meter_status' => 'Active',
        'view_details' => 'View Details',
        'no_meters' => 'No meters have been installed for this property yet.',
    ],

    'meters' => [
        'index_title' => 'My Meters',
        'index_description' => 'Utility meters for your assigned property',
        'empty_title' => 'No meters installed',
        'empty_body' => 'Your property does not have any meters assigned yet. Please contact your manager if you think this is a mistake.',
        'list_title' => 'Meters',
        'list_description' => 'Tap a meter to view its detailed history and recent readings.',
        'labels' => [
            'type' => 'Type',
            'serial' => 'Serial',
            'latest' => 'Latest',
            'updated' => 'Updated',
            'not_recorded' => 'Not recorded',
            'day_night' => 'Day & night',
            'single_zone' => 'Single zone',
        ],
        'status_active' => 'Active',
        'view_history' => 'View History',
        'all_readings' => 'All Readings',
        'back' => 'Back to My Meters',
        'show_title' => 'Meter :serial',
        'show_description' => 'Tracking your :type usage for :property',
        'view_all_readings' => 'View All Readings',
        'overview' => [
            'title' => 'Meter health',
            'active' => 'Active meters',
            'zones' => 'Day/night capable',
            'zones_hint' => 'Meters that support day/night readings.',
            'latest_update' => 'Latest update',
            'no_readings' => 'Awaiting first reading',
            'recency_hint' => 'Latest recorded reading date across all meters.',
        ],
    ],
];
