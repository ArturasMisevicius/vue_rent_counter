<?php

return [
    'description' => 'Description',
    'system_page' => [
        'description' => 'Manage platform-wide settings and configuration. Changes will take effect immediately after saving.',
        'notes' => [
            'backup_schedule' => 'Backup schedule uses cron expression format (e.g., "0 2 * * *" for daily at 2 AM)',
            'email' => 'Email settings require valid SMTP credentials to send notifications',
            'feature_flags' => 'Feature flags affect all organizations unless overridden',
            'password_policy' => 'Password policy changes apply to new passwords only',
            'queue' => 'Queue configuration changes may require worker restart',
        ],
        'notes_title' => 'Configuration Notes',
        'title' => 'System Configuration',
        'warning_body' => 'Modifying system settings can affect platform stability and user experience. Always test changes in a development environment first. Export your current configuration before making significant changes.',
        'warning_title' => 'Important Warning',
    ],
    'forms' => [
        'app_name' => 'App Name',
        'app_name_hint' => 'App Name Hint',
        'save' => 'Save',
        'timezone' => 'Timezone',
        'timezone_hint' => 'Timezone Hint',
        'title' => 'Title',
        'warnings' => [
            'backups' => 'Backups',
            'env' => 'Env',
            'note_title' => 'Note Title',
        ],
    ],
    'maintenance' => [
        'clear_cache' => 'Clear Cache',
        'clear_cache_description' => 'Clear Cache Description',
        'run_backup' => 'Run Backup',
        'run_backup_description' => 'Run Backup Description',
        'title' => 'Title',
    ],
    'stats' => [
        'cache_size' => 'Cache Size',
        'db_size' => 'Db Size',
        'invoices' => 'Invoices',
        'meters' => 'Meters',
        'properties' => 'Properties',
        'users' => 'Users',
    ],
    'title' => 'Title',
    'validation' => [
        'app_name' => [
            'max' => 'The application name may not be greater than 255 characters.',
            'string' => 'The application name must be a string.',
            'regex' => 'The application name may only contain letters, numbers, spaces, hyphens, underscores, and dots.',
        ],
        'timezone' => [
            'in' => 'The selected timezone is invalid.',
            'string' => 'The timezone must be a string.',
        ],
        'language' => [
            'in' => 'The selected language is not supported.',
        ],
        'date_format' => [
            'in' => 'The selected date format is invalid.',
        ],
        'currency' => [
            'size' => 'The currency code must be exactly 3 characters.',
            'in' => 'The selected currency is not supported.',
        ],
        'invoice_due_days' => [
            'min' => 'Invoice due days must be at least 1 day.',
            'max' => 'Invoice due days may not be greater than 90 days.',
        ],
    ],
    'attributes' => [
        'app_name' => 'application name',
        'timezone' => 'timezone',
        'language' => 'language',
        'date_format' => 'date format',
        'currency' => 'currency',
        'notifications_enabled' => 'notifications enabled',
        'email_notifications' => 'email notifications',
        'sms_notifications' => 'SMS notifications',
        'invoice_due_days' => 'invoice due days',
        'auto_generate_invoices' => 'auto-generate invoices',
        'maintenance_mode' => 'maintenance mode',
    ],
];
