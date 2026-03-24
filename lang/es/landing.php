<?php

return [
    'brand' => [
        'tagline' => 'Centro de operaciones de inquilinos y utilidades',
        'kicker' => 'Espacio previo al acceso',
    ],
    'cta' => [
        'heading' => 'Elige el punto de entrada correcto',
        'description' => 'Inicia sesión para continuar operaciones activas, o registra una cuenta Admin para lanzar una nueva organización.',
        'note' => 'El acceso de Manager y Tenant se mantiene por invitación para preservar onboarding controlado y aislamiento de inquilinos.',
        'login' => 'Iniciar sesión',
        'register' => 'Registrarse',
    ],
    'hero' => [
        'eyebrow' => 'Una plataforma para cada rol inmobiliario',
        'title' => 'Gestiona alquileres, facturación y servicio al inquilino desde una sola superficie clara.',
        'description' => 'Tenanto unifica onboarding de organizaciones, gestión de edificios y propiedades, procesos de medidores y facturas, y autoservicio de inquilinos. Esta página pública muestra el flujo completo antes del inicio de sesión.',
        'chips' => [
            0 => 'Espacios de trabajo por rol',
            1 => 'Flujo invitado y auth localizado',
            2 => 'Proceso operativo de lectura a factura',
            3 => 'Preparación de autoservicio del inquilino',
        ],
    ],
    'roadmap' => [
        'heading' => 'Hoja de ruta operativa',
        'lead' => 'Tenanto está evolucionando de acceso público a una plataforma operativa completa y consciente de roles.',
        'description' => 'Cada línea de la hoja de ruta cierra una brecha concreta entre onboarding, facturación, gobernanza y experiencia del inquilino.',
        'status' => 'Planificado',
        'items' => [
            0 => [
                'title' => 'Shell autenticado unificado',
                'description' => 'Una base común para navegación, localización, notificaciones y contexto de cuenta en todos los roles.',
            ],
            1 => [
                'title' => 'Capa de gobierno de plataforma',
                'description' => 'Herramientas superadmin para organizaciones, suscripciones, gestión de traducciones y monitoreo de seguridad.',
            ],
            2 => [
                'title' => 'Núcleo operativo de la organización',
                'description' => 'Flujos de Admin y Manager para edificios, propiedades, proveedores, medidores, facturas y reportes.',
            ],
            3 => [
                'title' => 'Portal de servicio para inquilinos',
                'description' => 'Área móvil para historial de facturas, envío de lecturas, actualizaciones de perfil y contexto de propiedad.',
            ],
            4 => [
                'title' => 'Reglas transversales de plataforma',
                'description' => 'Validación compartida, políticas y reglas de suscripción que mantienen confiable cada recorrido por rol.',
            ],
        ],
    ],
    'roles' => [
        'heading' => 'Espacios de trabajo por rol',
        'description' => 'Cada rol tiene una responsabilidad clara y las reglas compartidas mantienen alineados todos los traspasos operativos.',
        'items' => [
            0 => [
                'name' => 'Superadmin',
                'description' => 'Controla salud de la plataforma, gobierno global, cumplimiento de suscripciones y política de idiomas.',
            ],
            1 => [
                'name' => 'Admin',
                'description' => 'Configura organizaciones, define estándares operativos y supervisa preparación de facturación.',
            ],
            2 => [
                'name' => 'Manager',
                'description' => 'Ejecuta operaciones diarias en edificios, lecturas, tareas de campo y trabajo con inquilinos.',
            ],
            3 => [
                'name' => 'Tenant',
                'description' => 'Usa un portal directo para facturas, envío de lecturas y mantenimiento de perfil.',
            ],
        ],
    ],
    'tester' => [
        'heading' => 'Checklist de lanzamiento',
        'description' => 'Usa este checklist para validar la experiencia pública antes de pasar a flujos autenticados.',
        'items' => [
            0 => 'Verifica que los accesos de iniciar sesión y registro sean visibles y consistentes.',
            1 => 'Cambia el idioma y valida que estructura y CTA mantengan la misma intención.',
            2 => 'Confirma que cada tarjeta de rol describa responsabilidad sin superposición.',
            3 => 'Revisa la hoja de ruta y valida que refleje el alcance real de implementación.',
        ],
    ],
];
