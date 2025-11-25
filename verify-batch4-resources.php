<?php

/**
 * Batch 4 Filament Resources Verification Script
 * 
 * This script verifies that Batch 4 Filament resources (FaqResource, LanguageResource,
 * and TranslationResource) are properly configured for Filament 4 API compliance.
 * 
 * @package VilniusBilling
 * @category Testing
 * @version 1.0.0
 * @since Laravel 12.x, Filament 4.x
 * 
 * Usage:
 *   php verify-batch4-resources.php
 * 
 * Exit Codes:
 *   0 - All resources verified successfully
 *   1 - One or more resources have issues
 * 
 * Verification Checks:
 *   - Class existence and inheritance
 *   - Model configuration
 *   - Navigation icon setup
 *   - Page registration
 *   - Form and table method presence
 *   - Filament 4 Schema API usage
 *   - Action namespace usage (Tables\Actions\)
 * 
 * Related Documentation:
 *   - docs/upgrades/BATCH_4_RESOURCES_MIGRATION.md
 *   - docs/upgrades/LARAVEL_12_FILAMENT_4_UPGRADE.md
 *   - .kiro/specs/1-framework-upgrade/tasks.md
 * 
 * @see \Filament\Resources\Resource
 * @see \Filament\Schemas\Schema
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Verifying Batch 4 Filament Resources (Content & Localization)...\n\n";

/**
 * Resources to verify
 * 
 * @var array<string, class-string<\Filament\Resources\Resource>>
 */
$resources = [
    'FaqResource' => \App\Filament\Resources\FaqResource::class,
    'LanguageResource' => \App\Filament\Resources\LanguageResource::class,
    'TranslationResource' => \App\Filament\Resources\TranslationResource::class,
];

$passed = 0;
$failed = 0;

foreach ($resources as $name => $class) {
    echo "Testing {$name}...\n";
    
    try {
        // Check class exists
        if (!class_exists($class)) {
            throw new Exception("Class does not exist");
        }
        
        // Check it extends Resource
        if (!is_subclass_of($class, \Filament\Resources\Resource::class)) {
            throw new Exception("Does not extend Filament\Resources\Resource");
        }
        
        // Check model is set
        $model = $class::getModel();
        if (empty($model)) {
            throw new Exception("Model not set");
        }
        
        // Check navigation icon
        $icon = $class::getNavigationIcon();
        if (empty($icon)) {
            throw new Exception("Navigation icon not set");
        }
        
        // Check pages are registered
        $pages = $class::getPages();
        if (empty($pages)) {
            throw new Exception("No pages registered");
        }
        
        // Check form method exists
        if (!method_exists($class, 'form')) {
            throw new Exception("form() method not found");
        }
        
        // Check table method exists
        if (!method_exists($class, 'table')) {
            throw new Exception("table() method not found");
        }
        
        echo "  ✓ Class structure: OK\n";
        echo "  ✓ Model: {$model}\n";
        echo "  ✓ Icon: {$icon}\n";
        echo "  ✓ Pages: " . count($pages) . " registered\n";
        
        // Check for Filament 4 specific patterns
        $reflection = new ReflectionMethod($class, 'form');
        $parameters = $reflection->getParameters();
        
        if (count($parameters) > 0) {
            $firstParam = $parameters[0];
            $paramType = $firstParam->getType();
            
            if ($paramType && $paramType->getName() === 'Filament\Schemas\Schema') {
                echo "  ✓ Using Filament 4 Schema API\n";
            } else {
                echo "  ⚠ Warning: Not using Filament\Schemas\Schema parameter\n";
            }
        }
        
        // Check for proper action namespace usage
        $resourceFile = (new ReflectionClass($class))->getFileName();
        $resourceContent = file_get_contents($resourceFile);
        
        // Check that we're using Tables\Actions\ prefix instead of individual imports
        if (strpos($resourceContent, 'Tables\Actions\EditAction') !== false ||
            strpos($resourceContent, 'Tables\Actions\DeleteAction') !== false ||
            strpos($resourceContent, 'Tables\Actions\CreateAction') !== false) {
            echo "  ✓ Using proper Tables\Actions\ namespace\n";
        } else {
            echo "  ⚠ Warning: Not using Tables\Actions\ namespace prefix\n";
        }
        
        // Check that we're NOT using individual action imports
        if (strpos($resourceContent, 'use Filament\Tables\Actions\EditAction;') === false &&
            strpos($resourceContent, 'use Filament\Tables\Actions\DeleteAction;') === false &&
            strpos($resourceContent, 'use Filament\Tables\Actions\CreateAction;') === false) {
            echo "  ✓ Not using individual action imports (correct)\n";
        } else {
            throw new Exception("Still using individual action imports - should use Tables\Actions\ prefix");
        }
        
        echo "  ✓ {$name} is properly configured\n\n";
        $passed++;
        
    } catch (Exception $e) {
        echo "  ✗ Error: " . $e->getMessage() . "\n\n";
        $failed++;
    }
}

echo "\n";
echo "========================================\n";
echo "Results: {$passed} passed, {$failed} failed\n";
echo "========================================\n";

if ($failed === 0) {
    echo "\n✓ All Batch 4 resources are properly configured for Filament 4!\n";
    exit(0);
} else {
    echo "\n✗ Some resources have issues that need to be addressed.\n";
    exit(1);
}
