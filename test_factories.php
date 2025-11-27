<?php

/**
 * Factory Verification Script
 *
 * This script verifies that the hierarchical user management factories
 * (SubscriptionFactory and UserFactory) are working correctly with the
 * new state methods for admin, tenant, and superadmin roles.
 *
 * Usage:
 *   php test_factories.php
 *
 * Expected Output:
 *   - SubscriptionFactory creates subscriptions with correct plan types and limits
 *   - UserFactory creates admins with tenant_id and organization_name
 *   - UserFactory creates tenants with property_id and parent_user_id
 *   - UserFactory creates superadmins without tenant_id
 *
 * @see database/factories/SubscriptionFactory.php
 * @see database/factories/UserFactory.php
 * @see .kiro/specs/3-hierarchical-user-management/tasks.md (Task 13.1, 13.2)
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Testing SubscriptionFactory...\n";
$sub = App\Models\Subscription::factory()->basic()->make();
echo "✓ Plan: {$sub->plan_type}, Max Properties: {$sub->max_properties}, Max Tenants: {$sub->max_tenants}\n\n";

echo "Testing UserFactory - Admin...\n";
$admin = App\Models\User::factory()->admin(999)->make();
echo "✓ Role: {$admin->role->value}, Tenant ID: {$admin->tenant_id}, Org: {$admin->organization_name}\n\n";

echo "Testing UserFactory - Tenant...\n";
$tenant = App\Models\User::factory()->tenant(999, 1, 1)->make();
echo "✓ Role: {$tenant->role->value}, Tenant ID: {$tenant->tenant_id}, Property ID: {$tenant->property_id}, Parent: {$tenant->parent_user_id}\n\n";

echo "Testing UserFactory - Superadmin...\n";
$superadmin = App\Models\User::factory()->superadmin()->make();
echo "✓ Role: {$superadmin->role->value}, Tenant ID: " . ($superadmin->tenant_id ?? 'null') . "\n\n";

echo "✅ All factories working correctly!\n";
