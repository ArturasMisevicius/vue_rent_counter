<?php

return [
    'actions' => [
        'add' => 'Add',
        'deactivate' => 'Deactivate',
        'reactivate' => 'Reactivate',
        'reassign' => 'Reassign',
        'view' => 'View',
    ],
    'empty' => [
        'assignment_history' => 'No assignment history yet',
        'list' => 'No tenants found',
        'list_cta' => 'Create your first tenant',
        'property' => 'No property assigned',
        'recent_invoices' => 'No recent invoices',
        'recent_readings' => 'No recent readings',
    ],
    'headings' => [
        'account' => 'Tenant Account',
        'assignment_history' => 'Assignment History',
        'current_property' => 'Current Property',
        'index' => 'Tenants',
        'index_description' => 'Manage tenant accounts and property assignments',
        'list' => 'Tenant List',
        'recent_invoices' => 'Recent Invoices',
        'recent_readings' => 'Recent Readings',
        'show' => 'Tenant Details',
    ],
    'labels' => [
        'actions' => 'Actions',
        'address' => 'Address',
        'area' => 'Area',
        'building' => 'Building',
        'created' => 'Created',
        'created_by' => 'Created By',
        'email' => 'Email',
        'invoice' => 'Invoice',
        'name' => 'Name',
        'phone' => 'Phone',
        'property' => 'Property',
        'reading' => 'Reading',
        'reason' => 'Reason',
        'status' => 'Status',
        'type' => 'Type',
    ],
    'pages' => [
        'index' => [
            'subtitle' => 'All tenants across all organizations',
            'title' => 'Tenants',
        ],
        'admin_form' => [
            'actions' => [
                'cancel' => 'Cancel',
                'submit' => 'Submit',
            ],
            'errors_title' => 'Please fix the highlighted errors',
            'labels' => [
                'email' => 'Email',
                'name' => 'Name',
                'password' => 'Password',
                'password_confirmation' => 'Password Confirmation',
                'property' => 'Property',
            ],
            'notes' => [
                'credentials_sent' => 'Tenant login details can be sent after account creation',
                'no_properties' => 'Add a property before creating a tenant',
            ],
            'placeholders' => [
                'property' => 'Select a property',
            ],
            'subtitle' => 'Create a tenant account and assign it to a property in your portfolio.',
            'title' => 'Create Tenant',
        ],
        'reassign' => [
            'actions' => [
                'cancel' => 'Cancel',
                'submit' => 'Submit',
            ],
            'current_property' => [
                'empty' => 'No property currently assigned',
                'title' => 'Current Property',
            ],
            'errors_title' => 'Please fix the highlighted errors',
            'history' => [
                'empty' => 'No previous reassignments found',
                'title' => 'Reassignment History',
            ],
            'new_property' => [
                'empty' => 'No available properties',
                'label' => 'New Property',
                'note' => 'Select the property that should be assigned to this tenant',
                'placeholder' => 'Select a property',
            ],
            'subtitle' => 'Move this tenant to another property while preserving assignment history.',
            'title' => 'Reassign Tenant',
            'warning' => [
                'items' => [
                    'audit' => 'This change will be recorded in the audit log.',
                    'notify' => 'The tenant can be notified about the reassignment.',
                    'preserved' => 'Existing assignment history will be preserved.',
                ],
                'title' => 'Before you continue',
            ],
        ],
    ],
    'statuses' => [
        'active' => 'Active',
        'inactive' => 'Inactive',
    ],
    'sections' => [
        'details' => 'Details',
        'invoices' => 'Invoices',
        'stats' => 'Stats',
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
