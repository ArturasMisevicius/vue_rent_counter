<?php

return [
    'search' => [
        'label' => 'Búsqueda global',
        'placeholder' => 'Search anything',
        'groups' => [
            'platform' => 'Plataforma',
            'organization' => 'Organización',
            'tenant' => 'Inquilino',
        ],
        'empty' => [
            'heading' => 'Aún no hay resultados',
            'body' => 'Los resultados aparecerán aquí cuando existan rutas y registros coincidentes.',
        ],
    ],
    'navigation' => [
        'groups' => [
            'platform' => 'Plataforma',
            'organization' => 'Organización',
            'account' => 'Cuenta',
        ],
        'items' => [
            'organizations' => 'Organizaciones',
            'profile' => 'Perfil',
            'settings' => 'Configuración',
        ],
    ],
    'roles' => [
        'superadmin' => 'Superadministrador',
        'admin' => 'Administrador',
        'manager' => 'Gerente',
        'tenant' => 'Inquilino',
    ],
    'profile' => [
        'title' => 'Mi perfil',
        'eyebrow' => 'Espacio de cuenta',
        'heading' => 'Mi perfil',
        'description' => 'Revisa tu identidad de cuenta, el idioma preferido y el contexto de sesión desde un destino compartido.',
        'personal_information' => [
            'heading' => 'Información personal',
            'description' => 'Mantén actualizados tu nombre visible, correo electrónico e idioma preferido.',
        ],
        'password' => [
            'heading' => 'Cambiar contraseña',
            'description' => 'Define una nueva contraseña para tu cuenta y confírmala antes de guardar.',
        ],
        'fields' => [
            'name' => 'Nombre',
            'email' => 'Correo electrónico',
            'locale' => 'Idioma',
            'current_password' => 'Contraseña actual',
            'password' => 'Nueva contraseña',
            'password_confirmation' => 'Confirmar nueva contraseña',
        ],
        'actions' => [
            'save' => 'Guardar perfil',
            'update_password' => 'Actualizar contraseña',
        ],
        'messages' => [
            'saved' => 'Tu perfil ha sido actualizado.',
            'password_updated' => 'Tu contraseña ha sido actualizada.',
        ],
    ],
    'settings' => [
        'title' => 'Configuración',
        'organization' => [
            'heading' => 'Configuración de la organización',
            'description' => 'Gestiona los contactos de facturación y los detalles de pago que verán los futuros usuarios.',
            'fields' => [
                'billing_contact_name' => 'Nombre del contacto de facturación',
                'billing_contact_email' => 'Correo del contacto de facturación',
                'billing_contact_phone' => 'Teléfono del contacto de facturación',
                'payment_instructions' => 'Instrucciones de pago',
                'invoice_footer' => 'Pie de factura',
            ],
            'actions' => [
                'save' => 'Guardar configuración de la organización',
            ],
        ],
        'notifications' => [
            'heading' => 'Preferencias de notificación',
            'description' => 'Elige qué recordatorios deben recibir los administradores dentro del espacio de trabajo de la organización.',
            'fields' => [
                'invoice_reminders' => 'Recordatorios de facturas',
                'reading_deadline_alerts' => 'Alertas de vencimiento de lecturas',
            ],
            'actions' => [
                'save' => 'Guardar preferencias de notificación',
            ],
        ],
        'subscription' => [
            'heading' => 'Suscripción',
            'description' => 'Renueva el plan actual y actualiza los límites de uso de la organización.',
            'fields' => [
                'plan' => 'Plan',
                'duration' => 'Duración',
            ],
            'plans' => [
                'basic' => 'Básico',
                'professional' => 'Profesional',
                'enterprise' => 'Enterprise',
            ],
            'durations' => [
                'monthly' => 'Mensual',
                'quarterly' => 'Trimestral',
                'yearly' => 'Anual',
            ],
            'actions' => [
                'renew' => 'Renovar suscripción',
            ],
        ],
        'messages' => [
            'organization_saved' => 'La configuración de la organización ha sido actualizada.',
            'notifications_saved' => 'Las preferencias de notificación han sido actualizadas.',
            'subscription_renewed' => 'La suscripción ha sido renovada.',
        ],
    ],
    'actions' => [
        'back_to_dashboard' => 'Volver al panel',
    ],
    'impersonation' => [
        'eyebrow' => 'Suplantación activa',
        'heading' => 'Estás suplantando esta cuenta',
        'actions' => [
            'stop' => 'Dejar de suplantar',
        ],
    ],
    'errors' => [
        'eyebrow' => 'Error :status',
        '403' => [
            'title' => 'No tienes permiso para ver esta página',
            'description' => 'Tu cuenta no tiene acceso a esta área en este momento. Si crees que es un error, contacta a tu administrador o vuelve al panel correcto.',
        ],
        '404' => [
            'title' => 'La página que buscas no existe',
            'description' => 'Es posible que el enlace esté desactualizado, incompleto o ya no esté disponible. Vuelve a tu panel para continuar de forma segura.',
        ],
        '500' => [
            'title' => 'Algo salió mal de nuestro lado',
            'description' => 'No pudimos completar esa solicitud en este momento. Inténtalo de nuevo en unos instantes o contacta soporte si el problema continúa.',
        ],
    ],
    'notifications' => [
        'heading' => 'Notificaciones',
        'unread_count' => '{0} No hay notificaciones sin leer|{1} :count notificación sin leer|[2,*] :count notificaciones sin leer',
        'actions' => [
            'toggle' => 'Alternar notificaciones',
            'mark_all_read' => 'Marcar todo como leído',
        ],
        'empty' => [
            'heading' => 'Aún no hay notificaciones',
            'body' => 'Las nuevas actualizaciones aparecerán aquí cuando el producto tenga algo que compartir.',
        ],
        'defaults' => [
            'title' => 'Notificación',
            'body' => 'Hay detalles de la notificación disponibles.',
            'just_now' => 'ahora mismo',
        ],
    ],
];
