<?php

declare(strict_types=1);

return [
    'labels' => [
        'provider' => 'Provider',
        'providers' => 'Providers',
        'name' => 'Provider Name',
        'service_type' => 'Service Type',
        'contact_info' => 'Contact Information',
        'tariffs' => 'Tariffs',
        'created' => 'Created',
        'no_contact_info' => 'No contact info',
    ],

    'headings' => [
        'index' => 'Providers Management',
        'create' => 'Create Provider',
        'edit' => 'Edit Provider',
        'show' => 'Provider Details',
        'information' => 'Provider Information',
        'associated_tariffs' => 'Associated Tariffs',
        'quick_actions' => 'Quick Actions',
    ],

    'descriptions' => [
        'index' => 'Manage utility service providers',
        'create' => 'Add a new utility service provider',
        'edit' => 'Update provider information',
        'show' => 'View provider information and associated tariffs',
    ],

    'actions' => [
        'add' => 'Add Provider',
        'create' => 'Create Provider',
        'edit' => 'Edit Provider',
        'update' => 'Update Provider',
        'delete' => 'Delete Provider',
        'view' => 'View',
        'back' => 'Back to List',
        'cancel' => 'Cancel',
        'add_tariff' => 'Add Tariff',
    ],

    'sections' => [
        'provider_information' => 'Provider Information',
        'contact_information' => 'Contact Information',
    ],

    'forms' => [
        'contact' => [
            'field' => 'Field',
            'value' => 'Value',
            'add' => 'Add Contact Field',
            'helper' => 'Add contact information such as phone, email, address, website, etc.',
        ],
    ],

    'tables' => [
        'name' => 'Name',
        'service_type' => 'Service Type',
        'tariffs' => 'Tariffs',
        'contact_info' => 'Contact Info',
        'actions' => 'Actions',
        'active_from' => 'Active From',
        'active_until' => 'Active Until',
        'status' => 'Status',
        'tariff_count' => 'Tariff Count',
        'created_at' => 'Created At',
    ],

    'statuses' => [
        'active' => 'Active',
        'inactive' => 'Inactive',
        'present' => 'Present',
        'not_available' => 'N/A',
    ],


    'counts' => [
        'tariffs' => '{0} No tariffs|{1} :count tariff|[2,*] :count tariffs',
    ],

    'empty' => [
        'providers' => 'No providers found.',
        'tariffs' => 'No tariffs associated with this provider.',
    ],

    'notifications' => [
        'created' => 'Provider created successfully.',
        'updated' => 'Provider updated successfully.',
        'deleted' => 'Provider deleted successfully.',
        'cannot_delete' => 'Cannot delete provider with associated tariffs.',
    ],

    'confirmations' => [
        'delete' => 'Are you sure you want to delete this provider?',
    ],

    'validation' => [
        'name' => [
            'required' => 'Provider name is required.',
            'string' => 'Provider name must be text.',
            'max' => 'Provider name may not be greater than 255 characters.',
        ],
        'service_type' => [
            'required' => 'Service type is required.',
            'in' => 'Service type must be electricity, water, or heating.',
        ],
        'contact_info' => [
            'string' => 'Contact information must be text.',
        ],
    ],
];
