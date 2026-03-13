<?php

declare(strict_types=1);

return [
    'actions' => [
        'add' => 'Add',
        'add_first_property' => 'Add First Property',
        'assign_tenant' => 'Assign Tenant',
        'edit' => 'Edit',
        'export_selected' => 'Export Selected',
        'manage_tenant' => 'Manage Tenant',
        'reassign_tenant' => 'Reassign Tenant',
        'view' => 'View',
    ],
    'badges' => [
        'vacant' => 'Vacant',
    ],
    'empty_state' => [
        'description' => 'Description',
        'heading' => 'Heading',
    ],
    'errors' => [
        'has_relations' => 'Has Relations',
    ],
    'filters' => [
        'all_properties' => 'All Properties',
        'building' => 'Building',
        'large_properties' => 'Large Properties',
        'occupancy' => 'Occupancy',
        'occupied' => 'Occupied',
        'tags' => 'Žymos',
        'type' => 'Type',
        'vacant' => 'Vacant',
    ],
    'helper_text' => [
        'address' => 'Address',
        'area' => 'Area',
        'tags' => 'Pasirinkite žymas šiam turtui',
        'tenant_available' => 'Tenant Available',
        'tenant_reassign' => 'Tenant Reassign',
        'type' => 'Type',
    ],
    'labels' => [
        'address' => 'Address',
        'area' => 'Area',
        'building' => 'Building',
        'created' => 'Created',
        'current_tenant' => 'Current Tenant',
        'installed_meters' => 'Installed Meters',
        'meters' => 'Meters',
        'properties' => 'Properties',
        'property' => 'Property',
        'tags' => 'Žymos',
        'type' => 'Type',
    ],
    'manager' => [
        'index' => [
            'caption' => 'Caption',
            'description' => 'Description',
            'empty' => [
                'cta' => 'Cta',
                'text' => 'Text',
            ],
            'filters' => [
                'all_buildings' => 'All Buildings',
                'all_types' => 'All Types',
                'building' => 'Building',
                'clear' => 'Clear',
                'filter' => 'Filter',
                'search' => 'Search',
                'search_placeholder' => 'Search Placeholder',
                'type' => 'Type',
            ],
            'headers' => [
                'actions' => 'Actions',
                'address' => 'Address',
                'area' => 'Area',
                'building' => 'Building',
                'meters' => 'Meters',
                'tenants' => 'Tenants',
                'type' => 'Type',
            ],
            'title' => 'Title',
        ],
    ],
    'modals' => [
        'bulk_delete' => [
            'confirm' => 'Confirm',
            'description' => 'Description',
            'title' => 'Title',
        ],
        'delete_confirmation' => 'Delete Confirmation',
    ],
    'notifications' => [
        'bulk_deleted' => [
            'body' => 'Body',
            'title' => 'Title',
        ],
        'created' => [
            'body' => 'Body',
            'title' => 'Title',
        ],
        'deleted' => [
            'body' => 'Body',
            'title' => 'Title',
        ],
        'export_started' => [
            'body' => 'Body',
            'title' => 'Title',
        ],
        'updated' => [
            'body' => 'Body',
            'title' => 'Title',
        ],
        '{$action}' => [
            'body' => 'Body',
            'title' => 'Title',
        ],
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
                'area' => 'Area',
                'building' => 'Building',
                'type' => 'Type',
            ],
            'placeholders' => [
                'address' => 'Address',
                'area' => 'Area',
                'building' => 'Building',
            ],
        ],
        'manager_show' => [
            'add_meter' => 'Add Meter',
            'building_missing' => 'Building Missing',
            'current_tenant_title' => 'Current Tenant Title',
            'delete_confirm' => 'Delete Confirm',
            'delete_property' => 'Delete Property',
            'description' => 'Description',
            'edit_property' => 'Edit Property',
            'info_title' => 'Info Title',
            'labels' => [
                'address' => 'Address',
                'area' => 'Area',
                'building' => 'Building',
                'type' => 'Type',
            ],
            'latest_none' => 'Latest None',
            'meters_headers' => [
                'actions' => 'Actions',
                'installation' => 'Installation',
                'latest' => 'Latest',
                'serial' => 'Serial',
                'type' => 'Type',
            ],
            'meters_title' => 'Meters Title',
            'no_meters_installed' => 'No Meters Installed',
            'no_tenant' => 'No Tenant',
            'tenant_labels' => [
                'email' => 'Email',
                'name' => 'Name',
                'phone' => 'Phone',
            ],
            'tenant_na' => 'Tenant Na',
            'title' => 'Title',
            'view' => 'View',
        ],
    ],
    'placeholders' => [
        'address' => 'Address',
        'area' => 'Area',
    ],
    'sections' => [
        'additional_info' => 'Additional Info',
        'additional_info_description' => 'Additional Info Description',
        'property_details' => 'Property Details',
        'property_details_description' => 'Property Details Description',
    ],
    'tooltips' => [
        'copy_address' => 'Copy Address',
        'meters_count' => 'Meters Count',
        'no_tenant' => 'No Tenant',
        'occupied_by' => 'Occupied By',
    ],
    'validation' => [
        'address' => [
            'format' => 'Format',
            'invalid_characters' => 'Invalid Characters',
            'max' => 'Max',
            'prohibited_content' => 'Prohibited Content',
            'required' => 'Required',
            'string' => 'String',
        ],
        'area_sqm' => [
            'format' => 'Format',
            'max' => 'Max',
            'min' => 'Min',
            'negative' => 'Negative',
            'numeric' => 'Numeric',
            'precision' => 'Precision',
            'required' => 'Required',
        ],
        'building_id' => [
            'exists' => 'Exists',
        ],
        'property_id' => [
            'exists' => 'Exists',
            'required' => 'Required',
        ],
        'tenant_id' => [
            'integer' => 'Integer',
            'required' => 'Required',
        ],
        'type' => [
            'enum' => 'Enum',
            'required' => 'Required',
        ],
    ],
];
