<?php

return [
    'profile' => [
        'title' => 'My Profile',
        'description' => 'Manage your personal information, preferred language, and sign-in credentials from one shared account page.',
        'personal_information' => 'Personal Information',
        'fields' => [
            'name' => 'Name',
            'email' => 'Email',
            'locale' => 'Language Preference',
        ],
        'language_preference' => 'Language Preference',
        'security' => 'Security',
        'change_password' => 'Change Password',
        'password_description' => 'Use your current password to confirm the change, then choose a new password for future sign-ins.',
        'current_password' => 'Current Password',
        'new_password' => 'New Password',
        'confirm_password' => 'Confirm New Password',
        'messages' => [
            'saved' => 'Your profile details were updated.',
            'password_updated' => 'Your password was updated.',
        ],
    ],
    'settings' => [
        'title' => 'Settings',
        'description' => 'Configure organization-level billing, notification, and subscription preferences for your workspace.',
        'organization' => [
            'title' => 'Organization Settings',
            'description' => 'Keep your billing contact information and invoice copy consistent across the organization workspace.',
            'billing_contact_name' => 'Billing Contact Name',
            'billing_contact_email' => 'Billing Contact Email',
            'billing_contact_phone' => 'Billing Contact Phone',
            'payment_instructions' => 'Payment Instructions',
            'invoice_footer' => 'Invoice Footer',
        ],
        'notifications' => [
            'title' => 'Notification Preferences',
            'description' => 'Decide which operational alerts should stay visible for this organization.',
            'invoice_reminders' => 'Invoice Reminders',
            'invoice_reminders_help' => 'Show reminder controls for overdue invoices and due-date follow-ups.',
            'reading_deadline_alerts' => 'Reading Deadline Alerts',
            'reading_deadline_alerts_help' => 'Highlight meters that are approaching their next expected reading window.',
        ],
        'subscription' => [
            'title' => 'Subscription',
            'description' => 'Review the active commercial state and extend the current plan without leaving the organization workspace.',
            'plan' => 'Plan',
            'status' => 'Status',
            'expires_at' => 'Expires',
            'duration' => 'Renewal Duration',
            'not_set' => 'Not set',
        ],
        'manager' => [
            'title' => 'Settings',
            'description' => 'Organization billing, notification, and subscription controls stay with admins. Use your profile page for account-level changes.',
        ],
        'messages' => [
            'organization_saved' => 'Organization settings were updated.',
            'notifications_saved' => 'Notification preferences were updated.',
            'subscription_renewed' => 'Subscription renewal details were updated.',
        ],
    ],
    'actions' => [
        'save_profile' => 'Save Profile',
        'update_password' => 'Update Password',
        'save_settings' => 'Save Settings',
        'save_notifications' => 'Save Notification Preferences',
        'renew_subscription' => 'Renew Subscription',
    ],
];
