<?php

// Diagnostic script for authentication debugging
require_once __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "<h1>Authentication Debug</h1>";

try {
    // Check if superadmin user exists
    $superadmin = \App\Models\User::where('email', 'superadmin@example.com')->first();
    
    if ($superadmin) {
        echo "<h2>✅ Superadmin User Found</h2>";
        echo "<p><strong>ID:</strong> {$superadmin->id}</p>";
        echo "<p><strong>Name:</strong> {$superadmin->name}</p>";
        echo "<p><strong>Email:</strong> {$superadmin->email}</p>";
        echo "<p><strong>Role:</strong> {$superadmin->role->value}</p>";
        echo "<p><strong>Active:</strong> " . ($superadmin->is_active ? 'Yes' : 'No') . "</p>";
        echo "<p><strong>Email Verified:</strong> " . ($superadmin->email_verified_at ? 'Yes' : 'No') . "</p>";
        
        // Test password
        $testPassword = 'password';
        $passwordMatch = \Illuminate\Support\Facades\Hash::check($testPassword, $superadmin->password);
        echo "<p><strong>Password 'password' works:</strong> " . ($passwordMatch ? 'Yes' : 'No') . "</p>";
        
        // Check role enum
        echo "<p><strong>Role matches SUPERADMIN:</strong> " . ($superadmin->role === \App\Enums\UserRole::SUPERADMIN ? 'Yes' : 'No') . "</p>";
        
    } else {
        echo "<h2>❌ Superadmin User NOT Found</h2>";
    }
    
    // List all users
    echo "<h2>All Users in Database</h2>";
    $users = \App\Models\User::all(['id', 'name', 'email', 'role', 'is_active']);
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>Role</th><th>Active</th></tr>";
    foreach ($users as $user) {
        echo "<tr>";
        echo "<td>{$user->id}</td>";
        echo "<td>{$user->name}</td>";
        echo "<td>{$user->email}</td>";
        echo "<td>{$user->role->value}</td>";
        echo "<td>" . ($user->is_active ? 'Yes' : 'No') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Check routes
    echo "<h2>Route Information</h2>";
    echo "<p><strong>Superadmin Panel URL:</strong> <a href='/superadmin'>/superadmin</a></p>";
    echo "<p><strong>Superadmin Login URL:</strong> <a href='/superadmin/login'>/superadmin/login</a></p>";
    
    // Check middleware
    echo "<h2>Middleware Check</h2>";
    $middleware = \App\Http\Middleware\EnsureUserIsSuperadmin::class;
    echo "<p><strong>Middleware exists:</strong> " . (class_exists($middleware) ? 'Yes' : 'No') . "</p>";
    
} catch (\Exception $e) {
    echo "<h2>❌ Error</h2>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
    echo "<p>File: " . $e->getFile() . ":" . $e->getLine() . "</p>";
}

echo "<hr>";
echo "<p><em>Debug script completed at " . date('Y-m-d H:i:s') . "</em></p>";