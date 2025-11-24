<?php

return [
    'hero' => [
        'title' => 'Rent Counter keeps readings, invoices, and tenants perfectly aligned.',
        'tagline' => 'All teams share one source of truth across properties, tariffs, and meter readings.',
        'badge' => 'Utilities · Billing · Compliance',
    ],
    'metrics' => [
        'cache' => 'Dashboard cache refresh',
        'readings' => 'Unvalidated readings in production',
        'isolation' => 'Role-based isolation for tenants',
    ],
    'features_title' => 'Features built for utilities billing',
    'features_subtitle' => 'Why teams stay aligned',
    'cta_bar' => [
        'title' => 'Log in or register to start.',
        'eyebrow' => 'Ready to align billing?',
    ],
    'faq_intro' => 'We built Rent Counter to keep property managers, finance, and tenants confident. Here’s what most teams ask when they join.',
    'features' => [
        'unified_metering' => [
            'title' => 'Unified metering',
            'description' => 'Collect electricity, water, and heating readings in one place with automatic validation and anomaly detection.',
        ],
        'accurate_invoicing' => [
            'title' => 'Accurate invoicing',
            'description' => 'Generate itemized invoices with tariff versions, zones, and taxes while keeping tenants and managers in sync.',
        ],
        'role_access' => [
            'title' => 'Role-based access',
            'description' => 'Dashboards for admins, managers, and tenants with strict policy enforcement and audit trails.',
        ],
        'reporting' => [
            'title' => 'Reporting that informs',
            'description' => 'Consumption, revenue, and compliance reports with filters, exports, and pagination.',
        ],
        'performance' => [
            'title' => 'Performance-first',
            'description' => 'Optimized queries, eager loading, and caching so large portfolios stay fast.',
        ],
        'tenant_clarity' => [
            'title' => 'Tenant clarity',
            'description' => 'Self-serve tenant views with invoice breakdowns, trends, and property-specific filtering.',
        ],
    ],
    'faq' => [
        'validation' => [
            'question' => 'How are meter readings validated?',
            'answer' => 'Readings pass monotonic checks, zone validation, and anomaly detection before invoicing.',
        ],
        'tenants' => [
            'question' => 'Can tenants see only their properties?',
            'answer' => 'Yes, dashboards enforce TenantScope and policies so users only see their assigned data.',
        ],
        'invoices' => [
            'question' => 'Do invoices support versions and corrections?',
            'answer' => 'Invoices track drafts, finalization timestamps, and correction audits to preserve history.',
        ],
        'admin' => [
            'question' => 'Is there an admin back office?',
            'answer' => 'Admins manage users, providers, tariffs, and audits via the Filament panel with role-based navigation.',
        ],
    ],
    'dashboard' => [
        'live_overview' => 'Live overview',
        'portfolio_health' => 'Portfolio health',
        'healthy' => 'Healthy',
        'draft_invoices' => 'Draft invoices',
        'draft_invoices_hint' => 'Ready to finalize',
        'meters_validated' => 'Meters validated',
        'meters_validated_hint' => 'Across all zones',
        'recent_readings' => 'Recent readings',
        'water' => 'Water',
        'water_status' => 'Monotonic ✓',
        'electricity' => 'Electricity',
        'electricity_status' => 'Anomaly scan ✓',
        'heating' => 'Heating',
        'heating_status' => 'Zone split ✓',
        'trusted' => 'Trusted',
    ],
    'faq_section' => [
        'eyebrow' => 'Answers you need',
        'title' => 'FAQ',
        'category_prefix' => 'Category:',
    ],
    'metric_values' => [
        'five_minutes' => '5 min',
        'zero' => 'Zero',
        'full' => '100%',
    ],
];
