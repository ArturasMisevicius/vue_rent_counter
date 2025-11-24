<?php

declare(strict_types=1);

return [
    'headings' => [
        'index' => 'Tenant Accounts',
        'index_description' => 'Manage tenant accounts and property assignments',
        'list' => 'Tenant accounts list',
        'show' => 'Tenant Details',
        'account' => 'Account Information',
        'current_property' => 'Current Property',
        'assignment_history' => 'Assignment History',
        'recent_readings' => 'Recent Meter Readings',
        'recent_invoices' => 'Recent Invoices',
    ],

    'actions' => [
        'deactivate' => 'Deactivate',
        'reactivate' => 'Reactivate',
        'reassign' => 'Reassign Property',
        'add' => 'Add Tenant',
        'view' => 'View',
    ],

    'labels' => [
        'name' => 'Name',
        'status' => 'Status',
        'email' => 'Email',
        'created' => 'Created',
        'created_by' => 'Created By',
        'address' => 'Address',
        'type' => 'Type',
        'area' => 'Area',
        'reading' => 'Reading',
        'invoice' => 'Invoice #:id',
        'reason' => 'Reason',
        'property' => 'Property',
        'actions' => 'Actions',
    ],

    'statuses' => [
        'active' => 'Active',
        'inactive' => 'Inactive',
    ],

    'empty' => [
        'property' => 'No property assigned',
        'assignment_history' => 'No assignment history available',
        'recent_readings' => 'No recent readings',
        'recent_invoices' => 'No recent invoices',
        'list' => 'No tenant accounts found.',
        'list_cta' => 'Create your first tenant',
    ],

    'pages' => [
        'admin_form' => [
            'title' => 'Create Tenant Account',
            'subtitle' => 'Add a new tenant account and assign to a property',
            'breadcrumb' => 'Create',
            'errors_title' => 'There were errors with your submission',
            'labels' => [
                'name' => 'Full Name',
                'email' => 'Email Address',
                'password' => 'Password',
                'password_confirmation' => 'Confirm Password',
                'property' => 'Assign to Property',
            ],
            'placeholders' => [
                'property' => 'Select a property',
            ],
            'notes' => [
                'credentials_sent' => 'Login credentials will be sent to this email',
                'no_properties' => 'No properties available. Please create a property first.',
            ],
            'actions' => [
                'cancel' => 'Cancel',
                'submit' => 'Create Tenant',
            ],
        ],
        'reassign' => [
            'title' => 'Reassign Tenant to Different Property',
            'subtitle' => 'Move :name to a different property in your portfolio',
            'breadcrumb' => 'Reassign',
            'errors_title' => 'There were errors with your submission',
            'current_property' => [
                'title' => 'Current Property',
                'empty' => 'No property currently assigned',
            ],
            'new_property' => [
                'label' => 'New Property',
                'placeholder' => 'Select a property',
                'empty' => 'No other properties available for reassignment.',
                'note' => 'Select the property to reassign this tenant to',
            ],
            'warning' => [
                'title' => 'Important Information',
                'items' => [
                    'preserved' => 'All historical meter readings and invoices will be preserved',
                    'notify' => 'The tenant will be notified via email about the reassignment',
                    'audit' => 'This action will be logged in the audit trail',
                ],
            ],
            'history' => [
                'title' => 'Reassignment History',
                'empty' => 'Previous property assignments will be displayed here after reassignment',
            ],
            'actions' => [
                'cancel' => 'Cancel',
                'submit' => 'Reassign Tenant',
            ],
        ],
    ],
];
