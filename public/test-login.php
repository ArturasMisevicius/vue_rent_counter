<?php

// Test login script
require_once __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "<h1>Login Test</h1>";

try {
    // Attempt to authenticate the superadmin user
    $credentials = [
        'email' => 'superadmin@example.com',
        'password' => 'password'
    ];
    
    echo "<h2>Testing Authentication</h2>";
    echo "<p><strong>Email:</strong> {$credentials['email']}</p>";
    echo "<p><strong>Password:</strong> {$credentials['password']}</p>";
    
    // Test if Auth::attempt works
    $authResult = \Illuminate\Support\Facades\Auth::attempt($credentials);
    echo "<p><strong>Auth::attempt result:</strong> " . ($authResult ? 'SUCCESS' : 'FAILED') . "</p>";
    
    if ($authResult) {
        $user = \Illuminate\Support\Facades\Auth::user();
        echo "<p><strong>Authenticated user:</strong> {$user->name} ({$user->email})</p>";
        echo "<p><strong>User role:</strong> {$user->role->value}</p>";
        
        // Test middleware logic
        $middleware = new \App\Http\Middleware\EnsureUserIsSuperadmin();
        echo "<p><strong>User is superadmin:</strong> " . ($user->role === \App\Enums\UserRole::SUPERADMIN ? 'Yes' : 'No') . "</p>";
        
        // Test canAccessPanel
        $panel = \Filament\Facades\Filament::getPanel('superadmin');
        $canAccess = $user->canAccessPanel($panel);
        echo "<p><strong>Can access superadmin panel:</strong> " . ($canAccess ? 'Yes' : 'No') . "</p>";
        
        \Illuminate\Support\Facades\Auth::logout();
    }
    
    // Test direct user lookup and password verification
    echo "<h2>Direct Password Test</h2>";
    $user = \App\Models\User::where('email', 'superadmin@example.com')->first();
    if ($user) {
        $passwordCheck = \Illuminate\Support\Facades\Hash::check('password', $user->password);
        echo "<p><strong>Password hash check:</strong> " . ($passwordCheck ? 'PASS' : 'FAIL') . "</p>";
        echo "<p><strong>User active:</strong> " . ($user->is_active ? 'Yes' : 'No') . "</p>";
        echo "<p><strong>Email verified:</strong> " . ($user->email_verified_at ? 'Yes' : 'No') . "</p>";
    }
    
} catch (\Exception $e) {
    echo "<h2>❌ Error</h2>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
    echo "<p>File: " . $e->getFile() . ":" . $e->getLine() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<hr>";
echo "<p><em>Login test completed at " . date('Y-m-d H:i:s') . "</em></p>";
echo "<p><a href='/superadmin/login'>Try Superadmin Login</a></p>";