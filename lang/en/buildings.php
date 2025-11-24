<?php

declare(strict_types=1);

return [
    'labels' => [
        'building' => 'Building',
        'buildings' => 'Buildings',
        'name' => 'Building Name',
        'address' => 'Address',
        'total_apartments' => 'Total Apartments',
        'total_area' => 'Total Area',
        'property_count' => 'Property Count',
        'created_at' => 'Created At',
    ],

    'validation' => [
        'tenant_id' => [
            'required' => 'Tenant is required.',
            'integer' => 'Tenant identifier must be a valid number.',
        ],
        'name' => [
            'required' => 'The building name is required.',
            'string' => 'The building name must be text.',
            'max' => 'The building name may not be greater than 255 characters.',
        ],
        'address' => [
            'required' => 'The building address is required.',
            'string' => 'The building address must be text.',
            'max' => 'The building address may not be greater than 255 characters.',
        ],
        'total_apartments' => [
            'required' => 'The total number of apartments is required.',
            'numeric' => 'The total number of apartments must be a whole number.',
            'integer' => 'The total number of apartments must be a whole number.',
            'min' => 'The building must have at least 1 apartment.',
            'max' => 'The building cannot have more than 1,000 apartments.',
        ],
        'total_area' => [
            'required' => 'The total area is required.',
            'numeric' => 'The total area must be a number.',
            'min' => 'The total area must be at least 0.',
        ],
        'gyvatukas' => [
            'start_date' => [
                'required' => 'Start date is required to calculate the gyvatukas average.',
                'date' => 'Start date must be a valid date.',
            ],
            'end_date' => [
                'required' => 'End date is required to calculate the gyvatukas average.',
                'date' => 'End date must be a valid date.',
                'after' => 'End date must be after the start date.',
            ],
        ],
    ],

    'errors' => [
        'has_properties' => 'Cannot delete building with associated properties.',
    ],

    'pages' => [
        'show' => [
            'title' => 'Building Details',
            'heading' => 'Building Details',
        ],
        'manager_index' => [
            'title' => 'Buildings',
            'description' => 'Multi-unit buildings with gyvatukas calculations',
            'add' => 'Add Building',
            'table_caption' => 'Buildings list',
            'headers' => [
                'building' => 'Building',
                'total_apartments' => 'Total Apartments',
                'properties' => 'Properties',
                'gyvatukas' => 'Gyvatukas Average',
                'last_calculated' => 'Last Calculated',
                'actions' => 'Actions',
            ],
            'not_calculated' => 'Not calculated',
            'never' => 'Never',
            'empty' => 'No buildings found.',
            'create_now' => 'Create one now',
            'mobile' => [
                'apartments' => 'Apartments:',
                'properties' => 'Properties:',
                'gyvatukas' => 'Gyvatukas:',
                'last' => 'Last:',
                'view' => 'View',
                'edit' => 'Edit',
            ],
        ],
        'manager_show' => [
            'title' => 'Building Details',
            'description' => 'Building details and gyvatukas calculations',
            'info_title' => 'Building Information',
            'labels' => [
                'name' => 'Building Name',
                'address' => 'Address',
                'total_apartments' => 'Total Apartments',
                'properties_registered' => 'Properties Registered',
            ],
            'gyvatukas_title' => 'Gyvatukas (Circulation Fee)',
            'summer_average' => 'Summer Average',
            'last_calculated' => 'Last Calculated',
            'status' => 'Status',
            'calculated' => 'Calculated',
            'pending' => 'Pending',
            'not_calculated' => 'Not calculated',
            'never' => 'Never',
            'form' => [
                'start_date' => 'Start Date',
                'end_date' => 'End Date',
                'submit' => 'Calculate Summer Average',
            ],
            'properties_title' => 'Properties in Building',
            'add_property' => 'Add Property',
            'properties_headers' => [
                'address' => 'Address',
                'type' => 'Type',
                'area' => 'Area',
                'meters' => 'Meters',
                'tenant' => 'Tenant',
                'actions' => 'Actions',
            ],
            'vacant' => 'Vacant',
            'view' => 'View',
            'edit_building' => 'Edit Building',
            'delete_building' => 'Delete',
            'delete_confirm' => 'Are you sure you want to delete this building?',
            'empty_properties' => 'No properties registered in this building.',
        ],

        'manager_form' => [
            'create_title' => 'Create Building',
            'create_subtitle' => 'Add a new multi-unit building',
            'edit_title' => 'Edit Building',
            'edit_subtitle' => 'Update building information',
            'breadcrumb_create' => 'Create',
            'breadcrumb_edit' => 'Edit',
            'labels' => [
                'name' => 'Building Name',
                'address' => 'Address',
                'total_apartments' => 'Total Apartments',
            ],
            'placeholders' => [
                'name' => 'Gedimino 15',
                'address' => '123 Main Street, Vilnius',
                'total_apartments' => '10',
            ],
            'actions' => [
                'cancel' => 'Cancel',
                'save_create' => 'Create Building',
                'save_edit' => 'Update Building',
            ],
        ],
    ],
];
