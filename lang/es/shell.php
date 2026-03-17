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
