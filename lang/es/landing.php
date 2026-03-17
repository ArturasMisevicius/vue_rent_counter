<?php

return [
    'brand' => [
        'tagline' => 'Entorno de pruebas de operaciones inmobiliarias',
        'kicker' => 'Entrada pública',
    ],
    'hero' => [
        'eyebrow' => 'Inicio para testers',
        'title' => 'Plataforma de operaciones inmobiliarias presentada como un laboratorio de pruebas guiado.',
        'description' => 'Tenanto está evolucionando hacia una plataforma multirrol para incorporación, facturación, medición, autoservicio de inquilinos y gobierno de la plataforma. Esta página pública ayuda a los futuros usuarios a entender el producto y ofrece a los testers un punto claro para validar localización y flujos de acceso.',
        'chips' => [
            'Entorno de pruebas activo',
            '4 roles de interfaz',
            'Experiencia pública localizada',
        ],
    ],
    'roles' => [
        'heading' => 'Resumen de roles',
        'description' => 'Tenanto se está construyendo alrededor de cuatro experiencias coordinadas, cada una con una responsabilidad operativa clara.',
        'items' => [
            [
                'name' => 'Superadmin',
                'description' => 'Control de la plataforma, gobernanza, supervisión de traducciones y monitoreo del despliegue.',
            ],
            [
                'name' => 'Admin',
                'description' => 'Configuración de la organización, incorporación, preparación de facturación y propiedad operativa.',
            ],
            [
                'name' => 'Manager',
                'description' => 'Ejecución diaria en edificios, propiedades, lecturas y trabajo orientado al inquilino.',
            ],
            [
                'name' => 'Tenant',
                'description' => 'Acceso de autoservicio a lecturas, facturas, perfil y contexto de la propiedad.',
            ],
        ],
    ],
    'tester' => [
        'heading' => 'Para testers del sistema',
        'description' => 'Usa esta página como punto de partida público para validar la experiencia actual de invitados.',
        'items' => [
            'Verifica los puntos de entrada de Login y Register.',
            'Cambia entre inglés, lituano, español y ruso.',
            'Confirma que el texto público coincide con el modelo de roles previsto.',
            'Comprueba que la hoja de ruta comunica con honestidad las próximas superficies del producto.',
        ],
    ],
    'roadmap' => [
        'heading' => 'En qué se está convirtiendo Tenanto',
        'lead' => 'Tenanto se está diseñando para futuros operadores, organizaciones e inquilinos.',
        'description' => 'Los planes actuales amplían Tenanto desde la autenticación pública hasta una plataforma completa de operaciones inmobiliarias.',
        'status' => 'Planificado',
        'items' => [
            [
                'title' => 'Shell compartido',
                'description' => 'Un shell autenticado y sensible al rol para mantener alineados navegación, idioma y contexto de cuenta.',
            ],
            [
                'title' => 'Plano de control de la plataforma',
                'description' => 'Herramientas de superadmin para gobernanza, monitoreo, organizaciones, suscripciones y traducciones.',
            ],
            [
                'title' => 'Operaciones de la organización',
                'description' => 'Flujos de admin y manager para edificios, propiedades, medidores, facturas, proveedores e informes.',
            ],
            [
                'title' => 'Autoservicio del inquilino',
                'description' => 'Un portal móvil para historial de facturas, envío de lecturas y mantenimiento del perfil.',
            ],
        ],
    ],
    'cta' => [
        'heading' => '¿Listo para explorar el flujo público?',
        'description' => 'Inicia sesión si ya tienes acceso, o registra una nueva cuenta Admin para comenzar el proceso de incorporación.',
        'note' => 'El acceso de Manager y Tenant se habilita mediante invitaciones de la organización.',
        'login' => 'Login',
        'register' => 'Register',
    ],
];
