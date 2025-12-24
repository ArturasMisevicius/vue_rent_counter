<?php

declare(strict_types=1);

return [
    'cta_bar' => [
        'eyebrow' => 'Utilities Management',
        'title' => 'Streamline Your Property Operations',
    ],
    'dashboard' => [
        'draft_invoices' => 'Draft Invoices',
        'draft_invoices_hint' => 'Invoices pending finalization',
        'electricity' => 'Electricity',
        'electricity_status' => 'Electricity System Status',
        'healthy' => 'Healthy',
        'heating' => 'Heating',
        'heating_status' => 'Heating System Status',
        'live_overview' => 'Live System Overview',
        'meters_validated' => 'Meters Validated',
        'meters_validated_hint' => 'Meters with validated readings',
        'portfolio_health' => 'Portfolio Health',
        'recent_readings' => 'Recent Meter Readings',
        'trusted' => 'Trusted',
        'water' => 'Water',
        'water_status' => 'Water System Status',
    ],
    'faq_intro' => 'Frequently asked questions about our utilities management platform',
    'faq_section' => [
        'category_prefix' => 'Category:',
        'eyebrow' => 'Support',
        'title' => 'Frequently Asked Questions',
    ],
    'features_subtitle' => 'Everything you need to manage utilities efficiently',
    'features_title' => 'Comprehensive Utilities Management',
    'hero' => [
        'badge' => 'Vilnius Utilities Platform',
        'tagline' => 'Manage properties, meters, and invoices with confidence',
        'title' => 'Modern Utilities Management for Lithuanian Properties',
    ],
    'metric_values' => [
        'five_minutes' => '< 5 minutes',
        'full' => '100%',
        'zero' => '0',
    ],
    'metrics' => [
        'cache' => 'Cache Performance',
        'isolation' => 'Tenant Isolation',
        'readings' => 'Meter Readings',
    ],
    'features' => [
        'unified_metering' => [
            'title' => 'Unified Meter Management',
            'description' => 'Manage all electricity, water, and heating meters in one place with automated reading validation.',
        ],
        'accurate_invoicing' => [
            'title' => 'Accurate Invoice Calculations',
            'description' => 'Automatically generate invoices based on validated meter readings with tariff snapshots.',
        ],
        'role_access' => [
            'title' => 'Role-Based Access Control',
            'description' => 'Secure multi-tenant access management for superadmins, managers, and tenants.',
        ],
        'reporting' => [
            'title' => 'Comprehensive Reporting',
            'description' => 'Generate detailed reports on consumption, revenue, and portfolio performance.',
        ],
        'performance' => [
            'title' => 'High Performance',
            'description' => 'Optimized architecture with caching mechanisms and N+1 query prevention.',
        ],
        'tenant_clarity' => [
            'title' => 'Tenant Transparency',
            'description' => 'Tenants can view their meter readings, invoices, and download PDF statements.',
        ],
    ],
    'faq' => [
        'validation' => [
            'question' => 'How does meter reading validation work?',
            'answer' => 'All meter readings are validated using monotonicity and temporal rules. The system automatically detects anomalies and requires manager approval.',
        ],
        'tenants' => [
            'question' => 'What can tenants see?',
            'answer' => 'Tenants can view their property information, meter readings, invoice history, and download PDF statements. They cannot see other tenants\' data.',
        ],
        'invoices' => [
            'question' => 'How does invoice generation work?',
            'answer' => 'Invoices are generated automatically based on validated meter readings. Tariff snapshots ensure invoice calculations remain accurate even when tariffs change.',
        ],
        'security' => [
            'question' => 'How is data security ensured?',
            'answer' => 'The platform uses multi-tenant isolation, role-based access control, and comprehensive auditing. All data is encrypted and regularly backed up.',
        ],
        'support' => [
            'question' => 'What support is available?',
            'answer' => 'We provide comprehensive documentation, training, and technical support. The platform supports Lithuanian and English languages with localized interfaces.',
        ],
    ],
];
