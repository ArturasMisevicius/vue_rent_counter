<?php

return [
    'description' => 'Descripción',
    'system_page' => [
        'description' => 'Manage platform-wide configuración and configuration. Changes will take effect immediately after saving.',
        'notes' => [
            'backup_schedule' => 'Backup schedule uses cron expression format (e.g., "0 2 * * *" for daily at 2 AM)',
            'email' => 'La configuración de email requiere credenciales SMTP válidas para enviar notificaciones.',
            'feature_flags' => 'Feature flags affect all organizaciones unless overridden',
            'password_policy' => 'Los cambios de política de contraseña se aplican solo a nuevas contraseñas.',
            'queue' => 'Queue configuration changes may require worker restart',
        ],
        'notes_title' => 'Configuration Notes',
        'title' => 'Configuración del sistema',
        'warning_body' => 'Modifying system configuración can affect platform stability and user experience. Always test changes in a development environment first. Export your current configuration before making significant changes.',
        'warning_title' => 'Important Warning',
    ],
    'forms' => [
        'app_name' => 'Nombre de la aplicación',
        'app_name_hint' => 'Ayuda del nombre de la aplicación',
        'save' => 'Guardar',
        'timezone' => 'Zona horaria',
        'timezone_hint' => 'Seleccione la zona horaria',
        'title' => 'Título',
        'warnings' => [
            'backups' => 'Copias de seguridad',
            'env' => 'Env',
            'note_title' => 'Título de nota',
        ],
    ],
    'maintenance' => [
        'clear_cache' => 'Clear Cache',
        'clear_cache_description' => 'Descripción de limpieza de caché',
        'run_backup' => 'Run Backup',
        'run_backup_description' => 'Descripción de ejecución de copia de seguridad',
        'title' => 'Título',
    ],
    'stats' => [
        'cache_size' => 'Cache Size',
        'db_size' => 'Db Size',
        'invoices' => 'Facturas',
        'meters' => 'Contadores',
        'properties' => 'Propiedades',
        'users' => 'Usuarios',
    ],
    'title' => 'Título',
    'validation' => [
        'app_name' => [
            'max' => 'El nombre de la aplicación no puede superar 255 caracteres.',
            'string' => 'El nombre de la aplicación debe ser texto.',
            'regex' => 'El nombre de la aplicación solo puede contener letras, números, espacios, guiones, guiones bajos y puntos.',
        ],
        'timezone' => [
            'in' => 'La zona horaria seleccionada no es válida.',
            'string' => 'La zona horaria debe ser texto.',
        ],
        'language' => [
            'in' => 'El idioma seleccionado no es compatible.',
        ],
        'date_format' => [
            'in' => 'El formato de fecha seleccionado no es válido.',
        ],
        'currency' => [
            'size' => 'El código de moneda debe tener exactamente 3 caracteres.',
            'in' => 'La moneda seleccionada no es compatible.',
        ],
        'invoice_due_days' => [
            'min' => 'Factura due days must be at least 1 day.',
            'max' => 'Factura due days may not be greater than 90 days.',
        ],
    ],
    'attributes' => [
        'app_name' => 'application name',
        'timezone' => 'timezone',
        'language' => 'idioma',
        'date_format' => 'date format',
        'currency' => 'moneda',
        'notifications_enabled' => 'notifications enabled',
        'email_notifications' => 'email notifications',
        'sms_notifications' => 'SMS notifications',
        'invoice_due_days' => 'factura due days',
        'auto_generate_invoices' => 'auto-generate facturas',
        'maintenance_mode' => 'maintenance mode',
    ],
];
