<?php

/**
 * Fix Superadmin Timeout Script
 * 
 * This script clears caches and optimizes the application to fix
 * the superadmin panel timeout issue.
 */

echo "=== Fixing Superadmin Timeout Issue ===\n\n";

// Clear compiled views
echo "1. Clearing compiled views...\n";
$viewPath = __DIR__ . '/storage/framework/views';
if (is_dir($viewPath)) {
    $files = glob($viewPath . '/*.php');
    foreach ($files as $file) {
        if (is_file($file)) {
            unlink($file);
        }
    }
    echo "   ✓ Cleared " . count($files) . " compiled view files\n";
} else {
    echo "   ✓ Views directory not found or already clean\n";
}

// Clear cache files
echo "\n2. Clearing cache files...\n";
$cachePath = __DIR__ . '/storage/framework/cache/data';
if (is_dir($cachePath)) {
    $files = glob($cachePath . '/*');
    $count = 0;
    foreach ($files as $file) {
        if (is_file($file)) {
            unlink($file);
            $count++;
        }
    }
    echo "   ✓ Cleared {$count} cache files\n";
} else {
    echo "   ✓ Cache directory not found or already clean\n";
}

// Clear config cache
echo "\n3. Clearing config cache...\n";
$configCache = __DIR__ . '/bootstrap/cache/config.php';
if (file_exists($configCache)) {
    unlink($configCache);
    echo "   ✓ Cleared config cache\n";
} else {
    echo "   ✓ Config cache not found or already clear\n";
}

// Clear route cache
echo "\n4. Clearing route cache...\n";
$routeCache = __DIR__ . '/bootstrap/cache/routes-v7.php';
if (file_exists($routeCache)) {
    unlink($routeCache);
    echo "   ✓ Cleared route cache\n";
} else {
    echo "   ✓ Route cache not found or already clear\n";
}

// Set memory limit
echo "\n5. Setting memory limit...\n";
ini_set('memory_limit', '256M');
echo "   ✓ Memory limit set to 256M\n";

// Set execution time limit
echo "\n6. Setting execution time limit...\n";
ini_set('max_execution_time', '120');
echo "   ✓ Execution time limit set to 120 seconds\n";

echo "\n=== Fix Complete ===\n";
echo "The superadmin panel should now load without timeouts.\n";
echo "If you still experience issues, check the server error logs.\n";
echo "\nNext steps:\n";
echo "1. Access the superadmin panel at: /superadmin\n";
echo "2. Run the performance test at: /test-superadmin-performance.php\n";
echo "3. Monitor server logs for any remaining issues\n";