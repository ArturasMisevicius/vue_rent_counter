<?php

return [
    'actions' => [
        'add' => 'Añadir',
        'back' => 'Volver',
        'clear' => 'Limpiar',
        'create' => 'Crear',
        'delete' => 'Eliminar',
        'edit' => 'Editar',
        'filter' => 'Filtrar',
        'update' => 'Actualizar',
        'view' => 'Ver',
    ],
    'descriptions' => [
        'index' => 'Listado',
    ],
    'empty' => [
        'users' => 'Usuarios',
    ],
    'empty_state' => [
        'description' => 'Descripción',
        'heading' => 'Encabezado',
    ],
    'errors' => [
        'has_readings' => 'Has lecturas',
    ],
    'filters' => [
        'active_only' => 'Solo activos',
        'all_users' => 'All Users',
        'inactive_only' => 'Solo inactivos',
        'is_active' => 'Activo',
        'role' => 'Rol',
    ],
    'headings' => [
        'create' => 'Crear',
        'edit' => 'Editar',
        'index' => 'Listado',
        'information' => 'Information',
        'quick_actions' => 'Quick Actions',
        'show' => 'Ver',
    ],
    'helper_text' => [
        'is_active' => 'Activo',
        'password' => 'Contraseña',
        'role' => 'Rol',
        'tenant' => 'Inquilino',
    ],
    'labels' => [
        'activity_hint' => 'Revise la actividad reciente del usuario',
        'activity_history' => 'Historial de actividad',
        'created' => 'Creado',
        'created_at' => 'Creado',
        'email' => 'Correo electrónico',
        'is_active' => 'Activo',
        'last_login_at' => 'Último acceso',
        'meter_readings_entered' => 'Lectura de medidors Entered',
        'name' => 'Nombre',
        'no_activity' => 'Sin actividad',
        'password' => 'Contraseña',
        'password_confirmation' => 'Confirmación de contraseña',
        'role' => 'Rol',
        'tenant' => 'Inquilino',
        'updated_at' => 'Actualizado',
        'user' => 'Usuario',
        'users' => 'Usuarios',
    ],
    'placeholders' => [
        'email' => 'Correo electrónico',
        'name' => 'Nombre',
        'password' => 'Contraseña',
        'password_confirmation' => 'Confirmación de contraseña',
    ],
    'sections' => [
        'role_and_access' => 'Role And Access',
        'role_and_access_description' => 'Descripción de rol y acceso',
        'user_details' => 'Detalles del usuario',
        'user_details_description' => 'Descripción de detalles del usuario',
    ],
    'tables' => [
        'actions' => 'Acciones',
        'email' => 'Correo electrónico',
        'name' => 'Nombre',
        'role' => 'Rol',
        'tenant' => 'Inquilino',
    ],
    'tooltips' => [
        'copy_email' => 'Copiar email',
    ],
    'validation' => [
        'current_password' => [
            'current_password' => 'Contraseña actual',
            'required' => 'Obligatorio',
            'required_with' => 'Obligatorio con',
            'string' => 'String',
        ],
        'email' => [
            'email' => 'Correo electrónico',
            'max' => 'Máximo',
            'required' => 'Obligatorio',
            'string' => 'String',
            'unique' => 'Único',
        ],
        'name' => [
            'max' => 'Máximo',
            'required' => 'Obligatorio',
            'string' => 'String',
        ],
        'organization_name' => [
            'max' => 'Máximo',
            'string' => 'String',
        ],
        'password' => [
            'confirmed' => 'Confirmed',
            'min' => 'Min',
            'required' => 'Obligatorio',
            'string' => 'String',
        ],
        'role' => [
            'enum' => 'Enum',
            'required' => 'Obligatorio',
        ],
        'tenant_id' => [
            'exists' => 'Existe',
            'integer' => 'Integer',
            'required' => 'Obligatorio',
        ],
    ],
];
