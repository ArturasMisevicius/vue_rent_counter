<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Properties Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines are used in the Properties Relation Manager
    | and related components. All user-facing strings should be localized.
    |
    */

    'labels' => [
        'property' => 'Property',
        'properties' => 'Properties',
        'address' => 'Address',
        'type' => 'Property Type',
        'area' => 'Area (m²)',
        'current_tenant' => 'Current Tenant',
        'building' => 'Building',
        'installed_meters' => 'Installed Meters',
        'meters' => 'Meters',
        'created' => 'Created At',
    ],

    'placeholders' => [
        'address' => 'Enter property address',
        'area' => 'Enter area in square meters',
    ],

    'helper_text' => [
        'address' => 'Full street address including building and apartment number',
        'type' => 'Select apartment or house',
        'area' => 'Property area in square meters (max 2 decimal places)',
        'tenant_available' => 'Select an available tenant to assign to this property',
        'tenant_reassign' => 'Select a new tenant or leave empty to vacate the property',
    ],

    'sections' => [
        'property_details' => 'Property Details',
        'property_details_description' => 'Basic information about the property',
        'additional_info' => 'Additional Information',
        'additional_info_description' => 'Building, tenant, and meter information',
    ],

    'badges' => [
        'vacant' => 'Vacant',
    ],

    'tooltips' => [
        'copy_address' => 'Click to copy address',
        'occupied_by' => 'Occupied by :name',
        'no_tenant' => 'No tenant assigned',
        'meters_count' => 'Number of installed meters',
    ],

    'filters' => [
        'type' => 'Property Type',
        'building' => 'Building',
        'occupancy' => 'Occupancy Status',
        'all_properties' => 'All Properties',
        'occupied' => 'Occupied',
        'vacant' => 'Vacant',
        'large_properties' => 'Large Properties (>100 m²)',
    ],

    'actions' => [
        'manage_tenant' => 'Manage Tenant',
        'assign_tenant' => 'Assign Tenant',
        'reassign_tenant' => 'Reassign Tenant',
        'export_selected' => 'Export Selected',
        'add_first_property' => 'Add First Property',
    ],

    'notifications' => [
        'created' => [
            'title' => 'Property Created',
            'body' => 'The property has been created successfully.',
        ],
        'updated' => [
            'title' => 'Property Updated',
            'body' => 'The property has been updated successfully.',
        ],
        'deleted' => [
            'title' => 'Property Deleted',
            'body' => 'The property has been deleted successfully.',
        ],
        'bulk_deleted' => [
            'title' => 'Properties Deleted',
            'body' => ':count properties have been deleted successfully.',
        ],
        'tenant_assigned' => [
            'title' => 'Tenant Assigned',
            'body' => 'The tenant has been assigned to the property successfully.',
        ],
        'tenant_removed' => [
            'title' => 'Tenant Removed',
            'body' => 'The tenant has been removed from the property successfully.',
        ],
        'export_started' => [
            'title' => 'Export Started',
            'body' => 'Your export is being processed. You will be notified when it is ready.',
        ],
    ],

    'modals' => [
        'delete_confirmation' => 'Are you sure you want to delete this property? This action cannot be undone.',
    ],

    'empty_state' => [
        'heading' => 'No Properties',
        'description' => 'Get started by creating your first property.',
    ],

    'validation' => [
        'address' => [
            'required' => 'The property address is required.',
            'max' => 'The property address may not be greater than 255 characters.',
            'invalid_characters' => 'The address contains invalid characters.',
            'prohibited_content' => 'The address contains prohibited content.',
            'format' => 'The address may only contain letters, numbers, spaces, and common punctuation (.,#/-()).',
        ],
        'type' => [
            'required' => 'The property type is required.',
            'enum' => 'The property type must be either apartment or house.',
        ],
        'area_sqm' => [
            'required' => 'The property area is required.',
            'numeric' => 'The property area must be a number.',
            'min' => 'The property area must be at least 0 square meters.',
            'max' => 'The property area cannot exceed 10,000 square meters.',
            'format' => 'The area must be a standard decimal number.',
            'negative' => 'The area cannot be negative.',
            'precision' => 'The area may have at most 2 decimal places.',
        ],
        'building_id' => [
            'exists' => 'The selected building does not exist.',
        ],
    ],

];
