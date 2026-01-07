<?php

declare(strict_types=1);

// List of dangerous test files to delete from root directory
$dangerousFiles = [
    // Test files that caused the original crash
    'test-final-verification-fixed.php',
    'test-comprehensive-verification.php',
    
    // Other dangerous test files
    'test-auth-simple.php',
    'test-base-providers.php',
    'test-config-bootstrap.php',
    'test-critical-fix.php',
    'test-database.php',
    'test-filament-issue.php',
    'test-filament-manager.php',
    'test-final-verification.php',
    'test-fix.php',
    'test-foundation-provider.php',
    'test-laravel12-bootstrap.php',
    'test-laravel12-real-bootstrap.php',
    'test-load-configuration.php',
    'test-login.php',
    'test-php.php',
    'test-provider-boot.php',
    'test-simple.php',
    'test-translations-simple.php',
    'test-utilities-system.php',
    'test-validation-performance.php',
    'test-web-access.php',
    
    // Emergency/nuclear fix files
    'emergency-fix.php',
    'nuclear-fix.php',
    'nuclear-solution.php',
    'one-command-fix.php',
    'final-solution.php',
    'restore-laravel.php',
    'run_laravel.php',
    'fix-laravel-cache.php',
    
    // Bootstrap temp files
    'bootstrap_temp.php',
    'bootstrap_windows_fix.php',
    'laravel_windows_bootstrap.php',
    
    // Debug files
    'invoice-debug.php',
    'check-db.php',
    
    // Verification files
    'verify-batch3-resources.php',
    'verify-batch4-resources.php',
    'verify-models.php',
    'verify-multi-tenancy.php',
    'verify-notifications.php',
    
    // Test factory files in root (should be in tests/)
    'test_factories.php',
    'test_service_registry_integration.php',
    
    // Weird files
    '$binding)',
    '$null',
    'getBindings())',
    'getMessage()',
    
    // Output files
    'test-dynamic-fields-output.txt',
    'test_property_resource_output.txt',
    'policy_test_output.txt',
    'test-results-baseline-full.txt',
    'test-results-baseline.txt',
    'filament_resources_tests.txt',
];

$deletedCount = 0;
$notFoundCount = 0;

echo "🧹 Starting cleanup of dangerous test files from root directory...\n\n";

foreach ($dangerousFiles as $file) {
    if (file_exists($file)) {
        if (unlink($file)) {
            echo "✅ Deleted: {$file}\n";
            $deletedCount++;
        } else {
            echo "❌ Failed to delete: {$file}\n";
        }
    } else {
        echo "ℹ️  Not found: {$file}\n";
        $notFoundCount++;
    }
}

echo "\n📊 Cleanup Summary:\n";
echo "✅ Files deleted: {$deletedCount}\n";
echo "ℹ️  Files not found: {$notFoundCount}\n";
echo "🎉 Root directory sanitized!\n";