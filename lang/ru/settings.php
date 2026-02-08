<?php

return array (
  'description' => 'Description',
  'forms' => 
  array (
    'app_name' => 'App Name',
    'app_name_hint' => 'App Name Hint',
    'save' => 'Save',
    'timezone' => 'Timezone',
    'timezone_hint' => 'Timezone Hint',
    'title' => 'Title',
    'warnings' => 
    array (
      'backups' => 'Backups',
      'env' => 'Env',
      'note_title' => 'Note Title',
    ),
  ),
  'maintenance' => 
  array (
    'clear_cache' => 'Clear Cache',
    'clear_cache_description' => 'Clear Cache Description',
    'run_backup' => 'Run Backup',
    'run_backup_description' => 'Run Backup Description',
    'title' => 'Title',
  ),
  'stats' => 
  array (
    'cache_size' => 'Cache Size',
    'db_size' => 'Db Size',
    'invoices' => 'Invoices',
    'meters' => 'Meters',
    'properties' => 'Properties',
    'users' => 'Users',
  ),
  'system_info' => 
  array (
    'database' => 'Database',
    'database_sqlite' => 'Database Sqlite',
    'environment' => 'Environment',
    'laravel' => 'Laravel',
    'php' => 'Php',
    'timezone' => 'Timezone',
    'title' => 'Title',
  ),
  'title' => 'Title',
  'validation' => 
  array (
    'app_name' => 
    array (
      'max' => 'Max',
      'string' => 'String',
      'regex' => 'The application name may only contain letters, numbers, spaces, hyphens, underscores, and dots.',
    ),
    'timezone' => 
    array (
      'in' => 'In',
      'string' => 'String',
    ),
    'language' => 
    array (
      'in' => 'The selected language is not supported.',
    ),
    'date_format' => 
    array (
      'in' => 'The selected date format is invalid.',
    ),
    'currency' => 
    array (
      'size' => 'The currency code must be exactly 3 characters.',
      'in' => 'The selected currency is not supported.',
    ),
    'invoice_due_days' => 
    array (
      'min' => 'Invoice due days must be at least 1 day.',
      'max' => 'Invoice due days may not be greater than 90 days.',
    ),
  ),
  'attributes' => 
  array (
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
  ),
);
