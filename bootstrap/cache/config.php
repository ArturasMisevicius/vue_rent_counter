<?php return array (
  'hashing' => 
  array (
    'driver' => 'bcrypt',
    'bcrypt' => 
    array (
      'rounds' => '12',
      'verify' => true,
      'limit' => NULL,
    ),
    'argon' => 
    array (
      'memory' => 65536,
      'threads' => 1,
      'time' => 4,
      'verify' => true,
    ),
    'rehash_on_login' => true,
  ),
  'concurrency' => 
  array (
    'default' => 'process',
  ),
  'broadcasting' => 
  array (
    'default' => 'log',
    'connections' => 
    array (
      'reverb' => 
      array (
        'driver' => 'reverb',
        'key' => NULL,
        'secret' => NULL,
        'app_id' => NULL,
        'options' => 
        array (
          'host' => NULL,
          'port' => 443,
          'scheme' => 'https',
          'useTLS' => true,
        ),
        'client_options' => 
        array (
        ),
      ),
      'pusher' => 
      array (
        'driver' => 'pusher',
        'key' => NULL,
        'secret' => NULL,
        'app_id' => NULL,
        'options' => 
        array (
          'cluster' => NULL,
          'host' => 'api-mt1.pusher.com',
          'port' => 443,
          'scheme' => 'https',
          'encrypted' => true,
          'useTLS' => true,
        ),
        'client_options' => 
        array (
        ),
      ),
      'ably' => 
      array (
        'driver' => 'ably',
        'key' => NULL,
      ),
      'log' => 
      array (
        'driver' => 'log',
      ),
      'null' => 
      array (
        'driver' => 'null',
      ),
    ),
  ),
  'view' => 
  array (
    'paths' => 
    array (
      0 => '/Users/andrejprus/Herd/tenanto/resources/views',
    ),
    'compiled' => '/Users/andrejprus/Herd/tenanto/storage/framework/views',
  ),
  'cors' => 
  array (
    'paths' => 
    array (
      0 => 'api/*',
      1 => 'sanctum/csrf-cookie',
    ),
    'allowed_methods' => 
    array (
      0 => '*',
    ),
    'allowed_origins' => 
    array (
      0 => '*',
    ),
    'allowed_origins_patterns' => 
    array (
    ),
    'allowed_headers' => 
    array (
      0 => '*',
    ),
    'exposed_headers' => 
    array (
    ),
    'max_age' => 0,
    'supports_credentials' => false,
  ),
  'api_tokens' => 
  array (
    'cache' => 
    array (
      'ttl' => 900,
      'prefix' => 'api_tokens:',
    ),
    'monitoring' => 
    array (
      'enabled' => true,
      'suspicious_threshold' => 10,
      'alert_channels' => 
      array (
        0 => 'log',
      ),
    ),
    'pruning' => 
    array (
      'enabled' => true,
      'hours_after_expiration' => 24,
      'schedule' => 'daily',
    ),
    'security' => 
    array (
      'require_active_user' => true,
      'check_suspension' => true,
      'log_usage' => true,
    ),
  ),
  'app' => 
  array (
    'name' => 'Laravel',
    'env' => 'local',
    'debug' => true,
    'url' => 'http://localhost',
    'frontend_url' => 'http://localhost:3000',
    'asset_url' => NULL,
    'timezone' => 'UTC',
    'locale' => 'en',
    'fallback_locale' => 'en',
    'faker_locale' => 'en_US',
    'cipher' => 'AES-256-CBC',
    'key' => 'base64:bL7nbidefWIBBgEq/tcbbQXJJKYzrWXly+cwOTKqYDU=',
    'previous_keys' => 
    array (
    ),
    'maintenance' => 
    array (
      'driver' => 'file',
      'store' => 'database',
    ),
    'providers' => 
    array (
      0 => 'Illuminate\\Foundation\\Providers\\FoundationServiceProvider',
      1 => 'Illuminate\\Auth\\AuthServiceProvider',
      2 => 'Illuminate\\Broadcasting\\BroadcastServiceProvider',
      3 => 'Illuminate\\Bus\\BusServiceProvider',
      4 => 'Illuminate\\Cache\\CacheServiceProvider',
      5 => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
      6 => 'Illuminate\\Cookie\\CookieServiceProvider',
      7 => 'Illuminate\\Database\\DatabaseServiceProvider',
      8 => 'Illuminate\\Encryption\\EncryptionServiceProvider',
      9 => 'Illuminate\\Filesystem\\FilesystemServiceProvider',
      10 => 'Illuminate\\Hashing\\HashServiceProvider',
      11 => 'Illuminate\\Mail\\MailServiceProvider',
      12 => 'Illuminate\\Notifications\\NotificationServiceProvider',
      13 => 'Illuminate\\Pagination\\PaginationServiceProvider',
      14 => 'Illuminate\\Pipeline\\PipelineServiceProvider',
      15 => 'Illuminate\\Queue\\QueueServiceProvider',
      16 => 'Illuminate\\Redis\\RedisServiceProvider',
      17 => 'Illuminate\\Auth\\Passwords\\PasswordResetServiceProvider',
      18 => 'Illuminate\\Session\\SessionServiceProvider',
      19 => 'Illuminate\\Translation\\TranslationServiceProvider',
      20 => 'Illuminate\\Validation\\ValidationServiceProvider',
      21 => 'Illuminate\\View\\ViewServiceProvider',
      22 => 'Livewire\\LivewireServiceProvider',
      23 => 'App\\Providers\\AppServiceProvider',
      24 => 'App\\Providers\\DatabaseServiceProvider',
      25 => 'App\\Providers\\RepositoryServiceProvider',
      26 => 'App\\Providers\\ValidationServiceProvider',
      27 => 'Spatie\\Permission\\PermissionServiceProvider',
      28 => 'Filament\\FilamentServiceProvider',
      29 => 'Filament\\Actions\\ActionsServiceProvider',
      30 => 'Filament\\Forms\\FormsServiceProvider',
      31 => 'Filament\\Infolists\\InfolistsServiceProvider',
      32 => 'Filament\\Notifications\\NotificationsServiceProvider',
      33 => 'Filament\\Schemas\\SchemasServiceProvider',
      34 => 'Filament\\Support\\SupportServiceProvider',
      35 => 'Filament\\Tables\\TablesServiceProvider',
      36 => 'Filament\\Widgets\\WidgetsServiceProvider',
      37 => 'App\\Providers\\Filament\\AdminPanelProvider',
      38 => 'App\\Providers\\Filament\\SuperadminPanelProvider',
      39 => 'App\\Providers\\Filament\\TenantPanelProvider',
    ),
    'aliases' => 
    array (
      'TenantContext' => 'App\\Facades\\TenantContext',
    ),
  ),
  'auth' => 
  array (
    'defaults' => 
    array (
      'guard' => 'web',
      'passwords' => 'users',
    ),
    'guards' => 
    array (
      'web' => 
      array (
        'driver' => 'session',
        'provider' => 'users',
      ),
      'admin' => 
      array (
        'driver' => 'session',
        'provider' => 'users',
      ),
      'sanctum' => 
      array (
        'driver' => 'sanctum',
        'provider' => 'users',
      ),
    ),
    'providers' => 
    array (
      'users' => 
      array (
        'driver' => 'eloquent',
        'model' => 'App\\Models\\User',
      ),
    ),
    'passwords' => 
    array (
      'users' => 
      array (
        'provider' => 'users',
        'table' => 'password_reset_tokens',
        'expire' => 60,
        'throttle' => 60,
      ),
    ),
    'password_timeout' => 10800,
  ),
  'backup' => 
  array (
    'backup' => 
    array (
      'name' => 'Laravel',
      'source' => 
      array (
        'files' => 
        array (
          'include' => 
          array (
            0 => '/Users/andrejprus/Herd/tenanto',
          ),
          'exclude' => 
          array (
            0 => '/Users/andrejprus/Herd/tenanto/vendor',
            1 => '/Users/andrejprus/Herd/tenanto/node_modules',
          ),
          'follow_links' => false,
          'ignore_unreadable_directories' => false,
          'relative_path' => NULL,
        ),
        'databases' => 
        array (
          0 => 'sqlite',
        ),
      ),
      'database_dump_compressor' => NULL,
      'database_dump_file_timestamp_format' => NULL,
      'database_dump_filename_base' => 'database',
      'database_dump_file_extension' => '',
      'destination' => 
      array (
        'compression_method' => -1,
        'compression_level' => 9,
        'filename_prefix' => '',
        'disks' => 
        array (
          0 => 'local',
        ),
      ),
      'temporary_directory' => '/Users/andrejprus/Herd/tenanto/storage/app/backup-temp',
      'password' => NULL,
      'encryption' => 'default',
      'tries' => 1,
      'retry_delay' => 0,
    ),
    'notifications' => 
    array (
      'notifications' => 
      array (
        'Spatie\\Backup\\Notifications\\Notifications\\BackupHasFailedNotification' => 
        array (
          0 => 'mail',
        ),
        'Spatie\\Backup\\Notifications\\Notifications\\UnhealthyBackupWasFoundNotification' => 
        array (
          0 => 'mail',
        ),
        'Spatie\\Backup\\Notifications\\Notifications\\CleanupHasFailedNotification' => 
        array (
          0 => 'mail',
        ),
        'Spatie\\Backup\\Notifications\\Notifications\\BackupWasSuccessfulNotification' => 
        array (
          0 => 'mail',
        ),
        'Spatie\\Backup\\Notifications\\Notifications\\HealthyBackupWasFoundNotification' => 
        array (
          0 => 'mail',
        ),
        'Spatie\\Backup\\Notifications\\Notifications\\CleanupWasSuccessfulNotification' => 
        array (
          0 => 'mail',
        ),
      ),
      'notifiable' => 'Spatie\\Backup\\Notifications\\Notifiable',
      'mail' => 
      array (
        'to' => 'your@example.com',
        'from' => 
        array (
          'address' => 'hello@example.com',
          'name' => 'Laravel',
        ),
      ),
      'slack' => 
      array (
        'webhook_url' => '',
        'channel' => NULL,
        'username' => NULL,
        'icon' => NULL,
      ),
      'discord' => 
      array (
        'webhook_url' => '',
        'username' => '',
        'avatar_url' => '',
      ),
    ),
    'monitor_backups' => 
    array (
      0 => 
      array (
        'name' => 'Laravel',
        'disks' => 
        array (
          0 => 'local',
        ),
        'health_checks' => 
        array (
          'Spatie\\Backup\\Tasks\\Monitor\\HealthChecks\\MaximumAgeInDays' => 1,
          'Spatie\\Backup\\Tasks\\Monitor\\HealthChecks\\MaximumStorageInMegabytes' => 5000,
        ),
      ),
    ),
    'cleanup' => 
    array (
      'strategy' => 'Spatie\\Backup\\Tasks\\Cleanup\\Strategies\\DefaultStrategy',
      'default_strategy' => 
      array (
        'keep_all_backups_for_days' => 7,
        'keep_daily_backups_for_days' => 16,
        'keep_weekly_backups_for_weeks' => 8,
        'keep_monthly_backups_for_months' => 4,
        'keep_yearly_backups_for_years' => 2,
        'delete_oldest_backups_when_using_more_megabytes_than' => 5000,
      ),
      'tries' => 1,
      'retry_delay' => 0,
    ),
  ),
  'billing' => 
  array (
    'rate_limit' => 
    array (
      'enabled' => true,
      'max_attempts' => 10,
      'decay_minutes' => 1,
    ),
    'water_tariffs' => 
    array (
      'default_supply_rate' => 0.97,
      'default_sewage_rate' => 1.23,
      'default_fixed_fee' => 0.85,
    ),
    'invoice' => 
    array (
      'default_due_days' => 14,
    ),
    'seasons' => 
    array (
      'summer_months' => 
      array (
        0 => 5,
        1 => 6,
        2 => 7,
        3 => 8,
        4 => 9,
      ),
    ),
    'property' => 
    array (
      'default_apartment_area' => 50,
      'default_house_area' => 120,
      'default_commercial_area' => 150,
      'min_area' => 0,
      'max_area' => 10000,
    ),
    'security' => 
    array (
      'audit_retention_days' => 90,
      'encrypt_audit_logs' => true,
      'redact_pii_in_logs' => true,
    ),
  ),
  'blade-heroicons' => 
  array (
    'prefix' => 'heroicon',
    'fallback' => '',
    'class' => '',
    'attributes' => 
    array (
    ),
  ),
  'blade-icons' => 
  array (
    'sets' => 
    array (
    ),
    'class' => '',
    'attributes' => 
    array (
    ),
    'fallback' => '',
    'components' => 
    array (
      'disabled' => false,
      'default' => 'icon',
    ),
  ),
  'cache' => 
  array (
    'default' => 'database',
    'stores' => 
    array (
      'array' => 
      array (
        'driver' => 'array',
        'serialize' => false,
      ),
      'session' => 
      array (
        'driver' => 'session',
        'key' => '_cache',
      ),
      'database' => 
      array (
        'driver' => 'database',
        'table' => 'cache',
        'connection' => NULL,
        'lock_connection' => NULL,
      ),
      'file' => 
      array (
        'driver' => 'file',
        'path' => '/Users/andrejprus/Herd/tenanto/storage/framework/cache/data',
        'lock_path' => '/Users/andrejprus/Herd/tenanto/storage/framework/cache/data',
      ),
      'memcached' => 
      array (
        'driver' => 'memcached',
        'persistent_id' => NULL,
        'sasl' => 
        array (
          0 => NULL,
          1 => NULL,
        ),
        'options' => 
        array (
        ),
        'servers' => 
        array (
          0 => 
          array (
            'host' => '127.0.0.1',
            'port' => 11211,
            'weight' => 100,
          ),
        ),
      ),
      'redis' => 
      array (
        'driver' => 'redis',
        'connection' => 'cache',
        'lock_connection' => 'default',
      ),
      'dynamodb' => 
      array (
        'driver' => 'dynamodb',
        'key' => '',
        'secret' => '',
        'region' => 'us-east-1',
        'table' => 'cache',
        'endpoint' => NULL,
      ),
      'octane' => 
      array (
        'driver' => 'octane',
      ),
      'failover' => 
      array (
        'driver' => 'failover',
        'stores' => 
        array (
          0 => 'database',
          1 => 'array',
        ),
      ),
    ),
    'prefix' => '',
  ),
  'circuit-breaker' => 
  array (
    'default' => 
    array (
      'failure_threshold' => 5,
      'recovery_timeout' => 60,
      'success_threshold' => 3,
      'cache_ttl' => 60,
      'registry_ttl' => 30,
    ),
    'services' => 
    array (
      'external-api' => 
      array (
        'failure_threshold' => 3,
        'recovery_timeout' => 30,
        'success_threshold' => 2,
      ),
      'payment-gateway' => 
      array (
        'failure_threshold' => 2,
        'recovery_timeout' => 120,
        'success_threshold' => 5,
      ),
    ),
    'logging' => 
    array (
      'enabled' => true,
      'channel' => 'default',
      'level' => 'info',
    ),
  ),
  'database' => 
  array (
    'default' => 'sqlite',
    'connections' => 
    array (
      'sqlite' => 
      array (
        'driver' => 'sqlite',
        'url' => NULL,
        'database' => '/Users/andrejprus/Herd/tenanto/database/database.sqlite',
        'prefix' => '',
        'foreign_key_constraints' => true,
        'busy_timeout' => NULL,
        'journal_mode' => NULL,
        'synchronous' => NULL,
      ),
      'mysql' => 
      array (
        'driver' => 'mysql',
        'url' => NULL,
        'host' => '127.0.0.1',
        'port' => '3306',
        'database' => 'laravel',
        'username' => 'root',
        'password' => '',
        'unix_socket' => '',
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_0900_ai_ci',
        'prefix' => '',
        'prefix_indexes' => true,
        'strict' => true,
        'engine' => NULL,
        'options' => 
        array (
        ),
      ),
      'mariadb' => 
      array (
        'driver' => 'mariadb',
        'url' => NULL,
        'host' => '127.0.0.1',
        'port' => '3306',
        'database' => 'laravel',
        'username' => 'root',
        'password' => '',
        'unix_socket' => '',
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_uca1400_ai_ci',
        'prefix' => '',
        'prefix_indexes' => true,
        'strict' => true,
        'engine' => NULL,
        'options' => 
        array (
        ),
      ),
      'pgsql' => 
      array (
        'driver' => 'pgsql',
        'url' => NULL,
        'host' => '127.0.0.1',
        'port' => '5432',
        'database' => 'laravel',
        'username' => 'root',
        'password' => '',
        'charset' => 'utf8',
        'prefix' => '',
        'prefix_indexes' => true,
        'search_path' => 'public',
        'sslmode' => 'prefer',
      ),
      'sqlsrv' => 
      array (
        'driver' => 'sqlsrv',
        'url' => NULL,
        'host' => 'localhost',
        'port' => '1433',
        'database' => 'laravel',
        'username' => 'root',
        'password' => '',
        'charset' => 'utf8',
        'prefix' => '',
        'prefix_indexes' => true,
      ),
    ),
    'migrations' => 
    array (
      'table' => 'migrations',
      'update_date_on_publish' => true,
    ),
    'redis' => 
    array (
      'client' => 'phpredis',
      'options' => 
      array (
        'cluster' => 'redis',
        'prefix' => 'laravel_database_',
      ),
      'default' => 
      array (
        'url' => NULL,
        'host' => '127.0.0.1',
        'username' => NULL,
        'password' => NULL,
        'port' => '6379',
        'database' => '0',
      ),
      'cache' => 
      array (
        'url' => NULL,
        'host' => '127.0.0.1',
        'username' => NULL,
        'password' => NULL,
        'port' => '6379',
        'database' => '1',
      ),
    ),
  ),
  'dbstan' => 
  array (
    'max_columns' => 25,
    'max_varchar_length' => 190,
    'max_json_columns' => 2,
    'large_table_mb' => 100,
    'null_ratio_threshold' => 0.5,
    'enabled_checks' => 
    array (
      0 => 'structure',
      1 => 'performance',
      2 => 'integrity',
      3 => 'architecture',
    ),
  ),
  'debugbar' => 
  array (
    'enabled' => true,
    'collect_jobs' => false,
    'except' => 
    array (
      0 => 'telescope*',
      1 => 'horizon*',
      2 => '_boost/browser-logs',
    ),
    'collectors' => 
    array (
      'phpinfo' => true,
      'messages' => true,
      'time' => true,
      'memory' => true,
      'exceptions' => true,
      'log' => true,
      'db' => true,
      'views' => true,
      'route' => true,
      'auth' => true,
      'gate' => true,
      'session' => true,
      'symfony_request' => true,
      'mail' => true,
      'laravel' => true,
      'events' => true,
      'default_request' => true,
      'logs' => true,
      'files' => false,
      'config' => true,
      'cache' => true,
      'models' => true,
      'livewire' => true,
      'jobs' => true,
      'pennant' => true,
    ),
    'options' => 
    array (
      'time' => 
      array (
        'memory_usage' => false,
      ),
      'messages' => 
      array (
        'trace' => true,
        'capture_dumps' => false,
      ),
      'memory' => 
      array (
        'reset_peak' => false,
        'with_baseline' => false,
        'precision' => 0,
      ),
      'auth' => 
      array (
        'show_name' => true,
        'show_guards' => true,
      ),
      'gate' => 
      array (
        'trace' => false,
      ),
      'db' => 
      array (
        'with_params' => true,
        'exclude_paths' => 
        array (
        ),
        'backtrace' => true,
        'backtrace_exclude_paths' => 
        array (
        ),
        'timeline' => false,
        'duration_background' => true,
        'explain' => 
        array (
          'enabled' => false,
        ),
        'hints' => false,
        'show_copy' => true,
        'only_slow_queries' => true,
        'slow_threshold' => false,
        'memory_usage' => false,
        'soft_limit' => 100,
        'hard_limit' => 500,
      ),
      'mail' => 
      array (
        'timeline' => true,
        'show_body' => true,
      ),
      'views' => 
      array (
        'timeline' => true,
        'data' => false,
        'group' => 50,
        'inertia_pages' => 'js/Pages',
        'exclude_paths' => 
        array (
          0 => 'vendor/filament',
        ),
      ),
      'route' => 
      array (
        'label' => true,
      ),
      'session' => 
      array (
        'hiddens' => 
        array (
        ),
      ),
      'symfony_request' => 
      array (
        'label' => true,
        'hiddens' => 
        array (
        ),
      ),
      'events' => 
      array (
        'data' => false,
        'excluded' => 
        array (
        ),
      ),
      'logs' => 
      array (
        'file' => NULL,
      ),
      'cache' => 
      array (
        'values' => true,
      ),
    ),
    'custom_collectors' => 
    array (
    ),
    'editor' => 'phpstorm',
    'capture_ajax' => true,
    'add_ajax_timing' => false,
    'ajax_handler_auto_show' => true,
    'ajax_handler_enable_tab' => true,
    'defer_datasets' => false,
    'remote_sites_path' => NULL,
    'local_sites_path' => NULL,
    'storage' => 
    array (
      'enabled' => true,
      'open' => NULL,
      'driver' => 'file',
      'path' => '/Users/andrejprus/Herd/tenanto/storage/debugbar',
      'connection' => NULL,
      'provider' => '',
      'hostname' => '127.0.0.1',
      'port' => 2304,
      'username' => '',
      'password' => '',
      'database' => NULL,
      'token' => NULL,
    ),
    'use_dist_files' => true,
    'include_vendors' => true,
    'error_handler' => true,
    'error_level' => 30719,
    'clockwork' => false,
    'inject' => true,
    'route_prefix' => '_debugbar',
    'route_middleware' => 
    array (
    ),
    'route_domain' => NULL,
    'theme' => 'auto',
    'debug_backtrace_limit' => 50,
    'hide_empty_tabs' => true,
  ),
  'dompdf' => 
  array (
    'show_warnings' => false,
    'public_path' => NULL,
    'convert_entities' => true,
    'options' => 
    array (
      'font_dir' => '/Users/andrejprus/Herd/tenanto/storage/fonts',
      'font_cache' => '/Users/andrejprus/Herd/tenanto/storage/fonts',
      'temp_dir' => '/var/folders/x3/2d974lw51cd8v769p271xdtr0000gn/T',
      'chroot' => '/Users/andrejprus/Herd/tenanto',
      'allowed_protocols' => 
      array (
        'data://' => 
        array (
          'rules' => 
          array (
          ),
        ),
        'file://' => 
        array (
          'rules' => 
          array (
          ),
        ),
        'http://' => 
        array (
          'rules' => 
          array (
          ),
        ),
        'https://' => 
        array (
          'rules' => 
          array (
          ),
        ),
      ),
      'artifactPathValidation' => NULL,
      'log_output_file' => NULL,
      'enable_font_subsetting' => false,
      'pdf_backend' => 'CPDF',
      'default_media_type' => 'screen',
      'default_paper_size' => 'a4',
      'default_paper_orientation' => 'portrait',
      'default_font' => 'serif',
      'dpi' => 96,
      'enable_php' => false,
      'enable_javascript' => true,
      'enable_remote' => false,
      'allowed_remote_hosts' => NULL,
      'font_height_ratio' => 1.1,
      'enable_html5_parser' => true,
    ),
  ),
  'excel' => 
  array (
    'exports' => 
    array (
      'chunk_size' => 1000,
      'pre_calculate_formulas' => false,
      'strict_null_comparison' => false,
      'csv' => 
      array (
        'delimiter' => ',',
        'enclosure' => '"',
        'line_ending' => '
',
        'use_bom' => false,
        'include_separator_line' => false,
        'excel_compatibility' => false,
        'output_encoding' => '',
        'test_auto_detect' => true,
      ),
      'properties' => 
      array (
        'creator' => '',
        'lastModifiedBy' => '',
        'title' => '',
        'description' => '',
        'subject' => '',
        'keywords' => '',
        'category' => '',
        'manager' => '',
        'company' => '',
      ),
    ),
    'imports' => 
    array (
      'read_only' => true,
      'ignore_empty' => false,
      'heading_row' => 
      array (
        'formatter' => 'slug',
      ),
      'csv' => 
      array (
        'delimiter' => NULL,
        'enclosure' => '"',
        'escape_character' => '\\',
        'contiguous' => false,
        'input_encoding' => 'guess',
      ),
      'properties' => 
      array (
        'creator' => '',
        'lastModifiedBy' => '',
        'title' => '',
        'description' => '',
        'subject' => '',
        'keywords' => '',
        'category' => '',
        'manager' => '',
        'company' => '',
      ),
      'cells' => 
      array (
        'middleware' => 
        array (
        ),
      ),
    ),
    'extension_detector' => 
    array (
      'xlsx' => 'Xlsx',
      'xlsm' => 'Xlsx',
      'xltx' => 'Xlsx',
      'xltm' => 'Xlsx',
      'xls' => 'Xls',
      'xlt' => 'Xls',
      'ods' => 'Ods',
      'ots' => 'Ods',
      'slk' => 'Slk',
      'xml' => 'Xml',
      'gnumeric' => 'Gnumeric',
      'htm' => 'Html',
      'html' => 'Html',
      'csv' => 'Csv',
      'tsv' => 'Csv',
      'pdf' => 'Dompdf',
    ),
    'value_binder' => 
    array (
      'default' => 'Maatwebsite\\Excel\\DefaultValueBinder',
    ),
    'cache' => 
    array (
      'driver' => 'memory',
      'batch' => 
      array (
        'memory_limit' => 60000,
      ),
      'illuminate' => 
      array (
        'store' => NULL,
      ),
      'default_ttl' => 10800,
    ),
    'transactions' => 
    array (
      'handler' => 'db',
      'db' => 
      array (
        'connection' => NULL,
      ),
    ),
    'temporary_files' => 
    array (
      'local_path' => '/Users/andrejprus/Herd/tenanto/storage/framework/cache/laravel-excel',
      'local_permissions' => 
      array (
      ),
      'remote_disk' => NULL,
      'remote_prefix' => NULL,
      'force_resync_remote' => NULL,
    ),
  ),
  'faq' => 
  array (
    'rate_limiting' => 
    array (
      'create' => 
      array (
        'max_attempts' => 5,
        'decay_minutes' => 1,
      ),
      'update' => 
      array (
        'max_attempts' => 10,
        'decay_minutes' => 1,
      ),
      'delete' => 
      array (
        'max_attempts' => 10,
        'decay_minutes' => 1,
      ),
      'bulk' => 
      array (
        'max_attempts' => 20,
        'decay_minutes' => 60,
      ),
    ),
    'validation' => 
    array (
      'question_max_length' => 255,
      'question_min_length' => 10,
      'answer_max_length' => 10000,
      'answer_min_length' => 10,
      'category_max_length' => 120,
      'display_order_max' => 9999,
      'allowed_html_tags' => '<p><br><strong><em><u><ul><ol><li><a>',
    ),
    'cache' => 
    array (
      'category_ttl' => 15,
      'key_prefix' => 'faq:',
      'max_categories' => 100,
    ),
    'security' => 
    array (
      'sanitize_html' => true,
      'audit_trail' => true,
      'confirm_bulk_delete' => true,
      'bulk_operation_limit' => 50,
    ),
  ),
  'filament' => 
  array (
    'broadcasting' => 
    array (
    ),
    'default_filesystem_disk' => 'local',
    'assets_path' => NULL,
    'cache_path' => '/Users/andrejprus/Herd/tenanto/bootstrap/cache/filament',
    'livewire_loading_delay' => 'default',
    'file_generation' => 
    array (
      'flags' => 
      array (
      ),
    ),
    'system_route_prefix' => 'filament',
  ),
  'filament-shield' => 
  array (
    'shield_resource' => 
    array (
      'slug' => 'shield/roles',
      'show_model_path' => true,
      'cluster' => NULL,
      'tabs' => 
      array (
        'pages' => true,
        'widgets' => true,
        'resources' => true,
        'custom_permissions' => false,
      ),
    ),
    'tenant_model' => NULL,
    'auth_provider_model' => 'App\\Models\\User',
    'super_admin' => 
    array (
      'enabled' => true,
      'name' => 'super_admin',
      'define_via_gate' => false,
      'intercept_gate' => 'before',
    ),
    'panel_user' => 
    array (
      'enabled' => true,
      'name' => 'panel_user',
    ),
    'permissions' => 
    array (
      'separator' => ':',
      'case' => 'pascal',
      'generate' => true,
    ),
    'policies' => 
    array (
      'path' => '/Users/andrejprus/Herd/tenanto/app/Policies',
      'merge' => true,
      'generate' => true,
      'methods' => 
      array (
        0 => 'viewAny',
        1 => 'view',
        2 => 'create',
        3 => 'update',
        4 => 'delete',
        5 => 'restore',
        6 => 'forceDelete',
        7 => 'forceDeleteAny',
        8 => 'restoreAny',
        9 => 'replicate',
        10 => 'reorder',
      ),
      'single_parameter_methods' => 
      array (
        0 => 'viewAny',
        1 => 'create',
        2 => 'deleteAny',
        3 => 'forceDeleteAny',
        4 => 'restoreAny',
        5 => 'reorder',
      ),
    ),
    'localization' => 
    array (
      'enabled' => false,
      'key' => 'filament-shield::filament-shield.resource_permission_prefixes_labels',
    ),
    'resources' => 
    array (
      'subject' => 'model',
      'manage' => 
      array (
        'BezhanSalleh\\FilamentShield\\Resources\\Roles\\RoleResource' => 
        array (
          0 => 'viewAny',
          1 => 'view',
          2 => 'create',
          3 => 'update',
          4 => 'delete',
        ),
      ),
      'exclude' => 
      array (
      ),
    ),
    'pages' => 
    array (
      'subject' => 'class',
      'prefix' => 'view',
      'exclude' => 
      array (
        0 => 'Filament\\Pages\\Dashboard',
      ),
    ),
    'widgets' => 
    array (
      'subject' => 'class',
      'prefix' => 'view',
      'exclude' => 
      array (
        0 => 'Filament\\Widgets\\AccountWidget',
        1 => 'Filament\\Widgets\\FilamentInfoWidget',
      ),
    ),
    'custom_permissions' => 
    array (
    ),
    'discovery' => 
    array (
      'discover_all_resources' => false,
      'discover_all_widgets' => false,
      'discover_all_pages' => false,
    ),
    'register_role_policy' => true,
  ),
  'filament-superadmin' => 
  array (
    'panel' => 
    array (
      'id' => 'superadmin',
      'path' => 'superadmin',
      'primary_color' => 'red',
      'auth_guard' => 'web',
    ),
    'features' => 
    array (
      'resource_discovery' => false,
      'page_discovery' => false,
      'widget_discovery' => false,
      'navigation' => false,
      'global_search' => false,
      'spa_mode' => false,
      'unsaved_changes_alerts' => false,
      'sidebar_collapsible' => false,
      'top_navigation' => false,
    ),
    'performance' => 
    array (
      'cache_ttl' => 300,
      'enable_caching' => true,
      'lazy_loading' => true,
    ),
    'widgets' => 
    array (
      'default' => 
      array (
        0 => 'Filament\\Widgets\\AccountWidget',
      ),
      'custom' => 
      array (
      ),
    ),
    'pages' => 
    array (
      'default' => 
      array (
        0 => 'App\\Filament\\Superadmin\\Pages\\Dashboard',
      ),
    ),
    'navigation_groups' => 
    array (
      0 => 
      array (
        'name' => 'System',
        'collapsed' => false,
      ),
      1 => 
      array (
        'name' => 'Users',
        'collapsed' => false,
      ),
      2 => 
      array (
        'name' => 'Monitoring',
        'collapsed' => true,
      ),
    ),
    'middleware' => 
    array (
      'core' => 
      array (
        0 => 'Illuminate\\Cookie\\Middleware\\EncryptCookies',
        1 => 'Illuminate\\Cookie\\Middleware\\AddQueuedCookiesToResponse',
        2 => 'Illuminate\\Session\\Middleware\\StartSession',
        3 => 'Illuminate\\Session\\Middleware\\AuthenticateSession',
        4 => 'Illuminate\\View\\Middleware\\ShareErrorsFromSession',
        5 => 'Illuminate\\Foundation\\Http\\Middleware\\VerifyCsrfToken',
        6 => 'Illuminate\\Routing\\Middleware\\SubstituteBindings',
      ),
      'filament' => 
      array (
        0 => 'Filament\\Http\\Middleware\\DisableBladeIconComponents',
        1 => 'Filament\\Http\\Middleware\\DispatchServingFilamentEvent',
      ),
      'auth' => 
      array (
        0 => 'Filament\\Http\\Middleware\\Authenticate',
        1 => 'App\\Http\\Middleware\\EnsureUserIsSuperadmin',
      ),
    ),
    'security' => 
    array (
      'require_superadmin_role' => true,
      'log_access_attempts' => true,
    ),
  ),
  'filesystems' => 
  array (
    'default' => 'local',
    'disks' => 
    array (
      'local' => 
      array (
        'driver' => 'local',
        'root' => '/Users/andrejprus/Herd/tenanto/storage/app',
        'throw' => false,
      ),
      'public' => 
      array (
        'driver' => 'local',
        'root' => '/Users/andrejprus/Herd/tenanto/storage/app/public',
        'url' => 'http://localhost/storage',
        'visibility' => 'public',
        'throw' => false,
      ),
      's3' => 
      array (
        'driver' => 's3',
        'key' => '',
        'secret' => '',
        'region' => 'us-east-1',
        'bucket' => '',
        'url' => NULL,
        'endpoint' => NULL,
        'use_path_style_endpoint' => false,
        'throw' => false,
      ),
    ),
    'links' => 
    array (
      '/Users/andrejprus/Herd/tenanto/public/storage' => '/Users/andrejprus/Herd/tenanto/storage/app/public',
    ),
  ),
  'flare' => 
  array (
    'key' => NULL,
    'flare_middleware' => 
    array (
      0 => 'Spatie\\FlareClient\\FlareMiddleware\\RemoveRequestIp',
      1 => 'Spatie\\FlareClient\\FlareMiddleware\\AddGitInformation',
      2 => 'Spatie\\LaravelIgnition\\FlareMiddleware\\AddNotifierName',
      3 => 'Spatie\\LaravelIgnition\\FlareMiddleware\\AddEnvironmentInformation',
      4 => 'Spatie\\LaravelIgnition\\FlareMiddleware\\AddExceptionInformation',
      5 => 'Spatie\\LaravelIgnition\\FlareMiddleware\\AddDumps',
      'Spatie\\LaravelIgnition\\FlareMiddleware\\AddLogs' => 
      array (
        'maximum_number_of_collected_logs' => 200,
      ),
      'Spatie\\LaravelIgnition\\FlareMiddleware\\AddQueries' => 
      array (
        'maximum_number_of_collected_queries' => 200,
        'report_query_bindings' => true,
      ),
      'Spatie\\LaravelIgnition\\FlareMiddleware\\AddJobs' => 
      array (
        'max_chained_job_reporting_depth' => 5,
      ),
      6 => 'Spatie\\LaravelIgnition\\FlareMiddleware\\AddContext',
      7 => 'Spatie\\LaravelIgnition\\FlareMiddleware\\AddExceptionHandledStatus',
      'Spatie\\FlareClient\\FlareMiddleware\\CensorRequestBodyFields' => 
      array (
        'censor_fields' => 
        array (
          0 => 'password',
          1 => 'password_confirmation',
        ),
      ),
      'Spatie\\FlareClient\\FlareMiddleware\\CensorRequestHeaders' => 
      array (
        'headers' => 
        array (
          0 => 'API-KEY',
          1 => 'Authorization',
          2 => 'Cookie',
          3 => 'Set-Cookie',
          4 => 'X-CSRF-TOKEN',
          5 => 'X-XSRF-TOKEN',
        ),
      ),
    ),
    'send_logs_as_events' => true,
  ),
  'generate-tests-easy' => 
  array (
    'paths' => 
    array (
      'feature' => 'Feature',
      'unit' => 'Unit',
      'performance' => 'Performance',
      'security' => 'Security',
    ),
    'namespaces' => 
    array (
      'feature' => 'Tests\\Feature',
      'unit' => 'Tests\\Unit',
      'performance' => 'Tests\\Performance',
      'security' => 'Tests\\Security',
    ),
    'templates' => 
    array (
      'controller' => '/Users/andrejprus/Herd/tenanto/tests/stubs/controller.test.stub',
      'model' => '/Users/andrejprus/Herd/tenanto/tests/stubs/model.test.stub',
      'service' => '/Users/andrejprus/Herd/tenanto/tests/stubs/service.test.stub',
      'filament' => '/Users/andrejprus/Herd/tenanto/tests/stubs/filament.test.stub',
      'policy' => '/Users/andrejprus/Herd/tenanto/tests/stubs/policy.test.stub',
      'middleware' => '/Users/andrejprus/Herd/tenanto/tests/stubs/middleware.test.stub',
      'observer' => '/Users/andrejprus/Herd/tenanto/tests/stubs/observer.test.stub',
      'value-object' => '/Users/andrejprus/Herd/tenanto/tests/stubs/value-object.test.stub',
    ),
    'multi_tenancy' => 
    array (
      'enabled' => true,
      'tenant_trait' => 'App\\Traits\\BelongsToTenant',
      'tenant_context' => 'App\\Services\\TenantContext',
      'tenant_scope' => 'App\\Scopes\\TenantScope',
      'hierarchical_scope' => 'App\\Scopes\\HierarchicalScope',
    ),
    'framework' => 
    array (
      'type' => 'pest',
      'version' => 3,
    ),
    'helpers' => 
    array (
      'use_refresh_database' => true,
      'use_factories' => true,
      'use_seeders' => false,
      'custom_traits' => 
      array (
      ),
    ),
    'authentication' => 
    array (
      'enabled' => true,
      'default_role' => 'admin',
      'helpers' => 
      array (
        'actingAsAdmin' => true,
        'actingAsManager' => true,
        'actingAsTenant' => true,
        'actingAsSuperadmin' => true,
      ),
    ),
    'assertions' => 
    array (
      'auto_generate' => true,
      'include_database' => true,
      'include_response' => true,
      'include_validation' => true,
      'include_authorization' => true,
    ),
    'mocking' => 
    array (
      'enabled' => true,
      'auto_detect_dependencies' => true,
      'mock_external_services' => true,
    ),
    'coverage' => 
    array (
      'generate_report' => true,
      'min_coverage' => 80,
      'exclude_paths' => 
      array (
        0 => 'vendor',
        1 => 'node_modules',
        2 => 'storage',
        3 => 'bootstrap/cache',
      ),
    ),
    'naming' => 
    array (
      'test_class_suffix' => 'Test',
      'test_method_prefix' => 'test_',
      'use_descriptive_names' => true,
      'use_snake_case' => true,
    ),
    'generation' => 
    array (
      'overwrite_existing' => false,
      'backup_existing' => true,
      'create_directories' => true,
      'format_code' => true,
      'add_comments' => true,
    ),
    'filament' => 
    array (
      'enabled' => true,
      'version' => 4,
      'test_resources' => true,
      'test_pages' => true,
      'test_widgets' => true,
      'test_actions' => true,
      'test_forms' => true,
      'test_tables' => true,
    ),
    'laravel' => 
    array (
      'test_routes' => true,
      'test_middleware' => true,
      'test_policies' => true,
      'test_observers' => true,
      'test_events' => true,
      'test_jobs' => true,
      'test_notifications' => true,
      'test_mail' => true,
    ),
    'custom_rules' => 
    array (
      'always_test_tenant_isolation' => true,
      'always_test_authorization' => true,
      'always_test_validation' => true,
      'include_property_tests' => true,
    ),
    'exclusions' => 
    array (
      'classes' => 
      array (
      ),
      'methods' => 
      array (
        0 => '__construct',
        1 => '__destruct',
        2 => '__get',
        3 => '__set',
        4 => '__call',
      ),
      'patterns' => 
      array (
      ),
    ),
    'documentation' => 
    array (
      'generate' => true,
      'format' => 'markdown',
      'output_path' => 'docs/testing',
      'include_examples' => true,
    ),
  ),
  'ignition' => 
  array (
    'editor' => 'phpstorm',
    'theme' => 'auto',
    'enable_share_button' => true,
    'register_commands' => false,
    'solution_providers' => 
    array (
      0 => 'Spatie\\Ignition\\Solutions\\SolutionProviders\\BadMethodCallSolutionProvider',
      1 => 'Spatie\\Ignition\\Solutions\\SolutionProviders\\MergeConflictSolutionProvider',
      2 => 'Spatie\\Ignition\\Solutions\\SolutionProviders\\UndefinedPropertySolutionProvider',
      3 => 'Spatie\\LaravelIgnition\\Solutions\\SolutionProviders\\IncorrectValetDbCredentialsSolutionProvider',
      4 => 'Spatie\\LaravelIgnition\\Solutions\\SolutionProviders\\MissingAppKeySolutionProvider',
      5 => 'Spatie\\LaravelIgnition\\Solutions\\SolutionProviders\\DefaultDbNameSolutionProvider',
      6 => 'Spatie\\LaravelIgnition\\Solutions\\SolutionProviders\\TableNotFoundSolutionProvider',
      7 => 'Spatie\\LaravelIgnition\\Solutions\\SolutionProviders\\MissingImportSolutionProvider',
      8 => 'Spatie\\LaravelIgnition\\Solutions\\SolutionProviders\\InvalidRouteActionSolutionProvider',
      9 => 'Spatie\\LaravelIgnition\\Solutions\\SolutionProviders\\ViewNotFoundSolutionProvider',
      10 => 'Spatie\\LaravelIgnition\\Solutions\\SolutionProviders\\RunningLaravelDuskInProductionProvider',
      11 => 'Spatie\\LaravelIgnition\\Solutions\\SolutionProviders\\MissingColumnSolutionProvider',
      12 => 'Spatie\\LaravelIgnition\\Solutions\\SolutionProviders\\UnknownValidationSolutionProvider',
      13 => 'Spatie\\LaravelIgnition\\Solutions\\SolutionProviders\\MissingMixManifestSolutionProvider',
      14 => 'Spatie\\LaravelIgnition\\Solutions\\SolutionProviders\\MissingViteManifestSolutionProvider',
      15 => 'Spatie\\LaravelIgnition\\Solutions\\SolutionProviders\\MissingLivewireComponentSolutionProvider',
      16 => 'Spatie\\LaravelIgnition\\Solutions\\SolutionProviders\\UndefinedViewVariableSolutionProvider',
      17 => 'Spatie\\LaravelIgnition\\Solutions\\SolutionProviders\\GenericLaravelExceptionSolutionProvider',
      18 => 'Spatie\\LaravelIgnition\\Solutions\\SolutionProviders\\OpenAiSolutionProvider',
      19 => 'Spatie\\LaravelIgnition\\Solutions\\SolutionProviders\\SailNetworkSolutionProvider',
      20 => 'Spatie\\LaravelIgnition\\Solutions\\SolutionProviders\\UnknownMysql8CollationSolutionProvider',
      21 => 'Spatie\\LaravelIgnition\\Solutions\\SolutionProviders\\UnknownMariadbCollationSolutionProvider',
    ),
    'ignored_solution_providers' => 
    array (
    ),
    'enable_runnable_solutions' => NULL,
    'remote_sites_path' => '/Users/andrejprus/Herd/tenanto',
    'local_sites_path' => '',
    'housekeeping_endpoint_prefix' => '_ignition',
    'settings_file_path' => '',
    'recorders' => 
    array (
      0 => 'Spatie\\LaravelIgnition\\Recorders\\DumpRecorder\\DumpRecorder',
      1 => 'Spatie\\LaravelIgnition\\Recorders\\JobRecorder\\JobRecorder',
      2 => 'Spatie\\LaravelIgnition\\Recorders\\LogRecorder\\LogRecorder',
      3 => 'Spatie\\LaravelIgnition\\Recorders\\QueryRecorder\\QueryRecorder',
    ),
    'open_ai_key' => NULL,
    'with_stack_frame_arguments' => true,
    'argument_reducers' => 
    array (
      0 => 'Spatie\\Backtrace\\Arguments\\Reducers\\BaseTypeArgumentReducer',
      1 => 'Spatie\\Backtrace\\Arguments\\Reducers\\ArrayArgumentReducer',
      2 => 'Spatie\\Backtrace\\Arguments\\Reducers\\StdClassArgumentReducer',
      3 => 'Spatie\\Backtrace\\Arguments\\Reducers\\EnumArgumentReducer',
      4 => 'Spatie\\Backtrace\\Arguments\\Reducers\\ClosureArgumentReducer',
      5 => 'Spatie\\Backtrace\\Arguments\\Reducers\\DateTimeArgumentReducer',
      6 => 'Spatie\\Backtrace\\Arguments\\Reducers\\DateTimeZoneArgumentReducer',
      7 => 'Spatie\\Backtrace\\Arguments\\Reducers\\SymphonyRequestArgumentReducer',
      8 => 'Spatie\\LaravelIgnition\\ArgumentReducers\\ModelArgumentReducer',
      9 => 'Spatie\\LaravelIgnition\\ArgumentReducers\\CollectionArgumentReducer',
      10 => 'Spatie\\Backtrace\\Arguments\\Reducers\\StringableArgumentReducer',
    ),
  ),
  'landing' => 
  array (
    'brand' => 
    array (
      'name' => 'Vilnius Utilities',
      'product' => 'Rent Counter',
      'tagline' => 'One cockpit for usage, billing, and compliance across your properties.',
    ),
    'features' => 
    array (
      0 => 
      array (
        'title' => 'landing.features.unified_metering.title',
        'description' => 'landing.features.unified_metering.description',
        'icon' => 'meter',
      ),
      1 => 
      array (
        'title' => 'landing.features.accurate_invoicing.title',
        'description' => 'landing.features.accurate_invoicing.description',
        'icon' => 'invoice',
      ),
      2 => 
      array (
        'title' => 'landing.features.role_access.title',
        'description' => 'landing.features.role_access.description',
        'icon' => 'shield',
      ),
      3 => 
      array (
        'title' => 'landing.features.reporting.title',
        'description' => 'landing.features.reporting.description',
        'icon' => 'chart',
      ),
      4 => 
      array (
        'title' => 'landing.features.performance.title',
        'description' => 'landing.features.performance.description',
        'icon' => 'rocket',
      ),
      5 => 
      array (
        'title' => 'landing.features.tenant_clarity.title',
        'description' => 'landing.features.tenant_clarity.description',
        'icon' => 'users',
      ),
    ),
    'faq' => 
    array (
      0 => 
      array (
        'question' => 'landing.faq.validation.question',
        'answer' => 'landing.faq.validation.answer',
      ),
      1 => 
      array (
        'question' => 'landing.faq.tenants.question',
        'answer' => 'landing.faq.tenants.answer',
      ),
      2 => 
      array (
        'question' => 'landing.faq.invoices.question',
        'answer' => 'landing.faq.invoices.answer',
      ),
      3 => 
      array (
        'question' => 'landing.faq.security.question',
        'answer' => 'landing.faq.security.answer',
      ),
      4 => 
      array (
        'question' => 'landing.faq.support.question',
        'answer' => 'landing.faq.support.answer',
      ),
    ),
  ),
  'livewire' => 
  array (
    'component_locations' => 
    array (
      0 => '/Users/andrejprus/Herd/tenanto/resources/views/components',
      1 => '/Users/andrejprus/Herd/tenanto/resources/views/livewire',
    ),
    'component_namespaces' => 
    array (
      'layouts' => '/Users/andrejprus/Herd/tenanto/resources/views/layouts',
      'pages' => '/Users/andrejprus/Herd/tenanto/resources/views/pages',
    ),
    'component_layout' => 'layouts::app',
    'component_placeholder' => NULL,
    'make_command' => 
    array (
      'type' => 'sfc',
      'emoji' => true,
      'with' => 
      array (
        'js' => false,
        'css' => false,
        'test' => false,
      ),
    ),
    'class_namespace' => 'App\\Livewire',
    'class_path' => '/Users/andrejprus/Herd/tenanto/app/Livewire',
    'view_path' => '/Users/andrejprus/Herd/tenanto/resources/views/livewire',
    'temporary_file_upload' => 
    array (
      'disk' => NULL,
      'rules' => NULL,
      'directory' => NULL,
      'middleware' => NULL,
      'preview_mimes' => 
      array (
        0 => 'png',
        1 => 'gif',
        2 => 'bmp',
        3 => 'svg',
        4 => 'wav',
        5 => 'mp4',
        6 => 'mov',
        7 => 'avi',
        8 => 'wmv',
        9 => 'mp3',
        10 => 'm4a',
        11 => 'jpg',
        12 => 'jpeg',
        13 => 'mpga',
        14 => 'webp',
        15 => 'wma',
      ),
      'max_upload_time' => 5,
      'cleanup' => true,
    ),
    'render_on_redirect' => false,
    'legacy_model_binding' => false,
    'inject_assets' => true,
    'navigate' => 
    array (
      'show_progress_bar' => true,
      'progress_bar_color' => '#2299dd',
    ),
    'inject_morph_markers' => true,
    'smart_wire_keys' => false,
    'pagination_theme' => 'tailwind',
    'release_token' => 'a',
    'csp_safe' => false,
    'payload' => 
    array (
      'max_size' => 1048576,
      'max_nesting_depth' => 10,
      'max_calls' => 50,
      'max_components' => 20,
    ),
    'layout' => 'components.layouts.app',
    'lazy_placeholder' => NULL,
  ),
  'locales' => 
  array (
    'default' => 'lt',
    'fallback' => 'en',
    'available' => 
    array (
      'lt' => 
      array (
        'label' => 'common.lithuanian',
        'abbreviation' => 'LT',
        'native_name' => 'Lietuvių',
      ),
      'en' => 
      array (
        'label' => 'common.english',
        'abbreviation' => 'EN',
        'native_name' => 'English',
      ),
      'ru' => 
      array (
        'label' => 'common.russian',
        'abbreviation' => 'RU',
        'native_name' => 'Русский',
      ),
    ),
  ),
  'logging' => 
  array (
    'default' => 'stack',
    'deprecations' => 
    array (
      'channel' => NULL,
      'trace' => false,
    ),
    'channels' => 
    array (
      'stack' => 
      array (
        'driver' => 'stack',
        'channels' => 
        array (
          0 => 'single',
        ),
        'ignore_exceptions' => false,
      ),
      'single' => 
      array (
        'driver' => 'single',
        'path' => '/Users/andrejprus/Herd/tenanto/storage/logs/laravel.log',
        'level' => 'debug',
        'replace_placeholders' => true,
        'tap' => 
        array (
          0 => 'App\\Logging\\Tap\\RedactSensitiveDataTap',
        ),
      ),
      'daily' => 
      array (
        'driver' => 'daily',
        'path' => '/Users/andrejprus/Herd/tenanto/storage/logs/laravel.log',
        'level' => 'debug',
        'days' => 14,
        'replace_placeholders' => true,
        'tap' => 
        array (
          0 => 'App\\Logging\\Tap\\RedactSensitiveDataTap',
        ),
      ),
      'slack' => 
      array (
        'driver' => 'slack',
        'url' => NULL,
        'username' => 'Laravel Log',
        'emoji' => ':boom:',
        'level' => 'debug',
        'replace_placeholders' => true,
      ),
      'papertrail' => 
      array (
        'driver' => 'monolog',
        'level' => 'debug',
        'handler' => 'Monolog\\Handler\\SyslogUdpHandler',
        'handler_with' => 
        array (
          'host' => NULL,
          'port' => NULL,
          'connectionString' => 'tls://:',
        ),
        'processors' => 
        array (
          0 => 'Monolog\\Processor\\PsrLogMessageProcessor',
        ),
      ),
      'stderr' => 
      array (
        'driver' => 'monolog',
        'level' => 'debug',
        'handler' => 'Monolog\\Handler\\StreamHandler',
        'formatter' => NULL,
        'with' => 
        array (
          'stream' => 'php://stderr',
        ),
        'processors' => 
        array (
          0 => 'Monolog\\Processor\\PsrLogMessageProcessor',
        ),
      ),
      'syslog' => 
      array (
        'driver' => 'syslog',
        'level' => 'debug',
        'facility' => 8,
        'replace_placeholders' => true,
      ),
      'errorlog' => 
      array (
        'driver' => 'errorlog',
        'level' => 'debug',
        'replace_placeholders' => true,
      ),
      'null' => 
      array (
        'driver' => 'monolog',
        'handler' => 'Monolog\\Handler\\NullHandler',
      ),
      'emergency' => 
      array (
        'path' => '/Users/andrejprus/Herd/tenanto/storage/logs/laravel.log',
      ),
      'audit' => 
      array (
        'driver' => 'daily',
        'path' => '/Users/andrejprus/Herd/tenanto/storage/logs/audit.log',
        'level' => 'info',
        'days' => 90,
        'replace_placeholders' => true,
        'permission' => 416,
        'tap' => 
        array (
          0 => 'App\\Logging\\Tap\\RedactSensitiveDataTap',
        ),
      ),
      'security' => 
      array (
        'driver' => 'daily',
        'path' => '/Users/andrejprus/Herd/tenanto/storage/logs/security.log',
        'level' => 'warning',
        'days' => 90,
        'permission' => 416,
        'replace_placeholders' => true,
        'tap' => 
        array (
          0 => 'App\\Logging\\Tap\\RedactSensitiveDataTap',
        ),
      ),
      'services' => 
      array (
        'driver' => 'daily',
        'path' => '/Users/andrejprus/Herd/tenanto/storage/logs/services.log',
        'level' => 'info',
        'days' => 14,
        'permission' => 416,
        'replace_placeholders' => true,
        'tap' => 
        array (
          0 => 'App\\Logging\\Tap\\RedactSensitiveDataTap',
        ),
      ),
      'tenant_context' => 
      array (
        'driver' => 'daily',
        'path' => '/Users/andrejprus/Herd/tenanto/storage/logs/tenant-context.log',
        'level' => 'info',
        'days' => 90,
        'permission' => 416,
        'replace_placeholders' => true,
        'tap' => 
        array (
          0 => 'App\\Logging\\Tap\\RedactSensitiveDataTap',
        ),
      ),
      'deprecations' => 
      array (
        'driver' => 'monolog',
        'handler' => 'Monolog\\Handler\\NullHandler',
      ),
      'browser' => 
      array (
        'driver' => 'single',
        'path' => '/Users/andrejprus/Herd/tenanto/storage/logs/browser.log',
        'level' => 'debug',
        'days' => 14,
      ),
    ),
  ),
  'mail' => 
  array (
    'default' => 'log',
    'mailers' => 
    array (
      'smtp' => 
      array (
        'transport' => 'smtp',
        'url' => NULL,
        'host' => '127.0.0.1',
        'port' => '2525',
        'encryption' => NULL,
        'username' => NULL,
        'password' => NULL,
        'timeout' => NULL,
        'local_domain' => NULL,
      ),
      'ses' => 
      array (
        'transport' => 'ses',
      ),
      'postmark' => 
      array (
        'transport' => 'postmark',
      ),
      'resend' => 
      array (
        'transport' => 'resend',
      ),
      'sendmail' => 
      array (
        'transport' => 'sendmail',
        'path' => '/usr/sbin/sendmail -bs -i',
      ),
      'log' => 
      array (
        'transport' => 'log',
        'channel' => NULL,
      ),
      'array' => 
      array (
        'transport' => 'array',
      ),
      'failover' => 
      array (
        'transport' => 'failover',
        'mailers' => 
        array (
          0 => 'smtp',
          1 => 'log',
        ),
      ),
      'roundrobin' => 
      array (
        'transport' => 'roundrobin',
        'mailers' => 
        array (
          0 => 'ses',
          1 => 'postmark',
        ),
        'retry_after' => 60,
      ),
    ),
    'from' => 
    array (
      'address' => 'hello@example.com',
      'name' => 'Laravel',
    ),
    'markdown' => 
    array (
      'theme' => 'default',
      'paths' => 
      array (
        0 => '/Users/andrejprus/Herd/tenanto/resources/views/vendor/mail',
      ),
      'extensions' => 
      array (
      ),
    ),
  ),
  'permission' => 
  array (
    'models' => 
    array (
      'permission' => 'Spatie\\Permission\\Models\\Permission',
      'role' => 'Spatie\\Permission\\Models\\Role',
    ),
    'table_names' => 
    array (
      'roles' => 'roles',
      'permissions' => 'permissions',
      'model_has_permissions' => 'model_has_permissions',
      'model_has_roles' => 'model_has_roles',
      'role_has_permissions' => 'role_has_permissions',
    ),
    'column_names' => 
    array (
      'role_pivot_key' => NULL,
      'permission_pivot_key' => NULL,
      'model_morph_key' => 'model_id',
      'team_foreign_key' => 'team_id',
    ),
    'register_permission_check_method' => true,
    'register_octane_reset_listener' => false,
    'events_enabled' => false,
    'teams' => false,
    'team_resolver' => 'Spatie\\Permission\\DefaultTeamResolver',
    'use_passport_client_credentials' => false,
    'display_permission_in_exception' => false,
    'display_role_in_exception' => false,
    'enable_wildcard_permission' => false,
    'cache' => 
    array (
      'expiration_time' => 
      \DateInterval::__set_state(array(
         'from_string' => true,
         'date_string' => '24 hours',
      )),
      'key' => 'spatie.permission.cache',
      'store' => 'default',
    ),
  ),
  'queue' => 
  array (
    'default' => 'database',
    'connections' => 
    array (
      'sync' => 
      array (
        'driver' => 'sync',
      ),
      'database' => 
      array (
        'driver' => 'database',
        'connection' => NULL,
        'table' => 'jobs',
        'queue' => 'default',
        'retry_after' => 90,
        'after_commit' => false,
      ),
      'beanstalkd' => 
      array (
        'driver' => 'beanstalkd',
        'host' => 'localhost',
        'queue' => 'default',
        'retry_after' => 90,
        'block_for' => 0,
        'after_commit' => false,
      ),
      'sqs' => 
      array (
        'driver' => 'sqs',
        'key' => '',
        'secret' => '',
        'prefix' => 'https://sqs.us-east-1.amazonaws.com/your-account-id',
        'queue' => 'default',
        'suffix' => NULL,
        'region' => 'us-east-1',
        'after_commit' => false,
      ),
      'redis' => 
      array (
        'driver' => 'redis',
        'connection' => 'default',
        'queue' => 'default',
        'retry_after' => 90,
        'block_for' => NULL,
        'after_commit' => false,
      ),
      'deferred' => 
      array (
        'driver' => 'deferred',
      ),
      'failover' => 
      array (
        'driver' => 'failover',
        'connections' => 
        array (
          0 => 'database',
          1 => 'deferred',
        ),
      ),
    ),
    'batching' => 
    array (
      'database' => 'sqlite',
      'table' => 'job_batches',
    ),
    'failed' => 
    array (
      'driver' => 'database-uuids',
      'database' => 'sqlite',
      'table' => 'failed_jobs',
    ),
  ),
  'sanctum' => 
  array (
    'stateful' => 
    array (
      0 => 'localhost',
      1 => 'localhost:3000',
      2 => '127.0.0.1',
      3 => '127.0.0.1:8000',
      4 => '::1',
      5 => 'localhost',
    ),
    'guard' => 
    array (
      0 => 'web',
    ),
    'expiration' => 525600,
    'token_prefix' => '',
    'middleware' => 
    array (
      'authenticate_session' => 'Laravel\\Sanctum\\Http\\Middleware\\AuthenticateSession',
      'encrypt_cookies' => 'App\\Http\\Middleware\\EncryptCookies',
      'validate_csrf_token' => 'App\\Http\\Middleware\\VerifyCsrfToken',
    ),
  ),
  'security' => 
  array (
    'policy_registration' => 
    array (
      'allowed_environments' => 
      array (
        0 => 'local',
        1 => 'testing',
      ),
      'require_super_admin' => true,
      'rate_limit' => 
      array (
        'max_attempts' => 5,
        'decay_minutes' => 5,
      ),
      'cache_ttl' => 3600,
    ),
    'logging' => 
    array (
      'redact_sensitive_data' => true,
      'hash_pii' => true,
      'security_channel' => 'security',
      'alert_on_violations' => true,
    ),
    'headers' => 
    array (
      'csp' => 
      array (
        'enabled' => true,
        'report_only' => false,
        'report_uri' => '/csp-report',
      ),
      'hsts' => 
      array (
        'enabled' => true,
        'max_age' => 31536000,
        'include_subdomains' => true,
      ),
      'x_frame_options' => 'DENY',
      'x_content_type_options' => 'nosniff',
      'referrer_policy' => 'strict-origin-when-cross-origin',
    ),
    'rate_limiting' => 
    array (
      'translation_requests' => 
      array (
        'max_attempts' => 100,
        'decay_minutes' => 1,
      ),
      'policy_registration' => 
      array (
        'max_attempts' => 5,
        'decay_minutes' => 5,
      ),
      'api_requests' => 
      array (
        'max_attempts' => 60,
        'decay_minutes' => 1,
      ),
    ),
    'validation' => 
    array (
      'locale_pattern' => '/^[a-z]{2}(_[A-Z]{2})?$/',
      'translation_key_pattern' => '/^[a-zA-Z0-9._-]+$/',
      'max_translation_key_length' => 255,
      'suspicious_patterns' => 
      array (
        0 => '/\\.\\.\\//i',
        1 => '/<script/i',
        2 => '/javascript:/i',
        3 => '/on\\w+\\s*=/i',
        4 => '/union\\s+select/i',
        5 => '/drop\\s+table/i',
        6 => '/exec\\s*\\(/i',
        7 => '/eval\\s*\\(/i',
        8 => '/system\\s*\\(/i',
      ),
    ),
    'monitoring' => 
    array (
      'enabled' => true,
      'alert_channels' => 
      array (
        0 => 'slack',
        1 => 'email',
      ),
      'thresholds' => 
      array (
        'failed_logins' => 10,
        'policy_registration_failures' => 5,
        'suspicious_requests' => 20,
      ),
    ),
  ),
  'service-registration' => 
  array (
    'error_handling' => 
    array (
      'fail_fast_environments' => 
      array (
        0 => 'local',
        1 => 'testing',
      ),
      'detailed_logging_environments' => 
      array (
        0 => 'local',
        1 => 'testing',
      ),
      'production_alert_environments' => 
      array (
        0 => 'production',
        1 => 'staging',
      ),
    ),
    'monitoring' => 
    array (
      'enabled' => true,
      'record_metrics' => true,
      'cache_ttl' => 3600,
    ),
    'logging' => 
    array (
      'contexts' => 
      array (
        'app_boot' => 'application_boot',
        'policy_registration' => 'policy_registration',
        'gate_registration' => 'gate_registration',
        'service_registration' => 'service_registration',
      ),
      'log_levels' => 
      array (
        'success' => 'info',
        'warning' => 'warning',
        'error' => 'error',
        'critical' => 'critical',
      ),
    ),
    'core_services' => 
    array (
      'singletons' => 
      array (
        0 => 'App\\Services\\TenantContext',
        1 => 'App\\Services\\TenantBoundaryService',
        2 => 'App\\Repositories\\Eloquent\\EloquentTenantRepository',
        3 => 'App\\Services\\TenantAuditLogger',
        4 => 'App\\Services\\TenantAuthorizationService',
      ),
      'bindings' => 
      array (
        'App\\Contracts\\ServiceRegistration\\PolicyRegistryInterface' => 'App\\Support\\ServiceRegistration\\PolicyRegistry',
        'App\\Contracts\\ServiceRegistration\\ErrorHandlingStrategyInterface' => 'App\\Services\\ServiceRegistration\\RegistrationErrorHandler',
        'App\\Contracts\\CircuitBreakerInterface' => 'App\\Services\\Integration\\CircuitBreakerService',
        'App\\Contracts\\SubscriptionCheckerInterface' => 'App\\Services\\SubscriptionChecker',
        'App\\Repositories\\TenantRepositoryInterface' => 'App\\Repositories\\Eloquent\\EloquentTenantRepository',
        'App\\Contracts\\TenantAuditLoggerInterface' => 'App\\Services\\TenantAuditLogger',
        'App\\Contracts\\TenantAuthorizationServiceInterface' => 'App\\Services\\TenantAuthorizationService',
        'App\\Contracts\\TenantContextInterface' => 'App\\Services\\TenantContext',
      ),
    ),
  ),
  'service_validation' => 
  array (
    'default_min_consumption' => 0,
    'default_max_consumption' => 10000,
    'rate_change_frequency_days' => 30,
    'seasonal_adjustments' => 
    array (
      'heating' => 
      array (
        'summer_max_threshold' => 50,
        'winter_min_threshold' => 100,
      ),
      'water' => 
      array (
        'summer_range' => 
        array (
          'min' => 80,
          'max' => 150,
        ),
        'winter_range' => 
        array (
          'min' => 60,
          'max' => 120,
        ),
      ),
      'electricity' => 
      array (
        'summer_range' => 
        array (
          'min' => 200,
          'max' => 800,
        ),
        'winter_range' => 
        array (
          'min' => 150,
          'max' => 600,
        ),
      ),
      'default' => 
      array (
        'variance_threshold' => 0.3,
      ),
    ),
    'performance' => 
    array (
      'batch_validation_size' => 100,
      'cache_ttl_seconds' => 3600,
      'historical_months' => 12,
      'chunk_size' => 50,
    ),
    'security' => 
    array (
      'max_array_depth' => 3,
      'max_array_size' => 1000,
      'max_string_length' => 255,
      'max_rate_value' => 999999.99,
      'min_rate_value' => 0,
      'max_date_future_years' => 10,
      'max_date_past_years' => 50,
      'max_time_slots' => 50,
      'max_tiers' => 50,
    ),
    'true_up_threshold' => 5.0,
    'validation_rules' => 
    array (
      'electricity' => 
      array (
        'min_consumption' => 0,
        'max_consumption' => 50000,
        'variance_threshold' => 0.5,
      ),
      'water_cold' => 
      array (
        'min_consumption' => 0,
        'max_consumption' => 1000,
        'variance_threshold' => 0.4,
      ),
      'water_hot' => 
      array (
        'min_consumption' => 0,
        'max_consumption' => 500,
        'variance_threshold' => 0.4,
      ),
      'heating' => 
      array (
        'min_consumption' => 0,
        'max_consumption' => 100000,
        'variance_threshold' => 0.6,
      ),
      'gas' => 
      array (
        'min_consumption' => 0,
        'max_consumption' => 10000,
        'variance_threshold' => 0.5,
      ),
    ),
    'error_messages' => 
    array (
      'unauthorized_access' => 'You do not have permission to access this resource.',
      'rate_schedule_invalid' => 'The provided rate schedule contains invalid data.',
      'consumption_out_of_range' => 'Consumption value is outside acceptable range.',
      'date_out_of_range' => 'Date is outside acceptable range.',
      'structure_too_complex' => 'Data structure is too complex.',
      'array_too_large' => 'Data array is too large.',
      'system_error' => 'A system error occurred during validation.',
    ),
  ),
  'services' => 
  array (
    'postmark' => 
    array (
      'token' => NULL,
    ),
    'resend' => 
    array (
      'key' => NULL,
    ),
    'ses' => 
    array (
      'key' => '',
      'secret' => '',
      'region' => 'us-east-1',
    ),
    'slack' => 
    array (
      'notifications' => 
      array (
        'bot_user_oauth_token' => NULL,
        'channel' => NULL,
      ),
    ),
    'gpt_researcher_mcp' => 
    array (
      'repository' => 'https://github.com/assafelovic/gptr-mcp.git',
      'branch' => 'master',
      'path' => '/Users/andrejprus/Herd/tenanto/storage/app/mcp/gptr-mcp',
      'python' => 'python3',
      'transport' => 'stdio',
      'openai_api_key' => NULL,
      'tavily_api_key' => NULL,
    ),
  ),
  'session' => 
  array (
    'driver' => 'database',
    'lifetime' => '120',
    'expire_on_close' => true,
    'encrypt' => false,
    'files' => '/Users/andrejprus/Herd/tenanto/storage/framework/sessions',
    'connection' => NULL,
    'table' => 'sessions',
    'store' => NULL,
    'lottery' => 
    array (
      0 => 2,
      1 => 100,
    ),
    'cookie' => 'laravel_session',
    'path' => '/',
    'domain' => NULL,
    'secure' => true,
    'http_only' => true,
    'same_site' => 'strict',
    'partitioned' => false,
  ),
  'subscription' => 
  array (
    'max_properties_basic' => 10,
    'max_properties_professional' => 50,
    'max_properties_enterprise' => 999999,
    'max_tenants_basic' => 50,
    'max_tenants_professional' => 200,
    'max_tenants_enterprise' => 999999,
    'grace_period_days' => 7,
    'expiry_warning_days' => 14,
    'cache_ttl' => 300,
    'rate_limit' => 
    array (
      'authenticated' => 60,
      'unauthenticated' => 10,
    ),
  ),
  'superadmin' => 
  array (
    'rate_limits' => 
    array (
      'dashboard' => 
      array (
        'max_attempts' => 60,
        'decay_minutes' => 1,
      ),
      'bulk_operations' => 
      array (
        'max_attempts' => 10,
        'decay_minutes' => 1,
      ),
      'exports' => 
      array (
        'max_attempts' => 5,
        'decay_minutes' => 1,
      ),
      'password_resets' => 
      array (
        'max_attempts' => 3,
        'decay_minutes' => 60,
      ),
    ),
    'security' => 
    array (
      'audit_log_retention_days' => 90,
      'impersonation_timeout_minutes' => 30,
      'require_confirmation_for_sensitive_operations' => true,
    ),
    'performance' => 
    array (
      'widget_cache_ttl_seconds' => 60,
      'dashboard_metrics_cache_ttl_seconds' => 300,
      'max_bulk_operation_size' => 100,
    ),
  ),
  'throttle' => 
  array (
    'login' => 
    array (
      'max_attempts' => 5,
      'decay_minutes' => 1,
    ),
    'register' => 
    array (
      'max_attempts' => 3,
      'decay_minutes' => 60,
    ),
    'password_reset' => 
    array (
      'max_attempts' => 3,
      'decay_minutes' => 60,
    ),
    'api' => 
    array (
      'max_attempts' => 60,
      'decay_minutes' => 1,
    ),
  ),
  'tinker' => 
  array (
    'commands' => 
    array (
    ),
    'alias' => 
    array (
    ),
    'dont_alias' => 
    array (
      0 => 'App\\Nova',
    ),
    'trust_project' => 'always',
  ),
  'translation' => 
  array (
    'loader' => 'file',
    'path' => '/Users/andrejprus/Herd/tenanto/lang',
    'cache' => false,
  ),
  'boost' => 
  array (
    'enabled' => true,
    'browser_logs_watcher' => true,
    'executable_paths' => 
    array (
      'php' => NULL,
      'composer' => NULL,
      'npm' => NULL,
      'vendor_bin' => NULL,
      'current_directory' => '/Users/andrejprus/Herd/tenanto',
    ),
  ),
  'mcp' => 
  array (
    'redirect_domains' => 
    array (
      0 => '*',
    ),
  ),
);
