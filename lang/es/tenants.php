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
        'list' => 'No inquilinos found',
        'list_cta' => 'Create your first inquilino',
        'property' => 'No propiedad assigned',
        'recent_invoices' => 'No recent facturas',
        'recent_readings' => 'No recent readings',
    ],
    'headings' => [
        'account' => 'Inquilino Account',
        'assignment_history' => 'Assignment History',
        'current_property' => 'Current Propiedad',
        'index' => 'Inquilinos',
        'index_description' => 'Manage inquilino accounts and propiedad assignments',
        'list' => 'Inquilino List',
        'recent_invoices' => 'Recent Facturas',
        'recent_readings' => 'Recent lecturas',
        'show' => 'Inquilino Details',
    ],
    'labels' => [
        'actions' => 'Actions',
        'address' => 'Address',
        'area' => 'Area',
        'building' => 'Edificio',
        'created' => 'Created',
        'created_by' => 'Created By',
        'email' => 'Email',
        'invoice' => 'Factura',
        'name' => 'Name',
        'phone' => 'Phone',
        'property' => 'Propiedad',
        'reading' => 'lectura',
        'reason' => 'Reason',
        'status' => 'Status',
        'type' => 'Type',
    ],
    'pages' => [
        'index' => [
            'subtitle' => 'All inquilinos across all organizacións',
            'title' => 'Inquilinos',
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
                'property' => 'Propiedad',
            ],
            'notes' => [
                'credentials_sent' => 'Inquilino login details can be sent after account creation',
                'no_properties' => 'Add a propiedad before creating a inquilino',
            ],
            'placeholders' => [
                'property' => 'Select a propiedad',
            ],
            'subtitle' => 'Create a inquilino account and assign it to a propiedad in your portfolio.',
            'title' => 'Create Inquilino',
        ],
        'reassign' => [
            'actions' => [
                'cancel' => 'Cancel',
                'submit' => 'Submit',
            ],
            'current_property' => [
                'empty' => 'No propiedad currently assigned',
                'title' => 'Current Propiedad',
            ],
            'errors_title' => 'Please fix the highlighted errors',
            'history' => [
                'empty' => 'No previous reassignments found',
                'title' => 'Reassignment History',
            ],
            'new_property' => [
                'empty' => 'No available properties',
                'label' => 'New Propiedad',
                'note' => 'Select the propiedad that should be assigned to this inquilino',
                'placeholder' => 'Select a propiedad',
            ],
            'subtitle' => 'Move this inquilino to another propiedad while preserving assignment history.',
            'title' => 'Reassign Inquilino',
            'warning' => [
                'items' => [
                    'audit' => 'This change will be recorded in the audit log.',
                    'notify' => 'The inquilino can be notified about the reassignment.',
                    'preserved' => 'Existing assignment history will be preserved.',
                ],
                'title' => 'Before you continue',
            ],
        ],
    ],
    'statuses' => [
        'active' => 'Activo',
        'inactive' => 'Inactivo',
    ],
    'sections' => [
        'details' => 'Details',
        'invoices' => 'Facturas',
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
