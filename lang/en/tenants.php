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
];
