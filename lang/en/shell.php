<?php

return [
    'search' => [
        'label' => 'Global search',
        'placeholder' => 'Search anything',
        'groups' => [
            'platform' => 'Platform',
            'organization' => 'Organization',
            'tenant' => 'Tenant',
        ],
        'empty' => [
            'heading' => 'No results yet',
            'body' => 'Search results will appear here when matching routes and records exist.',
        ],
    ],
    'navigation' => [
        'groups' => [
            'platform' => 'Platform',
            'organization' => 'Organization',
            'account' => 'Account',
        ],
        'items' => [
            'organizations' => 'Organizations',
            'profile' => 'Profile',
            'settings' => 'Settings',
        ],
    ],
    'roles' => [
        'superadmin' => 'Superadmin',
        'admin' => 'Admin',
        'manager' => 'Manager',
        'tenant' => 'Tenant',
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
    'settings' => [
        'title' => 'Settings',
        'organization' => [
            'heading' => 'Organization Settings',
            'description' => 'Manage billing contacts and the payment details shown to future users.',
            'fields' => [
                'billing_contact_name' => 'Billing Contact Name',
                'billing_contact_email' => 'Billing Contact Email',
                'billing_contact_phone' => 'Billing Contact Phone',
                'payment_instructions' => 'Payment Instructions',
                'invoice_footer' => 'Invoice Footer',
            ],
            'actions' => [
                'save' => 'Save Organization Settings',
            ],
        ],
        'notifications' => [
            'heading' => 'Notification Preferences',
            'description' => 'Choose which reminders admins should receive inside the organization workspace.',
            'fields' => [
                'invoice_reminders' => 'Invoice reminders',
                'reading_deadline_alerts' => 'Reading deadline alerts',
            ],
            'actions' => [
                'save' => 'Save Notification Preferences',
            ],
        ],
        'subscription' => [
            'heading' => 'Subscription',
            'description' => 'Renew the current plan and refresh usage limits for the organization.',
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
                'renew' => 'Renew Subscription',
            ],
        ],
        'messages' => [
            'organization_saved' => 'Organization settings have been updated.',
            'notifications_saved' => 'Notification preferences have been updated.',
            'subscription_renewed' => 'Subscription has been renewed.',
        ],
    ],
    'actions' => [
        'back_to_dashboard' => 'Back to dashboard',
    ],
    'impersonation' => [
        'eyebrow' => 'Impersonation active',
        'heading' => 'You are impersonating this account',
        'actions' => [
            'stop' => 'Stop impersonating',
        ],
    ],
    'errors' => [
        'eyebrow' => 'Error :status',
        '403' => [
            'title' => 'You do not have permission to view this page',
            'description' => 'Your account does not currently have access to this area. If you believe this is a mistake, contact your administrator or return to the correct dashboard.',
        ],
        '404' => [
            'title' => 'The page you are looking for does not exist',
            'description' => 'The link may be outdated, incomplete, or no longer available. Return to your dashboard to continue working safely.',
        ],
        '500' => [
            'title' => 'Something went wrong on our side',
            'description' => 'We could not complete that request right now. Please try again in a moment or contact support if the problem continues.',
        ],
    ],
    'notifications' => [
        'heading' => 'Notifications',
        'unread_count' => '{0} No unread notifications|{1} :count unread notification|[2,*] :count unread notifications',
        'actions' => [
            'toggle' => 'Toggle notifications',
            'mark_all_read' => 'Mark all as read',
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
];
