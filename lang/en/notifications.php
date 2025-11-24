<?php

declare(strict_types=1);

return [
    'invoice' => [
        'created' => 'Invoice created successfully.',
        'updated' => 'Invoice updated successfully.',
        'deleted' => 'Invoice deleted successfully.',
        'finalized' => 'Invoice finalized successfully.',
        'finalized_locked' => 'Invoice finalized successfully. It is now immutable.',
        'marked_paid' => 'Invoice marked as paid.',
        'sent' => 'Invoice sent successfully.',
        'generated_bulk' => 'Generated :count invoices successfully.',
        'none' => 'No recent invoices',
    ],

    'invoice_item' => [
        'created' => 'Invoice item added successfully.',
        'updated' => 'Invoice item updated successfully.',
        'deleted' => 'Invoice item deleted successfully.',
    ],

    'meter' => [
        'created' => 'Meter created successfully.',
        'updated' => 'Meter updated successfully.',
        'deleted' => 'Meter deleted successfully.',
    ],

    'meter_reading' => [
        'created' => 'Meter reading created successfully.',
        'updated' => 'Meter reading updated successfully.',
        'deleted' => 'Meter reading deleted successfully.',
        'bulk_created' => 'Bulk readings created successfully.',
        'corrected' => 'Meter reading corrected successfully. Audit trail created.',
    ],

    'building' => [
        'created' => 'Building created successfully.',
        'updated' => 'Building updated successfully.',
        'deleted' => 'Building deleted successfully.',
        'gyvatukas' => 'Gyvatukas calculated: :average kWh',
        'gyvatukas_summer' => 'Gyvatukas summer average calculated: :average kWh',
    ],

    'subscription' => [
        'updated' => 'Subscription updated successfully.',
        'renewed' => 'Subscription renewed successfully.',
        'suspended' => 'Subscription suspended successfully.',
        'cancelled' => 'Subscription cancelled successfully.',
    ],

    'organization' => [
        'created' => 'Organization created successfully.',
        'updated' => 'Organization updated successfully.',
        'deactivated' => 'Organization deactivated successfully.',
        'reactivated' => 'Organization reactivated successfully.',
    ],

    'tenant' => [
        'created' => 'Tenant created successfully.',
        'updated' => 'Tenant updated successfully.',
        'deleted' => 'Tenant deleted successfully.',
        'invoice_sent' => 'Invoice sent successfully.',
    ],

    'admin_tenant' => [
        'created' => 'Tenant account created successfully. Welcome email has been sent.',
        'updated' => 'Tenant account updated successfully.',
        'deactivated' => 'Tenant account deactivated successfully.',
        'reactivated' => 'Tenant account reactivated successfully.',
        'reassigned' => 'Tenant reassigned successfully. Notification email has been sent.',
    ],

    'property' => [
        'created' => 'Property created successfully.',
        'updated' => 'Property updated successfully.',
        'deleted' => 'Property deleted successfully.',
    ],

    'user' => [
        'created' => 'User created successfully.',
        'updated' => 'User updated successfully.',
        'deleted' => 'User deleted successfully.',
    ],

    'profile' => [
        'updated' => 'Profile updated successfully.',
        'password_updated' => 'Password updated successfully.',
    ],

    'settings' => [
        'updated' => 'Settings updated successfully. Note: Some changes may require updating the .env file and restarting the application.',
        'backup_completed' => 'Backup completed successfully.',
        'cache_cleared' => 'All caches cleared successfully.',
        'backup_failed' => 'Backup failed: :message',
        'cache_failed' => 'Failed to clear cache: :message',
    ],

    'tariff' => [
        'created' => 'Tariff created successfully.',
        'version_created' => 'New tariff version created successfully.',
        'updated' => 'Tariff updated successfully.',
        'deleted' => 'Tariff deleted successfully.',
    ],

    'account' => [
        'updated' => 'Subscription updated successfully.',
    ],
];
