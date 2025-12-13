<?php

declare(strict_types=1);

return [
    'admin' => [
        'title' => 'Organization Profile',
        'breadcrumb' => 'Profile',
        'org_title' => 'Organization Profile',
        'profile_title' => 'Profile',
        'org_description' => 'Manage your organization profile and subscription',
        'profile_description' => 'Manage your profile information',
        'alerts' => [
            'errors' => 'There were errors with your submission',
            'expired_title' => 'Subscription Expired',
            'expired_body' => 'Your subscription expired on :date. Please contact support to renew your subscription.',
            'expiring_title' => 'Subscription Expiring Soon',
            'expiring_body' => 'Your subscription will expire in :days on :date. Contact support to renew.',
        ],
        'subscription' => [
            'card_title' => 'Subscription Details',
            'plan_type' => 'Plan Type',
            'status' => 'Status',
            'start_date' => 'Start Date',
            'expiry_date' => 'Expiry Date',
            'days_remaining' => '(:days remaining)',
            'usage_limits' => 'Usage Limits',
            'properties' => 'Properties',
            'tenants' => 'Tenants',
            'approaching_limit' => 'Approaching limit - consider upgrading your plan',
        ],
        'profile_form' => [
            'title' => 'Profile Information',
            'name' => 'Full Name',
            'email' => 'Email Address',
            'organization' => 'Organization Name',
            'submit' => 'Update Profile',
        ],
        'password_form' => [
            'title' => 'Change Password',
            'current' => 'Current Password',
            'new' => 'New Password',
            'confirm' => 'Confirm New Password',
            'submit' => 'Update Password',
        ],
        'days' => ':count day|:count days',
    ],
    'superadmin' => [
        'title' => 'Superadmin Profile',
        'heading' => 'Profile & Credentials',
        'description' => 'Manage your profile details and sign-in credentials.',
        'alerts' => [
            'errors' => 'There were errors with your submission',
        ],
        'profile_form' => [
            'title' => 'Profile Information',
            'name' => 'Full Name',
            'email' => 'Email Address',
            'password' => 'New Password',
            'password_confirmation' => 'Confirm Password',
            'password_hint' => 'Leave blank to keep your current password.',
        ],
        'language_form' => [
            'title' => 'Language Preference',
            'description' => 'Choose your preferred language for the interface.',
        ],
        'actions' => [
            'update_profile' => 'Save Changes',
        ],
    ],
];
