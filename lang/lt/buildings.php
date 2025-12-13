<?php

declare(strict_types=1);

return [
    'errors' => [
        'has_properties' => 'Has Properties',
    ],
    'labels' => [
        'address' => 'Address',
        'building' => 'Building',
        'buildings' => 'Buildings',
        'created_at' => 'Created At',
        'name' => 'Name',
        'property_count' => 'Property Count',
        'total_apartments' => 'Total Apartments',
    ],
    'pages' => [
        'manager_form' => [
            'actions' => [
                'cancel' => 'Cancel',
                'save_create' => 'Save Create',
                'save_edit' => 'Save Edit',
            ],
            'create_subtitle' => 'Create Subtitle',
            'create_title' => 'Create Title',
            'edit_subtitle' => 'Edit Subtitle',
            'edit_title' => 'Edit Title',
            'labels' => [
                'address' => 'Address',
                'name' => 'Name',
                'total_apartments' => 'Total Apartments',
            ],
            'placeholders' => [
                'address' => 'Address',
                'name' => 'Name',
                'total_apartments' => 'Total Apartments',
            ],
        ],
        'manager_index' => [
            'add' => 'Add',
            'create_now' => 'Create Now',
            'description' => 'Description',
            'empty' => 'Empty',
            'headers' => [
                'actions' => 'Actions',
                'building' => 'Building',
                'gyvatukas' => 'Gyvatukas',
                'last_calculated' => 'Last Calculated',
                'properties' => 'Properties',
                'total_apartments' => 'Total Apartments',
            ],
            'mobile' => [
                'apartments' => 'Apartments',
                'edit' => 'Edit',
                'gyvatukas' => 'Gyvatukas',
                'last' => 'Last',
                'properties' => 'Properties',
                'view' => 'View',
            ],
            'never' => 'Never',
            'not_calculated' => 'Not Calculated',
            'table_caption' => 'Table Caption',
            'title' => 'Title',
        ],
        'manager_show' => [
            'add_property' => 'Add Property',
            'calculated' => 'Calculated',
            'delete_building' => 'Delete Building',
            'delete_confirm' => 'Delete Confirm',
            'description' => 'Description',
            'edit_building' => 'Edit Building',
            'empty_properties' => 'Empty Properties',
            'form' => [
                'end_date' => 'End Date',
                'start_date' => 'Start Date',
                'submit' => 'Submit',
            ],
            'gyvatukas_title' => 'Gyvatukas Title',
            'info_title' => 'Info Title',
            'labels' => [
                'address' => 'Address',
                'name' => 'Name',
                'properties_registered' => 'Properties Registered',
                'total_apartments' => 'Total Apartments',
            ],
            'last_calculated' => 'Last Calculated',
            'never' => 'Never',
            'not_calculated' => 'Not Calculated',
            'pending' => 'Pending',
            'properties_headers' => [
                'actions' => 'Actions',
                'address' => 'Address',
                'area' => 'Area',
                'meters' => 'Meters',
                'tenant' => 'Tenant',
                'type' => 'Type',
            ],
            'properties_title' => 'Properties Title',
            'status' => 'Status',
            'summer_average' => 'Summer Average',
            'title' => 'Title',
            'vacant' => 'Vacant',
            'view' => 'View',
        ],
        'show' => [
            'heading' => 'Heading',
            'title' => 'Title',
        ],
    ],
    'validation' => [
        'address' => [
            'max' => 'Max',
            'required' => 'Required',
            'string' => 'String',
        ],
        'name' => [
            'max' => 'Max',
            'required' => 'Required',
            'string' => 'String',
        ],
        'tenant_id' => [
            'integer' => 'Integer',
            'required' => 'Required',
        ],
        'total_apartments' => [
            'integer' => 'Integer',
            'max' => 'Max',
            'min' => 'Min',
            'required' => 'Required',
        ],
    ],
];
