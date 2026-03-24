<?php

return [
    'actions' => [
        'back_to_dashboard' => 'Back to Panel de control',
        'destructive_confirm_single' => 'Esta acción no se puede deshacer. Está a punto de afectar permanentemente a :item.',
        'destructive_confirm_bulk' => 'Esta acción no se puede deshacer. Está a punto de afectar permanentemente todos los registros seleccionados.',
        'destructive_item_fallback' => 'este registro',
    ],
    'errors' => [
        'eyebrow' => 'Error :status',
        403 => [
            'title' => 'You do not have permission to view this page',
            'description' => 'Your account does not currently have access to this area. If you believe this is a mistake, contact your administrator or return to the correct Panel de control.',
        ],
        404 => [
            'title' => 'The page you are looking for does not exist',
            'description' => 'The link may be outdated, incomplete, or no longer available. Return to your Panel de control to continue working safely.',
        ],
        500 => [
            'title' => 'Something went wrong on our side',
            'description' => 'We could not complete that request right now. Please try again in a moment or contact support if the problem continues.',
        ],
    ],
    'impersonation' => [
        'eyebrow' => 'Impersonation activo',
        'heading' => 'You are impersonating this account',
        'actions' => [
            'stop' => 'Stop impersonating',
        ],
    ],
    'navigation' => [
        'groups' => [
            'platform' => 'Platform',
            'properties' => 'Properties',
            'billing' => 'Billing',
            'reports' => 'Informes',
            'my_home' => 'My Home',
            'organization' => 'Organización',
            'account' => 'Account',
        ],
        'items' => [
            'organizations' => 'Organizacións',
            'users' => 'Users',
            'subscriptions' => 'Suscripcións',
            'languages' => 'Languages',
            'translations' => 'Translations',
            'translation_management' => 'Translation Management',
            'system_configuration' => 'System Configuration',
            'platform_notifications' => 'Platform Notifications',
            'audit_logs' => 'Audit Logs',
            'security_violations' => 'Security Violations',
            'integration_health' => 'Integration Health',
            'profile' => 'Profile',
            'reports' => 'Informes',
            'settings' => 'Configuración',
            'organization_users' => 'Organization Users',
            'projects' => 'Projects',
            'tasks' => 'Tasks',
            'task_assignments' => 'Task Assignments',
            'time_entries' => 'Time Entries',
            'comments' => 'Comments',
            'comment_reactions' => 'Comment Reactions',
            'attachments' => 'Attachments',
            'tags' => 'Tags',
            'property_assignments' => 'Property Assignments',
            'invoice_items' => 'Invoice Items',
            'invoice_payments' => 'Invoice Payments',
            'invoice_reminder_logs' => 'Invoice Reminder Logs',
            'invoice_email_logs' => 'Invoice Email Logs',
            'subscription_payments' => 'Subscription Payments',
            'subscription_renewals' => 'Subscription Renewals',
        ],
    ],
    'notifications' => [
        'heading' => 'Notifications',
        'unread_count' => '{0} No unread notifications|{1} :count unread notification|[2,*] :count unread notifications',
        'actions' => [
            'toggle' => 'Toggle notifications',
            'mark_all_read' => 'Mark all as read',
        ],
        'page' => [
            'eyebrow' => 'Notificaciones de plataforma',
            'title' => 'Notificaciones de Plataforma',
            'description' => 'Revise las notificaciones más recientes dirigidas al panel de control del superadministrador.',
            'stats' => [
                'unread' => 'Notificaciones sin leer',
                'unread_description' => 'Notificaciones que la cuenta actual de superadministrador todavía debe revisar.',
                'total' => 'Total de notificaciones',
                'total_description' => 'Historial reciente de notificaciones dirigido a esta cuenta de control.',
            ],
            'actions' => [
                'open' => 'Abrir',
                'mark_read' => 'Marcar como leída',
                'viewed' => 'Vista',
            ],
            'messages' => [
                'opened' => 'Notificación abierta.',
                'marked_all_read' => 'Todas las notificaciones fueron marcadas como leídas.',
            ],
        ],
        'status' => [
            'read' => 'Read',
            'unread' => 'Unread',
        ],
        'empty' => [
            'heading' => 'No notifications yet',
            'body' => 'New updates will appear here when the product has something to share.',
        ],
        'defaults' => [
            'title' => 'Notification',
            'body' => 'Notification details are available.',
            'just_now' => 'just now',
        ],
    ],
    'profile' => [
        'title' => 'My Profile',
        'eyebrow' => 'Account Space',
        'heading' => 'My Profile',
        'description' => 'Review your account identity, preferred language, and signed-in context from one shared destination.',
        'personal_information' => [
            'heading' => 'Personal Information',
            'description' => 'Keep your display name, email address, and preferred language up to date.',
        ],
        'password' => [
            'heading' => 'Change Password',
            'description' => 'Set a new password for your account and confirm it before saving.',
        ],
        'fields' => [
            'name' => 'Name',
            'email' => 'Email',
            'locale' => 'Language',
            'current_password' => 'Current Password',
            'password' => 'New Password',
            'password_confirmation' => 'Confirm New Password',
        ],
        'actions' => [
            'save' => 'Save Profile',
            'update_password' => 'Update Password',
        ],
        'messages' => [
            'saved' => 'Your profile has been updated.',
            'password_updated' => 'Your password has been updated.',
        ],
    ],
    'roles' => [
        'superadmin' => 'Superadmin',
        'admin' => 'Admin',
        'manager' => 'Manager',
        'tenant' => 'Inquilino',
    ],
    'search' => [
        'label' => 'Global search',
        'placeholder' => 'Buscar cualquier cosa',
        'groups' => [
            'organizations' => 'Organizacións',
            'buildings' => 'Edificios',
            'properties' => 'Properties',
            'tenants' => 'Inquilinos',
            'invoices' => 'Facturas',
            'readings' => 'Readings',
        ],
        'empty' => [
            'heading' => 'No results yet',
            'body' => 'Search results will appear here when matching records are available in your current workspace.',
        ],
    ],
    'settings' => [
        'title' => 'Configuración',
        'organization' => [
            'heading' => 'Organización Configuración',
            'description' => 'Manage billing contacts and the payment details shown to future users.',
            'fields' => [
                'billing_contact_name' => 'Billing Contact Name',
                'billing_contact_email' => 'Billing Contact Email',
                'billing_contact_phone' => 'Billing Contact Phone',
                'payment_instructions' => 'Payment Instructions',
                'invoice_footer' => 'Factura Footer',
            ],
            'actions' => [
                'save' => 'Save Organización Configuración',
            ],
        ],
        'notifications' => [
            'heading' => 'Notification Preferences',
            'description' => 'Choose which operational emails admins should receive for this organización.',
            'fields' => [
                'new_invoice_generated' => 'New factura generated',
                'invoice_overdue' => 'Factura vencido',
                'tenant_submits_reading' => 'Inquilino submits reading',
                'subscription_expiring' => 'Suscripción expiring',
            ],
            'help' => [
                'new_invoice_generated' => 'Email admins when a newly generated factura is finalizado.',
                'invoice_overdue' => 'Email admins when vencido factura reminder workflows are triggered.',
                'tenant_submits_reading' => 'Email admins when a inquilino submits a fresh lectura de medidor.',
                'subscription_expiring' => 'Email admins before the current suscripción expires.',
            ],
            'actions' => [
                'save' => 'Save Notification Preferences',
            ],
        ],
        'subscription' => [
            'heading' => 'Suscripción',
            'description' => 'Renew the current plan and refresh usage limits for the organización.',
            'fields' => [
                'plan' => 'Plan',
                'duration' => 'Duration',
            ],
            'plans' => [
                'basic' => 'Basic',
                'professional' => 'Professional',
                'enterprise' => 'Enterprise',
            ],
            'durations' => [
                'monthly' => 'Monthly',
                'quarterly' => 'Quarterly',
                'yearly' => 'Yearly',
            ],
            'actions' => [
                'renew' => 'Renew Suscripción',
            ],
        ],
        'messages' => [
            'organization_saved' => 'Organización configuración have been updated.',
            'notifications_saved' => 'Notification preferences have been updated.',
            'subscription_renewed' => 'Suscripción has been renewed.',
        ],
    ],
];
