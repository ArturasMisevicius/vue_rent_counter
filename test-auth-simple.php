<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

echo "Testing authentication flow...\n";

try {
    // Test 1: Load user
    echo "1. Loading user... ";
    $user = App\Models\User::where('email', 'kub.kory@example.org')->first();
    echo "✅ User loaded: {$user->email}\n";
    
    // Test 2: Attempt login
    echo "2. Attempting login... ";
    $credentials = [
        'email' => 'kub.kory@example.org',
        'password' => 'password'
    ];
    
    if (Auth::attempt($credentials)) {
        echo "✅ Login successful!\n";
        
        // Test 3: Check authenticated user
        echo "3. Checking authenticated user... ";
        $authUser = Auth::user();
        echo "✅ Authenticated as: {$authUser->email}\n";
        
        // Test 4: Logout
        echo "4. Logging out... ";
        Auth::logout();
        echo "✅ Logged out\n";
    } else {
        echo "❌ Login failed\n";
    }
    
    echo "\n✅ All tests passed!\n";
    
} catch (\Throwable $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "\nStack trace:\n" . $e->getTraceAsString() . "\n";
}
