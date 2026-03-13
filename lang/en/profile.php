<?php

declare(strict_types=1);

return [
    'admin' => [
        'title' => 'Admin Profile',
        'org_title' => 'Organization Profile',
        'org_description' => 'Manage your organization account, subscription, and security settings.',
        'profile_title' => 'Profile',
        'profile_description' => 'Manage your personal account details.',
        'alerts' => [
            'errors' => 'Please fix the highlighted fields and try again.',
            'expired_body' => 'Your subscription expired on :date. Please renew to keep full access.',
            'expired_title' => 'Subscription Expired',
            'expiring_body' => 'Your subscription expires in :days (on :date). Consider renewing soon.',
            'expiring_title' => 'Subscription Expiring Soon',
        ],
        'days' => '{1}:count day|[2,*]:count days',
        'language_form' => [
            'description' => 'Choose the language you prefer for the admin workspace.',
            'title' => 'Language',
        ],
        'password_form' => [
            'confirm' => 'Confirm New Password',
            'current' => 'Current Password',
            'description' => 'Use a strong password to keep your account secure.',
            'new' => 'New Password',
            'submit' => 'Update Password',
            'title' => 'Password',
        ],
        'profile_form' => [
            'description' => 'Update your primary contact details used in the platform.',
            'currency' => 'Currency',
            'email' => 'Email',
            'name' => 'Name',
            'organization' => 'Organization Name',
            'submit' => 'Save Changes',
            'title' => 'Profile Details',
        ],
        'subscription' => [
            'approaching_limit' => 'You are approaching your plan limit.',
            'card_title' => 'Subscription',
            'days_remaining' => '(:days remaining)',
            'description' => 'Current plan status, expiry information, and usage limits.',
            'expiry_date' => 'Expiry Date',
            'plan_type' => 'Plan',
            'properties' => 'Properties',
            'start_date' => 'Start Date',
            'status' => 'Status',
            'tenants' => 'Tenants',
            'usage_limits' => 'Usage Limits',
        ],
    ],
    'superadmin' => [
        'title' => 'Superadmin Profile',
        'heading' => 'Superadmin Profile',
        'description' => 'Manage your platform-level account settings and language preferences.',
        'actions' => [
            'update_profile' => 'Update Profile',
        ],
        'alerts' => [
            'errors' => 'Please fix the highlighted fields and try again.',
        ],
        'language_form' => [
            'description' => 'Choose your preferred interface language for the superadmin workspace.',
            'title' => 'Language',
        ],
        'profile_form' => [
            'description' => 'Update your core account identity and optional password from one place.',
            'currency' => 'Currency',
            'email' => 'Email',
            'name' => 'Name',
            'password' => 'New Password',
            'password_confirmation' => 'Confirm New Password',
            'password_hint' => 'Leave password fields blank if you do not want to change it.',
            'title' => 'Account Details',
        ],
    ],
];
