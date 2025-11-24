<?php

declare(strict_types=1);

return [
    'subscription_expiry' => [
        'subject' => 'Subscription Expiry Warning',
        'greeting' => 'Hello :name!',
        'intro' => 'Your subscription to the Vilnius Utilities Billing System will expire in **:days days** on **:date**.',
        'plan' => '**Current Plan:** :plan',
        'properties' => '**Properties:** :used / :max',
        'tenants' => '**Tenants:** :used / :max',
        'cta_intro' => 'To avoid interruption of service, please renew your subscription before it expires.',
        'cta_notice' => 'After expiry, your account will be restricted to read-only access until renewal.',
        'action' => 'Renew Subscription',
        'support' => 'If you have any questions about renewal, please contact support.',
    ],

    'welcome' => [
        'subject' => 'Welcome to Vilnius Utilities Billing System',
        'greeting' => 'Hello :name!',
        'account_created' => 'Your tenant account has been created for the following property:',
        'address' => '**Address:** :address',
        'property_type' => '**Property Type:** :type',
        'credentials_heading' => '**Login Credentials:**',
        'email' => 'Email: :email',
        'temporary_password' => 'Temporary Password: :password',
        'password_reminder' => 'Please log in and change your password immediately.',
        'action' => 'Log In',
        'support' => 'If you have any questions, please contact your property administrator.',
    ],

    'tenant_reassigned' => [
        'subject' => 'Property Assignment Updated',
        'greeting' => 'Hello :name!',
        'updated' => 'Your property assignment has been updated.',
        'previous' => '**Previous Property:** :address',
        'new' => '**New Property:** :address',
        'assigned' => 'You have been assigned to a property:',
        'property' => '**Property:** :address',
        'property_type' => '**Property Type:** :type',
        'view_dashboard' => 'View Dashboard',
        'info' => 'You can now view your utility information for this property.',
        'support' => 'If you have any questions, please contact your property administrator.',
    ],

    'meter_reading_submitted' => [
        'subject' => 'New Meter Reading Submitted',
        'greeting' => 'Hello :name!',
        'submitted_by' => 'A new meter reading has been submitted by **:tenant**.',
        'details' => '**Reading Details:**',
        'property' => 'Property: :address',
        'meter_type' => 'Meter Type: :type',
        'serial' => 'Serial Number: :serial',
        'reading_date' => 'Reading Date: :date',
        'reading_value' => 'Reading Value: :value',
        'zone' => 'Zone: :zone',
        'consumption' => 'Consumption: :consumption',
        'view' => 'View Meter Readings',
        'manage_hint' => 'You can review and manage all meter readings from your dashboard.',
    ],

    'overdue_invoice' => [
        'subject' => 'Invoice #:id is overdue',
        'greeting' => 'Hello :name,',
        'overdue' => 'Invoice #:id is overdue.',
        'amount' => 'Total amount: :amount',
        'due_date' => 'Due date: :date',
        'pay_notice' => 'Please pay this invoice as soon as possible to avoid service issues.',
        'action' => 'View Invoice',
        'ignore' => 'If you have already paid, you can ignore this message.',
    ],

    'profile' => [
        'updated' => 'Profile updated successfully.',
        'password_updated' => 'Password updated successfully.',
    ],
];
