<?php

return [
    'actions' => [
        'add' => 'Añadir',
        'deactivate' => 'Desactivar',
        'reactivate' => 'Reactivar',
        'reassign' => 'Reassign',
        'view' => 'Ver',
    ],
    'empty' => [
        'assignment_history' => 'No assignment history yet',
        'list' => 'No se encontraron inquilinos',
        'list_cta' => 'Crea tu primer inquilino',
        'property' => 'Sin propiedad asignada',
        'recent_invoices' => 'No hay facturas recientes',
        'recent_readings' => 'No hay lecturas recientes',
    ],
    'headings' => [
        'account' => 'Cuenta de inquilino',
        'assignment_history' => 'Historial de asignaciones',
        'current_property' => 'Propiedad actual',
        'index' => 'Inquilinos',
        'index_description' => 'Gestiona cuentas de inquilinos y asignaciones de propiedades',
        'list' => 'Lista de inquilinos',
        'recent_invoices' => 'Facturas recientes',
        'recent_readings' => 'Lecturas recientes',
        'show' => 'Detalles del inquilino',
    ],
    'labels' => [
        'actions' => 'Acciones',
        'address' => 'Dirección',
        'area' => 'Área',
        'building' => 'Edificio',
        'created' => 'Creado',
        'created_by' => 'Created By',
        'email' => 'Correo electrónico',
        'invoice' => 'Factura',
        'name' => 'Nombre',
        'phone' => 'Teléfono',
        'property' => 'Propiedad',
        'reading' => 'lectura',
        'reason' => 'Motivo',
        'status' => 'Estado',
        'type' => 'Tipo',
    ],
    'pages' => [
        'index' => [
            'subtitle' => 'Todos los inquilinos de todas las organizaciones',
            'title' => 'Inquilinos',
        ],
        'admin_form' => [
            'actions' => [
                'cancel' => 'Cancelar',
                'submit' => 'Enviar',
            ],
            'errors_title' => 'Corrige los errores resaltados',
            'labels' => [
                'email' => 'Correo electrónico',
                'name' => 'Nombre',
                'password' => 'Contraseña',
                'password_confirmation' => 'Confirmación de contraseña',
                'property' => 'Propiedad',
            ],
            'notes' => [
                'credentials_sent' => 'Los datos de acceso del inquilino se pueden enviar después de crear la cuenta',
                'no_properties' => 'Añade una propiedad antes de crear un inquilino',
            ],
            'placeholders' => [
                'property' => 'Selecciona una propiedad',
            ],
            'subtitle' => 'Crea una cuenta de inquilino y asígnala a una propiedad de tu cartera.',
            'title' => 'Crear inquilino',
        ],
        'reassign' => [
            'actions' => [
                'cancel' => 'Cancelar',
                'submit' => 'Enviar',
            ],
            'current_property' => [
                'empty' => 'Actualmente no hay ninguna propiedad asignada',
                'title' => 'Propiedad actual',
            ],
            'errors_title' => 'Corrige los errores resaltados',
            'history' => [
                'empty' => 'No se encontraron reasignaciones anteriores',
                'title' => 'Historial de reasignaciones',
            ],
            'new_property' => [
                'empty' => 'No hay propiedades disponibles',
                'label' => 'Nueva propiedad',
                'note' => 'Selecciona la propiedad que debe asignarse a este inquilino',
                'placeholder' => 'Selecciona una propiedad',
            ],
            'subtitle' => 'Mueve este inquilino a otra propiedad conservando el historial de asignaciones.',
            'title' => 'Reasignar inquilino',
            'warning' => [
                'items' => [
                    'audit' => 'Este cambio se registrará en la auditoría.',
                    'notify' => 'Se puede notificar al inquilino sobre la reasignación.',
                    'preserved' => 'El historial de asignaciones existente se conservará.',
                ],
                'title' => 'Antes de continuar',
            ],
        ],
    ],
    'statuses' => [
        'active' => 'Activo',
        'inactive' => 'Inactivo',
    ],
    'sections' => [
        'details' => 'Detalles',
        'invoices' => 'Facturas',
        'stats' => 'Estadísticas',
    ],
    'validation' => [
        'email' => [
            'email' => 'Correo electrónico',
            'max' => 'Máximo',
            'required' => 'Obligatorio',
        ],
        'invoice_id' => [
            'exists' => 'Existe',
            'required' => 'Obligatorio',
        ],
        'lease_end' => [
            'after' => 'Después',
            'date' => 'Fecha',
        ],
        'lease_start' => [
            'date' => 'Fecha',
            'required' => 'Obligatorio',
        ],
        'name' => [
            'max' => 'Máximo',
            'required' => 'Obligatorio',
            'string' => 'String',
        ],
        'phone' => [
            'max' => 'Máximo',
            'string' => 'String',
        ],
        'property_id' => [
            'exists' => 'Existe',
            'required' => 'Obligatorio',
        ],
        'tenant_id' => [
            'integer' => 'Integer',
            'required' => 'Obligatorio',
        ],
    ],
];
