<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Meters Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines are used in the Meters Resource
    | and related components. All user-facing strings should be localized.
    |
    */

    'labels' => [
        'meter' => 'Meter',
        'meters' => 'Meters',
        'property' => 'Property',
        'type' => 'Meter Type',
        'serial_number' => 'Serial Number',
        'installation_date' => 'Installation Date',
        'supports_zones' => 'Supports Zones',
        'readings_count' => 'Readings',
        'readings' => 'Meter Readings',
        'created' => 'Created At',
        'updated' => 'Updated At',
        'never_updated' => 'Never updated',
    ],

    'placeholders' => [
        'serial_number' => 'Enter meter serial number',
    ],

    'helper_text' => [
        'property' => 'Select the property where this meter is installed',
        'type' => 'Select the type of utility this meter measures',
        'serial_number' => 'Unique identifier for this meter (must be unique)',
        'installation_date' => 'Date when the meter was installed (cannot be in the future)',
        'supports_zones' => 'Enable if this meter supports time-of-use zones (day/night rates)',
    ],

    'sections' => [
        'meter_details' => 'Meter Details',
        'meter_details_description' => 'Basic information about the meter',
        'metadata' => 'Metadata',
    ],

    'tooltips' => [
        'property_address' => 'Installed at: :address',
        'copy_serial' => 'Click to copy serial number',
        'supports_zones_yes' => 'This meter supports time-of-use zones',
        'supports_zones_no' => 'This meter does not support time-of-use zones',
        'readings_count' => 'Number of recorded readings',
    ],

    'filters' => [
        'type' => 'Meter Type',
        'property' => 'Property',
        'supports_zones' => 'Supports Zones',
        'no_readings' => 'No Readings',
    ],

    'actions' => [
        'create' => 'Create Meter',
        'delete' => 'Delete',
    ],

    'notifications' => [
        'created' => 'Meter created successfully.',
        'updated' => 'Meter updated successfully.',
        'deleted' => 'Meter deleted successfully.',
    ],

    'modals' => [
        'delete_heading' => 'Delete Meter',
        'delete_description' => 'Are you sure you want to delete this meter? This action cannot be undone.',
        'delete_confirm' => 'Yes, Delete',
        'bulk_delete' => [
            'title' => 'Delete Selected Meters',
            'description' => 'Are you sure you want to delete the selected meters? This action cannot be undone.',
            'confirm' => 'Delete Meters',
        ],
    ],

    'empty_state' => [
        'heading' => 'No Meters',
        'description' => 'Get started by creating your first meter.',
    ],

    'validation' => [
        'tenant_id' => [
            'required' => 'Tenant is required.',
            'integer' => 'Tenant identifier must be a valid number.',
        ],
        'serial_number' => [
            'required' => 'The serial number is required.',
            'unique' => 'This serial number is already in use.',
            'string' => 'The serial number must be text.',
            'max' => 'The serial number may not be greater than 255 characters.',
        ],
        'type' => [
            'required' => 'The meter type is required.',
            'enum_detail' => 'The meter type must be one of: electricity, water_cold, water_hot, or heating.',
        ],
        'property_id' => [
            'required' => 'A property selection is required.',
            'exists' => 'The selected property does not exist.',
        ],
        'installation_date' => [
            'required' => 'The installation date is required.',
            'date' => 'The installation date must be a valid date.',
            'before_or_equal' => 'The installation date cannot be in the future.',
        ],
        'supports_zones' => [
            'boolean' => 'The supports zones field must be true or false.',
        ],
    ],

];
