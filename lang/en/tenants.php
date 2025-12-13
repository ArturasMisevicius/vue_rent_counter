<?php

declare(strict_types=1);

return [
    'actions' => [
        'add' => 'Add',
        'deactivate' => 'Deactivate',
        'reactivate' => 'Reactivate',
        'reassign' => 'Reassign',
        'view' => 'View',
    ],
    'empty' => [
        'assignment_history' => 'Assignment History',
        'list' => 'List',
        'list_cta' => 'List Cta',
        'property' => 'Property',
        'recent_invoices' => 'Recent Invoices',
        'recent_readings' => 'Recent Readings',
    ],
    'headings' => [
        'account' => 'Account',
        'assignment_history' => 'Assignment History',
        'current_property' => 'Current Property',
        'index' => 'Index',
        'index_description' => 'Index Description',
        'list' => 'List',
        'recent_invoices' => 'Recent Invoices',
        'recent_readings' => 'Recent Readings',
        'show' => 'Show',
    ],
    'labels' => [
        'actions' => 'Actions',
        'address' => 'Address',
        'area' => 'Area',
        'created' => 'Created',
        'created_by' => 'Created By',
        'email' => 'Email',
        'invoice' => 'Invoice',
        'name' => 'Name',
        'property' => 'Property',
        'reading' => 'Reading',
        'reason' => 'Reason',
        'status' => 'Status',
        'type' => 'Type',
    ],
    'pages' => [
        'admin_form' => [
            'actions' => [
                'cancel' => 'Cancel',
                'submit' => 'Submit',
            ],
            'errors_title' => 'Errors Title',
            'labels' => [
                'email' => 'Email',
                'name' => 'Name',
                'password' => 'Password',
                'password_confirmation' => 'Password Confirmation',
                'property' => 'Property',
            ],
            'notes' => [
                'credentials_sent' => 'Credentials Sent',
                'no_properties' => 'No Properties',
            ],
            'placeholders' => [
                'property' => 'Property',
            ],
            'subtitle' => 'Subtitle',
            'title' => 'Title',
        ],
        'reassign' => [
            'actions' => [
                'cancel' => 'Cancel',
                'submit' => 'Submit',
            ],
            'current_property' => [
                'empty' => 'Empty',
                'title' => 'Title',
            ],
            'errors_title' => 'Errors Title',
            'history' => [
                'empty' => 'Empty',
                'title' => 'Title',
            ],
            'new_property' => [
                'empty' => 'Empty',
                'label' => 'Label',
                'note' => 'Note',
                'placeholder' => 'Placeholder',
            ],
            'subtitle' => 'Subtitle',
            'title' => 'Title',
            'warning' => [
                'items' => [
                    'audit' => 'Audit',
                    'notify' => 'Notify',
                    'preserved' => 'Preserved',
                ],
                'title' => 'Title',
            ],
        ],
    ],
    'statuses' => [
        'active' => 'Active',
        'inactive' => 'Inactive',
    ],
    'validation' => [
        'email' => [
            'email' => 'Email',
            'max' => 'Max',
            'required' => 'Required',
        ],
        'invoice_id' => [
            'exists' => 'Exists',
            'required' => 'Required',
        ],
        'lease_end' => [
            'after' => 'After',
            'date' => 'Date',
        ],
        'lease_start' => [
            'date' => 'Date',
            'required' => 'Required',
        ],
        'name' => [
            'max' => 'Max',
            'required' => 'Required',
            'string' => 'String',
        ],
        'phone' => [
            'max' => 'Max',
            'string' => 'String',
        ],
        'property_id' => [
            'exists' => 'Exists',
            'required' => 'Required',
        ],
        'tenant_id' => [
            'integer' => 'Integer',
            'required' => 'Required',
        ],
    ],
];
