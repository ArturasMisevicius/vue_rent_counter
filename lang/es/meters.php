<?php

return [
    'actions' => [
        'add' => 'Añadir',
        'create' => 'Crear',
        'delete' => 'Eliminar',
        'edit' => 'Editar',
        'edit_meter' => 'Edit Contador',
        'view' => 'Ver',
        'view_readings' => 'View readings',
    ],
    'confirmations' => [
        'delete' => 'Eliminar',
    ],
    'empty_state' => [
        'description' => 'Descripción',
        'heading' => 'Encabezado',
    ],
    'errors' => [
        'has_readings' => 'Has lecturas',
    ],
    'filters' => [
        'no_readings' => 'No lecturas',
        'property' => 'Propiedad',
        'supports_zones' => 'Supports Zones',
        'type' => 'Tipo',
    ],
    'headings' => [
        'information' => 'Information',
        'show' => 'Ver',
        'show_description' => 'Mostrar descripción',
    ],
    'helper_text' => [
        'installation_date' => 'Fecha de instalación',
        'property' => 'Propiedad',
        'serial_number' => 'Número de serie',
        'supports_zones' => 'Supports Zones',
        'type' => 'Tipo',
    ],
    'labels' => [
        'created' => 'Creado',
        'installation_date' => 'Fecha de instalación',
        'meter' => 'Contador',
        'meters' => 'Contadores',
        'property' => 'Propiedad',
        'readings' => 'lecturas',
        'readings_count' => 'Número de lecturas',
        'serial_number' => 'Número de serie',
        'supports_zones' => 'Supports Zones',
        'type' => 'Tipo',
    ],
    'manager' => [
        'index' => [
            'caption' => 'Subtítulo',
            'description' => 'Descripción',
            'empty' => [
                'cta' => 'Llamada a la acción',
                'text' => 'Texto',
            ],
            'headers' => [
                'actions' => 'Acciones',
                'installation_date' => 'Fecha de instalación',
                'latest_reading' => 'Latest lectura',
                'property' => 'Propiedad',
                'serial_number' => 'Número de serie',
                'type' => 'Tipo',
                'zones' => 'Zones',
            ],
            'title' => 'Título',
            'zones' => [
                'no' => 'No',
                'yes' => 'Sí',
            ],
        ],
    ],
    'modals' => [
        'bulk_delete' => [
            'confirm' => 'Confirmar',
            'description' => 'Descripción',
            'title' => 'Título',
        ],
        'delete_confirm' => 'Confirmar eliminación',
        'delete_description' => 'Descripción de eliminación',
        'delete_heading' => 'Encabezado de eliminación',
    ],
    'notifications' => [
        'created' => 'Creado',
        'updated' => 'Actualizado',
    ],
    'placeholders' => [
        'serial_number' => 'Número de serie',
    ],
    'relation' => [
        'add_first' => 'Add First',
        'empty_description' => 'Descripción del estado vacío',
        'empty_heading' => 'Sin resultados',
        'initial_reading' => 'Initial lectura',
        'installation_date' => 'Fecha de instalación',
        'installed' => 'Installed',
        'meter_type' => 'Tipo de contador',
        'readings' => 'lecturas',
        'serial_number' => 'Número de serie',
        'type' => 'Tipo',
    ],
    'sections' => [
        'meter_details' => 'Detalles del contador',
        'meter_details_description' => 'Descripción de detalles del contador',
    ],
    'tooltips' => [
        'copy_serial' => 'Copy Serial',
        'property_address' => 'Propiedad Address',
        'readings_count' => 'Número de lecturas',
        'supports_zones_no' => 'Supports Zones No',
        'supports_zones_yes' => 'Supports Zones Yes',
    ],
    'units' => [
        'kwh' => 'Kwh',
    ],
    'validation' => [
        'installation_date' => [
            'before_or_equal' => 'Before Or Equal',
            'date' => 'Fecha',
            'required' => 'Obligatorio',
        ],
        'property_id' => [
            'exists' => 'Existe',
            'required' => 'Obligatorio',
        ],
        'serial_number' => [
            'max' => 'Máximo',
            'required' => 'Obligatorio',
            'string' => 'String',
            'unique' => 'Único',
        ],
        'supports_zones' => [
            'boolean' => 'Boolean',
        ],
        'tenant_id' => [
            'integer' => 'Integer',
            'required' => 'Obligatorio',
        ],
        'type' => [
            'enum_detail' => 'Enum Detail',
            'required' => 'Obligatorio',
        ],
    ],
];
