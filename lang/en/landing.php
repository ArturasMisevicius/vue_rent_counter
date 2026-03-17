<?php

return [
    'brand' => [
        'tagline' => 'Property Operations Sandbox',
        'kicker' => 'Live public entry',
    ],
    'hero' => [
        'eyebrow' => 'Tester-first homepage',
        'title' => 'Property operations platform, presented as a guided testing lab.',
        'description' => 'Tenanto is growing into a multi-role operations platform for onboarding, billing, metering, tenant self-service, and platform governance. This public page helps future users understand the product while giving system testers a clear place to validate localization and access flows.',
        'chips' => [
            'Live testing environment',
            '4 interface roles',
            'Localized guest experience',
        ],
    ],
    'roles' => [
        'heading' => 'Role overview',
        'description' => 'Tenanto is being assembled around four coordinated experiences, each with a clear operational responsibility.',
        'items' => [
            [
                'name' => 'Superadmin',
                'description' => 'Platform control, governance, translation oversight, and rollout monitoring.',
            ],
            [
                'name' => 'Admin',
                'description' => 'Organization setup, onboarding, billing readiness, and operational ownership.',
            ],
            [
                'name' => 'Manager',
                'description' => 'Day-to-day execution across buildings, properties, readings, and tenant-facing work.',
            ],
            [
                'name' => 'Tenant',
                'description' => 'Self-service access to readings, invoices, profile details, and property context.',
            ],
        ],
    ],
    'tester' => [
        'heading' => 'For system testers',
        'description' => 'Use this page as the public starting point for validating the current guest experience.',
        'items' => [
            'Verify the Login and Register entry points.',
            'Switch between English, Lithuanian, Spanish, and Russian.',
            'Confirm the public copy matches the planned role model.',
            'Check that the roadmap communicates the next product surfaces honestly.',
        ],
    ],
    'roadmap' => [
        'heading' => 'What Tenanto is growing into',
        'lead' => 'Tenanto is being shaped for future operators, organizations, and tenants.',
        'description' => 'The current plans extend Tenanto from public auth into a full property-operations platform.',
        'status' => 'Planned',
        'items' => [
            [
                'title' => 'Shared interface shell',
                'description' => 'A role-aware authenticated shell that keeps navigation, localization, and account context aligned.',
            ],
            [
                'title' => 'Platform control plane',
                'description' => 'Superadmin tools for governance, monitoring, organizations, subscriptions, and translation management.',
            ],
            [
                'title' => 'Organization operations',
                'description' => 'Admin and manager workflows for buildings, properties, meters, invoices, providers, and reporting.',
            ],
            [
                'title' => 'Tenant self-service',
                'description' => 'A mobile-first portal for invoice history, reading submission, and profile maintenance.',
            ],
        ],
    ],
    'cta' => [
        'heading' => 'Ready to explore the public flow?',
        'description' => 'Sign in if you already have access, or register a new Admin account to begin the onboarding journey.',
        'note' => 'Manager and Tenant access is provisioned through organization invitations.',
        'login' => 'Login',
        'register' => 'Register',
    ],
];
