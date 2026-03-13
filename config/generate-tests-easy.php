<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Test Generation Paths
    |--------------------------------------------------------------------------
    |
    | Define where generated tests should be placed. These paths are relative
    | to the tests/ directory.
    |
    */
    'paths' => [
        'feature' => 'Feature',
        'unit' => 'Unit',
        'performance' => 'Performance',
        'security' => 'Security',
    ],

    /*
    |--------------------------------------------------------------------------
    | Test Namespaces
    |--------------------------------------------------------------------------
    |
    | Define the namespaces for generated tests.
    |
    */
    'namespaces' => [
        'feature' => 'Tests\\Feature',
        'unit' => 'Tests\\Unit',
        'performance' => 'Tests\\Performance',
        'security' => 'Tests\\Security',
    ],

    /*
    |--------------------------------------------------------------------------
    | Test Templates
    |--------------------------------------------------------------------------
    |
    | Customize the templates used for generating different types of tests.
    | Set to null to use package defaults.
    |
    */
    'templates' => [
        'controller' => base_path('tests/stubs/controller.test.stub'),
        'model' => base_path('tests/stubs/model.test.stub'),
        'service' => base_path('tests/stubs/service.test.stub'),
        'filament' => base_path('tests/stubs/filament.test.stub'),
        'policy' => base_path('tests/stubs/policy.test.stub'),
        'middleware' => base_path('tests/stubs/middleware.test.stub'),
        'observer' => base_path('tests/stubs/observer.test.stub'),
        'value-object' => base_path('tests/stubs/value-object.test.stub'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Multi-Tenancy Configuration
    |--------------------------------------------------------------------------
    |
    | Configure multi-tenancy support for generated tests.
    |
    */
    'multi_tenancy' => [
        'enabled' => true,
        'tenant_trait' => 'App\\Traits\\BelongsToTenant',
        'tenant_context' => 'App\\Services\\TenantContext',
        'tenant_scope' => 'App\\Scopes\\TenantScope',
        'hierarchical_scope' => 'App\\Scopes\\HierarchicalScope',
    ],

    /*
    |--------------------------------------------------------------------------
    | Test Framework
    |--------------------------------------------------------------------------
    |
    | Configure the test framework to use (PHPUnit or Pest).
    |
    */
    'framework' => [
        'type' => 'pest', // 'phpunit' or 'pest'
        'version' => 3,
    ],

    /*
    |--------------------------------------------------------------------------
    | Test Helpers
    |--------------------------------------------------------------------------
    |
    | Configure test helpers and utilities to include in generated tests.
    |
    */
    'helpers' => [
        'use_refresh_database' => true,
        'use_factories' => true,
        'use_seeders' => false,
        'custom_traits' => [
            // Add custom traits to include in all tests
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Authentication
    |--------------------------------------------------------------------------
    |
    | Configure authentication setup for generated tests.
    |
    */
    'authentication' => [
        'enabled' => true,
        'default_role' => 'admin',
        'helpers' => [
            'actingAsAdmin' => true,
            'actingAsManager' => true,
            'actingAsTenant' => true,
            'actingAsSuperadmin' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Assertions
    |--------------------------------------------------------------------------
    |
    | Configure automatic assertion generation.
    |
    */
    'assertions' => [
        'auto_generate' => true,
        'include_database' => true,
        'include_response' => true,
        'include_validation' => true,
        'include_authorization' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Mocking
    |--------------------------------------------------------------------------
    |
    | Configure automatic mock generation for dependencies.
    |
    */
    'mocking' => [
        'enabled' => true,
        'auto_detect_dependencies' => true,
        'mock_external_services' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Coverage
    |--------------------------------------------------------------------------
    |
    | Configure test coverage options.
    |
    */
    'coverage' => [
        'generate_report' => true,
        'min_coverage' => 80,
        'exclude_paths' => [
            'vendor',
            'node_modules',
            'storage',
            'bootstrap/cache',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Naming Conventions
    |--------------------------------------------------------------------------
    |
    | Configure naming conventions for generated tests.
    |
    */
    'naming' => [
        'test_class_suffix' => 'Test',
        'test_method_prefix' => 'test_',
        'use_descriptive_names' => true,
        'use_snake_case' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | File Generation
    |--------------------------------------------------------------------------
    |
    | Configure file generation behavior.
    |
    */
    'generation' => [
        'overwrite_existing' => false,
        'backup_existing' => true,
        'create_directories' => true,
        'format_code' => true,
        'add_comments' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Filament Configuration
    |--------------------------------------------------------------------------
    |
    | Configure Filament-specific test generation.
    |
    */
    'filament' => [
        'enabled' => true,
        'version' => 4,
        'test_resources' => true,
        'test_pages' => true,
        'test_widgets' => true,
        'test_actions' => true,
        'test_forms' => true,
        'test_tables' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Laravel Features
    |--------------------------------------------------------------------------
    |
    | Configure Laravel-specific features to test.
    |
    */
    'laravel' => [
        'test_routes' => true,
        'test_middleware' => true,
        'test_policies' => true,
        'test_observers' => true,
        'test_events' => true,
        'test_jobs' => true,
        'test_notifications' => true,
        'test_mail' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Rules
    |--------------------------------------------------------------------------
    |
    | Define custom rules for test generation.
    |
    */
    'custom_rules' => [
        // Add custom rules here
        'always_test_tenant_isolation' => true,
        'always_test_authorization' => true,
        'always_test_validation' => true,
        'include_property_tests' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Exclusions
    |--------------------------------------------------------------------------
    |
    | Define classes or methods to exclude from test generation.
    |
    */
    'exclusions' => [
        'classes' => [
            // Classes to exclude
        ],
        'methods' => [
            '__construct',
            '__destruct',
            '__get',
            '__set',
            '__call',
        ],
        'patterns' => [
            // Regex patterns to exclude
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Documentation
    |--------------------------------------------------------------------------
    |
    | Configure documentation generation for tests.
    |
    */
    'documentation' => [
        'generate' => true,
        'format' => 'markdown',
        'output_path' => 'docs/testing',
        'include_examples' => true,
    ],
];
