<?php

declare(strict_types=1);

/**
 * Superadmin Rescue Script
 * 
 * This script forcefully updates the tenant3@test.com user to have superadmin privileges.
 * It bypasses all normal security checks to fix the 403 Forbidden error.
 * 
 * SECURITY WARNING: This script should only be used in development/testing environments.
 * Remove this file after use to prevent security vulnerabilities.
 */

require_once __DIR__ . '/vendor/autoload.php';

use App\Models\User;
use App\Enums\UserRole;
use Illuminate\Support\Facades\DB;

// Bootstrap Laravel application
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== SUPERADMIN RESCUE SCRIPT ===" . PHP_EOL;
echo "Fixing superadmin access for tenant3@test.com" . PHP_EOL;
echo "----------------------------------------" . PHP_EOL;

try {
    // Find the user
    $user = User::where('email', 'tenant3@test.com')->first();
    
    if (!$user) {
        echo "❌ ERROR: User tenant3@test.com not found!" . PHP_EOL;
        exit(1);
    }
    
    echo "✅ User found: {$user->name} ({$user->email})" . PHP_EOL;
    echo "Current role: {$user->role->value}" . PHP_EOL;
    echo "Current is_super_admin: " . ($user->is_super_admin ? 'true' : 'false') . PHP_EOL;
    echo "Current is_active: " . ($user->is_active ? 'true' : 'false') . PHP_EOL;
    echo "Current suspended_at: " . ($user->suspended_at ?? 'null') . PHP_EOL;
    echo PHP_EOL;
    
    // Force update to superadmin
    echo "🔧 Updating user to superadmin..." . PHP_EOL;
    
    DB::transaction(function () use ($user) {
        $user->update([
            'role' => UserRole::SUPERADMIN,
            'is_super_admin' => true,
            'is_active' => true,
            'suspended_at' => null,
            'suspension_reason' => null,
            'tenant_id' => null, // Superadmins have no tenant scope
            'property_id' => null, // Superadmins are not assigned to properties
            'parent_user_id' => null, // Superadmins have no parent
        ]);
        
        // Clear any cached data
        $user->clearCache();
    });
    
    // Refresh the user model
    $user->refresh();
    
    echo "✅ User successfully updated!" . PHP_EOL;
    echo "New role: {$user->role->value}" . PHP_EOL;
    echo "New is_super_admin: " . ($user->is_super_admin ? 'true' : 'false') . PHP_EOL;
    echo "New is_active: " . ($user->is_active ? 'true' : 'false') . PHP_EOL;
    echo "New tenant_id: " . ($user->tenant_id ?? 'null') . PHP_EOL;
    echo PHP_EOL;
    
    // Test panel access
    echo "🧪 Testing panel access..." . PHP_EOL;
    
    // Test the services directly instead of creating a mock panel
    $panelService = app(\App\Services\PanelAccessService::class);
    $serviceResult = $panelService->canAccessSuperadminPanel($user);
    echo "PanelAccessService->canAccessSuperadminPanel(): " . ($serviceResult ? 'true' : 'false') . PHP_EOL;
    
    $roleService = app(\App\Services\UserRoleService::class);
    $isSuperadmin = $roleService->isSuperadmin($user);
    echo "UserRoleService->isSuperadmin(): " . ($isSuperadmin ? 'true' : 'false') . PHP_EOL;
    
    if ($serviceResult && $isSuperadmin) {
        echo "✅ SUCCESS: User can now access the superadmin panel!" . PHP_EOL;
    } else {
        echo "❌ FAILED: User still cannot access the superadmin panel!" . PHP_EOL;
        
        // Additional debugging
        echo PHP_EOL . "🔍 Debugging information:" . PHP_EOL;
        echo "User role enum: " . get_class($user->role) . PHP_EOL;
        echo "UserRole::SUPERADMIN: " . UserRole::SUPERADMIN->value . PHP_EOL;
        echo "Role comparison: " . ($user->role === UserRole::SUPERADMIN ? 'true' : 'false') . PHP_EOL;
    }
    
    echo PHP_EOL . "=== RESCUE SCRIPT COMPLETED ===" . PHP_EOL;
    echo "You can now try logging in at: /superadmin/dashboard" . PHP_EOL;
    echo "Email: tenant3@test.com" . PHP_EOL;
    echo "Password: password" . PHP_EOL;
    
} catch (\Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . PHP_EOL;
    echo "Stack trace:" . PHP_EOL;
    echo $e->getTraceAsString() . PHP_EOL;
    exit(1);
}