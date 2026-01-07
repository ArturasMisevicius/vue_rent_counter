<?php

declare(strict_types=1);

// Bootstrap Laravel
require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Enums\UserRole;

try {
    // Find the latest created user
    $user = User::latest()->first();
    
    if (!$user) {
        echo "❌ No users found in database\n";
        exit(1);
    }
    
    echo "🔍 Found latest user: {$user->name} ({$user->email}) - ID: {$user->id}\n";
    echo "📊 Current role: " . ($user->role ? $user->role->value : 'null') . "\n";
    
    // Update user to superadmin role
    $user->role = UserRole::SUPERADMIN;
    $user->is_super_admin = true;
    $user->tenant_id = null; // Superadmins have no tenant scope
    $user->is_active = true;
    $user->save();
    
    echo "✅ Updated user role to SUPERADMIN\n";
    echo "✅ Set is_super_admin = true\n";
    echo "✅ Cleared tenant_id (superadmin has global access)\n";
    echo "✅ Ensured user is active\n";
    
    echo "🎉 Super admin access granted successfully!\n";
    echo "🔗 User can now access /admin panel with full privileges\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "📍 File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    exit(1);
}