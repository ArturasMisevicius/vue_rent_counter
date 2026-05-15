<?php

return [
    'admin' => [
        'title' => 'Perfil de administrador',
        'org_title' => 'Perfil de organización',
        'org_description' => 'Manage your organización account, suscripción, and security configuración.',
        'profile_title' => 'Perfil',
        'profile_description' => 'Manage your personal account details.',
        'alerts' => [
            'errors' => 'Corrige los campos resaltados e inténtalo de nuevo.',
            'expired_body' => 'Tu suscripción venció el :date. Renueva para mantener el acceso completo.',
            'expired_title' => 'Suscripción Vencido',
            'expiring_body' => 'Tu suscripción vence en :days días (:date). Considera renovarla pronto.',
            'expiring_title' => 'Suscripción Expiring Soon',
        ],
        'days' => '{1}:count day|[2,*]:count days',
        'language_form' => [
            'description' => 'Elige el idioma que prefieres para el espacio de administración.',
            'title' => 'Idioma',
        ],
        'password_form' => [
            'confirm' => 'Confirmar nueva contraseña',
            'current' => 'Contraseña actual',
            'description' => 'Usa una contraseña segura para mantener protegida tu cuenta.',
            'new' => 'Nueva Contraseña',
            'submit' => 'Actualizar contraseña',
            'title' => 'Contraseña',
        ],
        'profile_form' => [
            'description' => 'Actualiza tus datos principales de contacto usados en la plataforma.',
            'currency' => 'Moneda',
            'email' => 'Correo electrónico',
            'name' => 'Nombre',
            'organization' => 'Nombre de organización',
            'submit' => 'Guardar cambios',
            'title' => 'Detalles del perfil',
        ],
        'subscription' => [
            'approaching_limit' => 'You are approaching your plan limit.',
            'card_title' => 'Suscripción',
            'days_remaining' => '(:days remaining)',
            'description' => 'Estado del plan actual, datos de vencimiento y límites de uso.',
            'expiry_date' => 'Fecha de vencimiento',
            'plan_type' => 'Plan',
            'properties' => 'Propiedades',
            'start_date' => 'Fecha de inicio',
            'status' => 'Estado',
            'tenants' => 'Inquilinos',
            'usage_limits' => 'Usage Limits',
        ],
    ],
    'superadmin' => [
        'title' => 'Perfil de superadministrador',
        'heading' => 'Perfil de superadministrador',
        'description' => 'Manage your platform-level account configuración and language preferences.',
        'actions' => [
            'update_profile' => 'Actualizar perfil',
        ],
        'alerts' => [
            'errors' => 'Corrige los campos resaltados e inténtalo de nuevo.',
        ],
        'language_form' => [
            'description' => 'Elige tu idioma preferido para la interfaz del superadministrador.',
            'title' => 'Idioma',
        ],
        'profile_form' => [
            'description' => 'Actualiza la identidad básica de tu cuenta y la contraseña opcional desde un solo lugar.',
            'currency' => 'Moneda',
            'email' => 'Correo electrónico',
            'name' => 'Nombre',
            'password' => 'Nueva Contraseña',
            'password_confirmation' => 'Confirmar nueva contraseña',
            'password_hint' => 'Leave password fields blank if you do not want to change it.',
            'title' => 'Detalles de la cuenta',
        ],
    ],
];
