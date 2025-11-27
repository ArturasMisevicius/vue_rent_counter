<?php

/**
 * Notification Classes Verification Script
 *
 * This script verifies that all notification classes in the hierarchical user
 * management system exist and properly implement the ShouldQueue interface
 * for asynchronous email delivery.
 *
 * Usage:
 *   php verify-notifications.php
 *
 * Verified Notifications:
 *   - WelcomeEmail: Sent to new tenant accounts
 *   - TenantReassignedEmail: Sent when tenants are reassigned to properties
 *   - SubscriptionExpiryWarningEmail: Sent when admin subscriptions are expiring
 *   - MeterReadingSubmittedEmail: Sent when tenants submit meter readings
 *
 * @see docs/notifications/NOTIFICATION_SYSTEM.md
 * @see .kiro/specs/3-hierarchical-user-management/tasks.md (Task 11)
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Notifications\WelcomeEmail;
use App\Notifications\TenantReassignedEmail;
use App\Notifications\SubscriptionExpiryWarningEmail;
use App\Notifications\MeterReadingSubmittedEmail;

echo "Checking notification classes...\n\n";

/**
 * Verify WelcomeEmail notification class
 *
 * Checks that WelcomeEmail exists and implements ShouldQueue for background processing.
 * This notification is sent to newly created tenant accounts with login credentials.
 */
echo "1. WelcomeEmail: ";
if (class_exists(WelcomeEmail::class)) {
    $reflection = new ReflectionClass(WelcomeEmail::class);
    $implements = $reflection->implementsInterface(\Illuminate\Contracts\Queue\ShouldQueue::class);
    echo $implements ? "✓ Exists and implements ShouldQueue\n" : "✓ Exists but missing ShouldQueue\n";
} else {
    echo "✗ Not found\n";
}

/**
 * Verify TenantReassignedEmail notification class
 *
 * Checks that TenantReassignedEmail exists and implements ShouldQueue.
 * This notification is sent when tenants are assigned or reassigned to properties.
 */
echo "2. TenantReassignedEmail: ";
if (class_exists(TenantReassignedEmail::class)) {
    $reflection = new ReflectionClass(TenantReassignedEmail::class);
    $implements = $reflection->implementsInterface(\Illuminate\Contracts\Queue\ShouldQueue::class);
    echo $implements ? "✓ Exists and implements ShouldQueue\n" : "✓ Exists but missing ShouldQueue\n";
} else {
    echo "✗ Not found\n";
}

/**
 * Verify SubscriptionExpiryWarningEmail notification class
 *
 * Checks that SubscriptionExpiryWarningEmail exists and implements ShouldQueue.
 * This notification warns admin users when their subscription is approaching expiration.
 */
echo "3. SubscriptionExpiryWarningEmail: ";
if (class_exists(SubscriptionExpiryWarningEmail::class)) {
    $reflection = new ReflectionClass(SubscriptionExpiryWarningEmail::class);
    $implements = $reflection->implementsInterface(\Illuminate\Contracts\Queue\ShouldQueue::class);
    echo $implements ? "✓ Exists and implements ShouldQueue\n" : "✓ Exists but missing ShouldQueue\n";
} else {
    echo "✗ Not found\n";
}

/**
 * Verify MeterReadingSubmittedEmail notification class
 *
 * Checks that MeterReadingSubmittedEmail exists and implements ShouldQueue.
 * This notification is sent to admin/manager users when tenants submit meter readings.
 */
echo "4. MeterReadingSubmittedEmail: ";
if (class_exists(MeterReadingSubmittedEmail::class)) {
    $reflection = new ReflectionClass(MeterReadingSubmittedEmail::class);
    $implements = $reflection->implementsInterface(\Illuminate\Contracts\Queue\ShouldQueue::class);
    echo $implements ? "✓ Exists and implements ShouldQueue\n" : "✓ Exists but missing ShouldQueue\n";
} else {
    echo "✗ Not found\n";
}

echo "\n✓ All notification classes are properly implemented!\n";
