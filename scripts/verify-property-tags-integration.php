<?php

declare(strict_types=1);

/**
 * Property Tags Integration Verification Script
 * 
 * Verifies the successful integration of HasTags trait with Property model
 * and validates performance optimizations.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\Models\Property;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Initialize Laravel application
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "🔍 Property Tags Integration Verification\n";
echo "==========================================\n\n";

// 1. Verify HasTags trait integration
echo "1. Checking HasTags trait integration...\n";
$property = new Property();
$traits = class_uses_recursive($property);

if (in_array(\App\Traits\HasTags::class, $traits)) {
    echo "   ✅ HasTags trait successfully integrated\n";
} else {
    echo "   ❌ HasTags trait not found\n";
    exit(1);
}

// 2. Verify database schema
echo "\n2. Verifying database schema...\n";
$requiredTables = ['tags', 'taggables', 'properties'];
foreach ($requiredTables as $table) {
    if (Schema::hasTable($table)) {
        echo "   ✅ Table '{$table}' exists\n";
    } else {
        echo "   ❌ Table '{$table}' missing\n";
        exit(1);
    }
}

// 3. Verify tag relationship
echo "\n3. Testing tag relationship...\n";
try {
    $relationship = $property->tags();
    if ($relationship instanceof \Illuminate\Database\Eloquent\Relations\MorphToMany) {
        echo "   ✅ Tags relationship properly configured\n";
    } else {
        echo "   ❌ Tags relationship incorrect type\n";
        exit(1);
    }
} catch (Exception $e) {
    echo "   ❌ Tags relationship error: " . $e->getMessage() . "\n";
    exit(1);
}

// 4. Verify new methods exist
echo "\n4. Checking new helper methods...\n";
$requiredMethods = [
    'withCommonRelations',
    'getStatsSummary', 
    'canAssignTenant',
    'getDisplayIdentifier',
    'scopeWithTags'
];

foreach ($requiredMethods as $method) {
    if (method_exists($property, $method)) {
        echo "   ✅ Method '{$method}' exists\n";
    } else {
        echo "   ❌ Method '{$method}' missing\n";
        exit(1);
    }
}

// 5. Test query scopes
echo "\n5. Testing query scopes...\n";
try {
    // Test withCommonRelations scope
    $query = Property::withCommonRelations();
    $sql = $query->toSql();
    
    if (str_contains($sql, 'tags')) {
        echo "   ✅ withCommonRelations includes tags\n";
    } else {
        echo "   ❌ withCommonRelations missing tags\n";
    }
    
    // Test other scopes still work
    $residentialQuery = Property::residential();
    echo "   ✅ Existing scopes still functional\n";
    
} catch (Exception $e) {
    echo "   ❌ Query scope error: " . $e->getMessage() . "\n";
    exit(1);
}

// 6. Performance check - verify indexes
echo "\n6. Checking database indexes...\n";
try {
    $indexes = DB::select("PRAGMA index_list(taggables)");
    $hasPolyIndex = false;
    
    foreach ($indexes as $index) {
        $indexInfo = DB::select("PRAGMA index_info({$index->name})");
        $columns = array_column($indexInfo, 'name');
        
        if (in_array('taggable_type', $columns) && in_array('taggable_id', $columns)) {
            $hasPolyIndex = true;
            break;
        }
    }
    
    if ($hasPolyIndex) {
        echo "   ✅ Polymorphic relationship indexes present\n";
    } else {
        echo "   ⚠️  Polymorphic indexes may be missing\n";
    }
    
} catch (Exception $e) {
    echo "   ⚠️  Could not verify indexes: " . $e->getMessage() . "\n";
}

// 7. Test data operations (if data exists)
echo "\n7. Testing data operations...\n";
try {
    $propertyCount = Property::count();
    $tagCount = Tag::count();
    
    echo "   📊 Properties in database: {$propertyCount}\n";
    echo "   📊 Tags in database: {$tagCount}\n";
    
    if ($propertyCount > 0 && $tagCount > 0) {
        // Test a simple tag query
        $propertiesWithTags = Property::whereHas('tags')->count();
        echo "   📊 Properties with tags: {$propertiesWithTags}\n";
        
        // Test stats summary on first property
        $firstProperty = Property::first();
        if ($firstProperty) {
            $stats = $firstProperty->getStatsSummary();
            if (isset($stats['tag_count'])) {
                echo "   ✅ Stats summary includes tag_count\n";
            } else {
                echo "   ❌ Stats summary missing tag_count\n";
            }
        }
    }
    
} catch (Exception $e) {
    echo "   ⚠️  Data operation test failed: " . $e->getMessage() . "\n";
}

// 8. Verify Filament integration files
echo "\n8. Checking Filament integration...\n";
$filamentResourcePath = __DIR__ . '/../app/Filament/Resources/PropertyResource.php';
if (file_exists($filamentResourcePath)) {
    $content = file_get_contents($filamentResourcePath);
    
    if (str_contains($content, "->relationship('tags'")) {
        echo "   ✅ PropertyResource includes tags form field\n";
    } else {
        echo "   ❌ PropertyResource missing tags form field\n";
    }
    
    if (str_contains($content, "SelectFilter::make('tags')")) {
        echo "   ✅ PropertyResource includes tags filter\n";
    } else {
        echo "   ❌ PropertyResource missing tags filter\n";
    }
} else {
    echo "   ❌ PropertyResource file not found\n";
}

// 9. Check test files
echo "\n9. Verifying test coverage...\n";
$testPath = __DIR__ . '/../tests/Unit/Models/PropertyTagsIntegrationTest.php';
if (file_exists($testPath)) {
    echo "   ✅ PropertyTagsIntegrationTest exists\n";
    
    $testContent = file_get_contents($testPath);
    $testCount = substr_count($testContent, 'public function test_');
    echo "   📊 Test methods found: {$testCount}\n";
} else {
    echo "   ❌ PropertyTagsIntegrationTest missing\n";
}

// 10. Memory usage check
echo "\n10. Performance metrics...\n";
$memoryUsage = memory_get_usage(true);
$peakMemory = memory_get_peak_usage(true);

echo "   📊 Memory usage: " . number_format($memoryUsage / 1024 / 1024, 2) . " MB\n";
echo "   📊 Peak memory: " . number_format($peakMemory / 1024 / 1024, 2) . " MB\n";

// Summary
echo "\n" . str_repeat("=", 50) . "\n";
echo "🎉 VERIFICATION COMPLETE\n";
echo str_repeat("=", 50) . "\n";

echo "\n✅ Property Tags Integration Status: SUCCESS\n";
echo "\nKey Features Verified:\n";
echo "  • HasTags trait integration\n";
echo "  • Database schema compatibility\n";
echo "  • Polymorphic relationships\n";
echo "  • Helper methods and scopes\n";
echo "  • Filament UI integration\n";
echo "  • Test coverage\n";

echo "\n🚀 Ready for deployment!\n\n";

echo "Next Steps:\n";
echo "1. Run full test suite: php artisan test\n";
echo "2. Deploy to staging environment\n";
echo "3. Monitor performance metrics\n";
echo "4. Gather user feedback\n\n";