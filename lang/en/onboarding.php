<?php

return [
    'title' => 'Start your free trial',
    'subtitle' => 'Set up your organization to unlock your admin workspace.',
    'trial_badge' => '14-Day Free Trial',
    'trial_message' => 'You are one step away from your Tenanto admin workspace. Create your organization now and we will activate your free trial immediately.',
    'organization_name_label' => 'Organization Name',
    'submit_button' => 'Activate Free Trial',
    'submit_button_loading' => 'Activating free trial...',
    'tour' => [
        'badge' => 'Product tour',
        'title' => 'Welcome to your workspace',
        'subtitle' => 'A short guided tour explains where the main tools are and how each area should be used.',
        'progress_label' => 'Tour progress',
        'step_count' => 'Step :current of :total',
        'actions' => [
            'back' => 'Back',
            'close' => 'Close tour',
            'finish' => 'Finish',
            'later' => 'Later',
            'next' => 'Next',
            'open' => 'Guide',
        ],
        'roles' => [
            'admin' => [
                'steps' => [
                    'workspace' => [
                        'title' => 'Dashboard and workspace',
                        'body' => 'The dashboard is the starting point for organization work. It summarizes properties, invoices, readings, and subscription limits so you can see what needs attention first.',
                        'detail' => 'Use it as the daily overview before opening detailed pages from the sidebar.',
                    ],
                    'navigation' => [
                        'title' => 'Sidebar, search, and language',
                        'body' => 'The left menu groups property, billing, reporting, and account tools. Global search is at the top, while the language selector changes the interface copy for your account.',
                        'detail' => 'Open records from the sidebar when you know the module, or search when you know a name, number, tenant, invoice, or meter.',
                    ],
                    'workflows' => [
                        'title' => 'Properties and tenants',
                        'body' => 'Buildings, properties, tenants, meters, and readings are connected. Start with the building or property, then review tenants, meters, and reading history from the related pages.',
                        'detail' => 'Create and edit data from the module pages; avoid duplicate records by checking the related property first.',
                    ],
                    'activity' => [
                        'title' => 'Billing and reports',
                        'body' => 'Invoices, tariffs, providers, service configurations, and utility services control billing. Reports are for review and operational checks.',
                        'detail' => 'Keep provider and tariff settings current before generating or reviewing invoice activity.',
                    ],
                    'profile' => [
                        'title' => 'Profile, settings, and managers',
                        'body' => 'Use Profile for your own details. Settings controls organization billing preferences, while Organization Users lets you review manager access.',
                        'detail' => 'Managers can be limited by permission matrix, so grant only the create, edit, and delete actions they should perform.',
                    ],
                ],
            ],
            'manager' => [
                'steps' => [
                    'workspace' => [
                        'title' => 'Your assigned workspace',
                        'body' => 'The dashboard shows the organization information you can review. Your admin controls which create, edit, and delete actions are available to you.',
                        'detail' => 'If a button is missing or a write action is blocked, the manager permission matrix likely does not grant that action.',
                    ],
                    'navigation' => [
                        'title' => 'Menu and search',
                        'body' => 'Use the sidebar to move between property records, readings, invoices, providers, and reports. Search helps you open known records faster.',
                        'detail' => 'The menu only shows the sections your role can use in this organization.',
                    ],
                    'workflows' => [
                        'title' => 'Properties, meters, and readings',
                        'body' => 'Use properties as the anchor for operational work. Meters and meter readings should be checked against the assigned property before editing.',
                        'detail' => 'When submitting readings, confirm the meter, date, value, and validation message before saving.',
                    ],
                    'activity' => [
                        'title' => 'Billing and reports',
                        'body' => 'Billing pages are visible when your manager permissions include billing work. Reports are available for review and follow-up.',
                        'detail' => 'If billing is hidden, ask an admin to grant the needed billing permissions.',
                    ],
                    'profile' => [
                        'title' => 'Profile and account',
                        'body' => 'Use Profile to update your name, email, phone, password, language, and avatar. Organization settings stay with admins.',
                        'detail' => 'Keep your profile current so notifications and audit history identify you correctly.',
                    ],
                ],
            ],
            'tenant' => [
                'steps' => [
                    'workspace' => [
                        'title' => 'Tenant home',
                        'body' => 'Your home page shows assigned property information, latest readings, invoice status, and quick actions for common tenant tasks.',
                        'detail' => 'Start here when you need to check what changed or continue a regular monthly task.',
                    ],
                    'navigation' => [
                        'title' => 'Top menu and mobile menu',
                        'body' => 'The top menu contains Home, Property, Readings, and Invoices. On mobile, open Menu to access the same pages, search, profile, and logout.',
                        'detail' => 'Use the same menu on every tenant page; the active item shows where you are now.',
                    ],
                    'workflows' => [
                        'title' => 'Submit readings',
                        'body' => 'The Readings page lets you choose an assigned meter, enter the date and value, preview the consumption change, and submit everything in one flow.',
                        'detail' => 'Read validation messages before submitting; they explain why a value may be too low, too high, duplicated, or outside the expected period.',
                    ],
                    'activity' => [
                        'title' => 'Invoices and property details',
                        'body' => 'Invoices show billing history, payment state, line items, and payment guidance. Property details show your assigned unit and building information.',
                        'detail' => 'Open invoice details when you need amounts, dates, services, or billing contact information.',
                    ],
                    'profile' => [
                        'title' => 'Account and language',
                        'body' => 'Use your account page to update contact details, preferred language, password, and avatar. The language selector there changes tenant interface labels.',
                        'detail' => 'After changing language or avatar, the tenant menu and pages refresh to use your latest account settings.',
                    ],
                ],
            ],
        ],
    ],
];
