<?php

return [
    'brand' => [
        'tagline' => 'Tenant and Utility Operations Hub',
        'kicker' => 'Pre-login workspace',
    ],
    'hero' => [
        'eyebrow' => 'One platform for every property role',
        'title' => 'Run leasing, billing, and tenant service from one clear command surface.',
        'description' => 'Tenanto gives teams a single place to onboard organizations, manage buildings and properties, process metering and invoices, and deliver a dependable tenant experience. This public page introduces the full flow before users sign in.',
        'chips' => [
            'Role-based operator workspaces',
            'Localized guest and auth journey',
            'Meter-to-invoice operational flow',
            'Tenant self-service readiness',
        ],
    ],
    'roles' => [
        'heading' => 'Role workspaces',
        'description' => 'Each role gets a focused workspace, while platform rules keep every handoff aligned across the same data model.',
        'items' => [
            [
                'name' => 'Superadmin',
                'description' => 'Owns platform health, global governance, subscription enforcement, and language policy.',
            ],
            [
                'name' => 'Admin',
                'description' => 'Configures organizations, controls operational standards, and supervises billing readiness.',
            ],
            [
                'name' => 'Manager',
                'description' => 'Executes day-to-day building operations, meter workflows, and tenant-facing tasks.',
            ],
            [
                'name' => 'Tenant',
                'description' => 'Uses a streamlined portal for invoices, meter submissions, and profile updates.',
            ],
        ],
    ],
    'tester' => [
        'heading' => 'Launch checklist',
        'description' => 'Use this checklist to validate the full public entry experience before moving into authenticated workflows.',
        'items' => [
            'Confirm primary entry points for Login and Register are clearly visible and consistent.',
            'Switch guest locale and verify each language keeps structure, hierarchy, and CTA intent.',
            'Validate that each role card communicates ownership without overlap or ambiguity.',
            'Review roadmap tracks and ensure planned capabilities reflect current implementation scope.',
        ],
    ],
    'roadmap' => [
        'heading' => 'Operational roadmap',
        'lead' => 'Tenanto is expanding from public access into a complete role-aware operations platform.',
        'description' => 'Each roadmap track closes a concrete gap between onboarding, billing, governance, and tenant-facing reliability.',
        'status' => 'Planned',
        'items' => [
            [
                'title' => 'Unified authenticated shell',
                'description' => 'A consistent shell for navigation, locale continuity, notifications, and account context across all roles.',
            ],
            [
                'title' => 'Platform governance layer',
                'description' => 'Superadmin controls for organizations, subscriptions, translation governance, and security monitoring.',
            ],
            [
                'title' => 'Organization operations core',
                'description' => 'Admin and manager workflows for buildings, properties, providers, metering, invoicing, and reporting.',
            ],
            [
                'title' => 'Tenant service portal',
                'description' => 'A mobile-first tenant area for invoices, reading submissions, profile updates, and property context.',
            ],
            [
                'title' => 'Cross-cutting platform rules',
                'description' => 'Shared validation, policy enforcement, and subscription rules that keep role journeys reliable.',
            ],
        ],
    ],
    'preview' => [
        'operations_workspace' => 'Operations workspace',
        'columns' => [
            'workflow' => 'Workflow',
            'status' => 'Status',
            'owner' => 'Owner',
        ],
    ],
    'cta' => [
        'heading' => 'Start with the right entry point',
        'description' => 'Sign in to continue active operations, or register a new Admin account to launch your organization workspace.',
        'note' => 'Manager and Tenant access remain invitation-based to preserve controlled onboarding and tenant isolation.',
        'login' => 'Login',
        'register' => 'Register',
    ],
];
