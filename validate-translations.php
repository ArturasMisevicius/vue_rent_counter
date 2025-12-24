<?php

// Simple translation validation script without PsySH dependencies
require_once __DIR__.'/vendor/autoload.php';

try {
    // Bootstrap Laravel application
    $app = require_once __DIR__.'/bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();

    echo "=== Translation Validation ===\n";
    
    // Test locales
    $locales = ['en', 'lt'];
    
    // Test keys from both app.php and dashboard.php
    $testKeys = [
        // App.php keys
        'app.nav.dashboard',
        'app.brand.name',
        'app.navigation.integration_health',
        'app.impersonation.history',
        'app.goodbye',
        'app.special_chars',
        
        // Dashboard.php keys
        'dashboard.admin.title',
        'dashboard.utility_analytics',
        'dashboard.audit.overview',
        'dashboard.audit.trends_title',
        'dashboard.audit.compliance_status',
    ];
    
    foreach ($locales as $locale) {
        echo "\n--- Testing locale: $locale ---\n";
        app()->setLocale($locale);
        
        $missing = [];
        $found = [];
        
        foreach ($testKeys as $key) {
            $translation = __($key);
            if ($translation === $key) {
                $missing[] = $key;
                echo "✗ MISSING: $key\n";
            } else {
                $found[] = $key;
                echo "✓ EXISTS: $key = '$translation'\n";
            }
        }
        
        echo "\nSummary for $locale:\n";
        echo "Found: " . count($found) . " keys\n";
        echo "Missing: " . count($missing) . " keys\n";
        
        if (count($missing) > 0) {
            echo "Missing keys:\n";
            foreach ($missing as $key) {
                echo "  - $key\n";
            }
        }
    }
    
    echo "\n=== Validation Complete ===\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    exit(1);
}