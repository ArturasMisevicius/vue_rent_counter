<?php

return [
    'profile' => [
        'title' => 'Mi perfil',
        'description' => 'Gestiona tu información personal, idioma preferido y credenciales de acceso desde una sola página de cuenta.',
        'personal_information' => 'Información personal',
        'fields' => [
            'name' => 'Nombre',
            'email' => 'Correo electrónico',
            'locale' => 'Idioma preferido',
        ],
        'language_preference' => 'Idioma preferido',
        'security' => 'Seguridad',
        'change_password' => 'Cambiar contraseña',
        'password_description' => 'Confirma el cambio con tu contraseña actual y luego elige una nueva para futuros accesos.',
        'current_password' => 'Contraseña actual',
        'new_password' => 'Nueva contraseña',
        'confirm_password' => 'Confirmar nueva contraseña',
        'messages' => [
            'saved' => 'Los datos del perfil fueron actualizados.',
            'password_updated' => 'La contraseña fue actualizada.',
        ],
    ],
    'settings' => [
        'title' => 'Configuración',
        'description' => 'Configura las preferencias de facturación, notificaciones y suscripción para tu organización.',
        'organization' => [
            'title' => 'Configuración de la organización',
            'description' => 'Mantén consistentes los datos de facturación y el contenido de las facturas en toda la organización.',
            'billing_contact_name' => 'Nombre del contacto de facturación',
            'billing_contact_email' => 'Correo de facturación',
            'billing_contact_phone' => 'Teléfono de facturación',
            'payment_instructions' => 'Instrucciones de pago',
            'invoice_footer' => 'Pie de factura',
        ],
        'notifications' => [
            'title' => 'Preferencias de notificación',
            'description' => 'Decide qué alertas operativas deben mantenerse visibles para esta organización.',
            'invoice_reminders' => 'Recordatorios de facturas',
            'invoice_reminders_help' => 'Mostrar recordatorios para facturas vencidas y próximos vencimientos.',
            'reading_deadline_alerts' => 'Alertas de fechas de lectura',
            'reading_deadline_alerts_help' => 'Resaltar medidores cuyo próximo período esperado de lectura está por vencer.',
        ],
        'subscription' => [
            'title' => 'Suscripción',
            'description' => 'Revisa el estado comercial actual y amplía el plan sin salir del espacio de trabajo de la organización.',
            'plan' => 'Plan',
            'status' => 'Estado',
            'expires_at' => 'Vence',
            'duration' => 'Duración de renovación',
            'not_set' => 'No configurado',
        ],
        'manager' => [
            'title' => 'Configuración',
            'description' => 'La facturación, notificaciones y suscripción de la organización siguen siendo administradas por los administradores. Usa tu página de perfil para cambios de cuenta.',
        ],
        'messages' => [
            'organization_saved' => 'La configuración de la organización fue actualizada.',
            'notifications_saved' => 'Las preferencias de notificación fueron actualizadas.',
            'subscription_renewed' => 'Los datos de renovación de la suscripción fueron actualizados.',
        ],
    ],
    'actions' => [
        'save_profile' => 'Guardar perfil',
        'update_password' => 'Actualizar contraseña',
        'save_settings' => 'Guardar configuración',
        'save_notifications' => 'Guardar notificaciones',
        'renew_subscription' => 'Renovar suscripción',
    ],
];
