<?php

return [
    'title' => 'Comienza tu prueba gratuita',
    'subtitle' => 'Configura tu organización para abrir tu espacio de administración.',
    'trial_badge' => 'Prueba gratuita de 14 días',
    'trial_message' => 'Estás a un paso de tu espacio de administración de Tenanto. Crea tu organización ahora y activaremos la prueba gratuita de inmediato.',
    'organization_name_label' => 'Nombre de la organización',
    'submit_button' => 'Activar prueba gratuita',
    'submit_button_loading' => 'Activando la prueba gratuita...',
    'tour' => [
        'badge' => 'Guía del sistema',
        'title' => 'Bienvenido a tu espacio de trabajo',
        'subtitle' => 'Una guía breve explica dónde están las herramientas principales y cómo debe usarse cada parte de la página.',
        'progress_label' => 'Progreso de la guía',
        'step_count' => 'Paso :current de :total',
        'actions' => [
            'back' => 'Atrás',
            'close' => 'Cerrar guía',
            'finish' => 'Finalizar',
            'later' => 'Más tarde',
            'next' => 'Siguiente',
            'open' => 'Guía',
        ],
        'roles' => [
            'admin' => [
                'steps' => [
                    'workspace' => [
                        'title' => 'Panel y espacio de trabajo',
                        'body' => 'El panel es el punto de inicio para el trabajo de la organización. Resume propiedades, facturas, lecturas y límites de suscripción para ver primero lo que necesita atención.',
                        'detail' => 'Úsalo como vista diaria antes de abrir páginas detalladas desde el menú lateral.',
                    ],
                    'navigation' => [
                        'title' => 'Menú lateral, búsqueda e idioma',
                        'body' => 'El menú izquierdo agrupa herramientas de propiedades, facturación, informes y cuenta. La búsqueda global está arriba, y el selector de idioma cambia los textos de la interfaz para tu cuenta.',
                        'detail' => 'Abre registros desde el menú cuando conozcas el módulo, o busca por nombre, número, inquilino, factura o contador.',
                    ],
                    'workflows' => [
                        'title' => 'Propiedades e inquilinos',
                        'body' => 'Edificios, propiedades, inquilinos, contadores y lecturas están conectados. Empieza por el edificio o la propiedad, luego revisa inquilinos, contadores e historial de lecturas desde las páginas relacionadas.',
                        'detail' => 'Crea y edita datos desde las páginas del módulo; evita duplicados revisando primero la propiedad relacionada.',
                    ],
                    'activity' => [
                        'title' => 'Facturación e informes',
                        'body' => 'Facturas, tarifas, proveedores, configuraciones de servicios y servicios públicos controlan la facturación. Los informes sirven para revisión y controles operativos.',
                        'detail' => 'Mantén actualizadas las configuraciones de proveedores y tarifas antes de generar o revisar actividad de facturas.',
                    ],
                    'profile' => [
                        'title' => 'Perfil, ajustes y gestores',
                        'body' => 'Usa Perfil para tus propios datos. Ajustes controla preferencias de facturación de la organización, y Usuarios de la organización permite revisar el acceso de gestores.',
                        'detail' => 'Los gestores pueden limitarse por matriz de permisos, así que concede solo las acciones de crear, editar y eliminar que deban realizar.',
                    ],
                ],
            ],
            'manager' => [
                'steps' => [
                    'workspace' => [
                        'title' => 'Tu espacio asignado',
                        'body' => 'El panel muestra la información de la organización que puedes revisar. Tu administrador controla qué acciones de crear, editar y eliminar tienes disponibles.',
                        'detail' => 'Si falta un botón o una acción de escritura está bloqueada, probablemente la matriz de permisos del gestor no concede esa acción.',
                    ],
                    'navigation' => [
                        'title' => 'Menú y búsqueda',
                        'body' => 'Usa el menú lateral para moverte entre propiedades, lecturas, facturas, proveedores e informes. La búsqueda ayuda a abrir registros conocidos más rápido.',
                        'detail' => 'El menú solo muestra las secciones que tu rol puede usar en esta organización.',
                    ],
                    'workflows' => [
                        'title' => 'Propiedades, contadores y lecturas',
                        'body' => 'Usa las propiedades como base del trabajo operativo. Los contadores y lecturas deben revisarse contra la propiedad asignada antes de editar.',
                        'detail' => 'Al enviar lecturas, confirma el contador, la fecha, el valor y el mensaje de validación antes de guardar.',
                    ],
                    'activity' => [
                        'title' => 'Facturación e informes',
                        'body' => 'Las páginas de facturación son visibles cuando tus permisos de gestor incluyen trabajo de facturación. Los informes están disponibles para revisión y seguimiento.',
                        'detail' => 'Si la facturación está oculta, pide a un administrador que conceda los permisos necesarios.',
                    ],
                    'profile' => [
                        'title' => 'Perfil y cuenta',
                        'body' => 'Usa Perfil para actualizar tu nombre, email, teléfono, contraseña, idioma y avatar. Los ajustes de organización quedan para administradores.',
                        'detail' => 'Mantén tu perfil actualizado para que las notificaciones y el historial de auditoría te identifiquen correctamente.',
                    ],
                ],
            ],
            'tenant' => [
                'steps' => [
                    'workspace' => [
                        'title' => 'Inicio del inquilino',
                        'body' => 'Tu página inicial muestra la propiedad asignada, lecturas recientes, estado de facturas y acciones rápidas para tareas comunes del inquilino.',
                        'detail' => 'Empieza aquí cuando necesites revisar cambios o continuar una tarea mensual regular.',
                    ],
                    'navigation' => [
                        'title' => 'Menú superior y menú móvil',
                        'body' => 'El menú superior contiene Inicio, Propiedad, Lecturas y Facturas. En móvil, abre Menú para acceder a las mismas páginas, búsqueda, perfil y cierre de sesión.',
                        'detail' => 'Usa el mismo menú en cada página del inquilino; el elemento activo muestra dónde estás ahora.',
                    ],
                    'workflows' => [
                        'title' => 'Enviar lecturas',
                        'body' => 'La página Lecturas permite elegir un contador asignado, introducir fecha y valor, previsualizar el cambio de consumo y enviar todo en un solo flujo.',
                        'detail' => 'Lee los mensajes de validación antes de enviar; explican por qué un valor puede ser demasiado bajo, demasiado alto, duplicado o estar fuera del periodo esperado.',
                    ],
                    'activity' => [
                        'title' => 'Facturas y detalles de propiedad',
                        'body' => 'Facturas muestra historial de facturación, estado de pago, líneas e instrucciones de pago. Detalles de propiedad muestra tu unidad asignada y la información del edificio.',
                        'detail' => 'Abre los detalles de la factura cuando necesites importes, fechas, servicios o información de contacto de facturación.',
                    ],
                    'profile' => [
                        'title' => 'Cuenta e idioma',
                        'body' => 'Usa la página de cuenta para actualizar datos de contacto, idioma preferido, contraseña y avatar. El selector de idioma allí cambia las etiquetas de la interfaz del inquilino.',
                        'detail' => 'Después de cambiar idioma o avatar, el menú del inquilino y las páginas se actualizan con tus últimos ajustes de cuenta.',
                    ],
                ],
            ],
        ],
    ],
];
