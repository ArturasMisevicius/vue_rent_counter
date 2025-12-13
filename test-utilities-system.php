<?php

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\DB;

// Bootstrap Laravel
$app = new Application(__DIR__);
$app->singleton(
    Illuminate\Contracts\Http\Kernel::class,
    App\Http\Kernel::class
);
$app->singleton(
    Illuminate\Contracts\Console\Kernel::class,
    App\Console\Kernel::class
);
$app->singleton(
    Illuminate\Contracts\Debug\ExceptionHandler::class,
    App\Exceptions\Handler::class
);

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== Utilities System Test ===\n\n";

// Test 1: Check database connection and path
echo "1. Database Configuration:\n";
echo "   Connection: " . config('database.default') . "\n";
echo "   Database Path: " . config('database.connections.sqlite.database') . "\n";
echo "   File Exists: " . (file_exists(config('database.connections.sqlite.database')) ? 'Yes' : 'No') . "\n\n";

// Test 2: Check if utilities tables exist
echo "2. Utilities Tables Check:\n";
$tables = ['buildings', 'properties', 'tenants', 'meters', 'meter_readings', 'invoices', 'invoice_items', 'providers', 'tariffs'];
foreach ($tables as $table) {
    try {
        $exists = DB::getSchemaBuilder()->hasTable($table);
        echo "   {$table}: " . ($exists ? 'EXISTS' : 'MISSING') . "\n";
    } catch (Exception $e) {
        echo "   {$table}: ERROR - " . $e->getMessage() . "\n";
    }
}

// Test 3: Check if models can be loaded
echo "\n3. Model Loading Test:\n";
$models = [
    'Building' => App\Models\Building::class,
    'Property' => App\Models\Property::class,
    'Tenant' => App\Models\Tenant::class,
    'Meter' => App\Models\Meter::class,
    'MeterReading' => App\Models\MeterReading::class,
    'Invoice' => App\Models\Invoice::class,
    'Provider' => App\Models\Provider::class,
    'Tariff' => App\Models\Tariff::class,
];

foreach ($models as $name => $class) {
    try {
        if (class_exists($class)) {
            $count = $class::count();
            echo "   {$name}: LOADED (count: {$count})\n";
        } else {
            echo "   {$name}: CLASS NOT FOUND\n";
        }
    } catch (Exception $e) {
        echo "   {$name}: ERROR - " . $e->getMessage() . "\n";
    }
}

// Test 4: Check Filament resources
echo "\n4. Filament Resources Check:\n";
$resources = [
    'BuildingResource' => App\Filament\Resources\BuildingResource::class,
    'PropertyResource' => App\Filament\Resources\PropertyResource::class,
    'TenantResource' => App\Filament\Resources\TenantResource::class,
    'MeterResource' => App\Filament\Resources\MeterResource::class,
    'InvoiceResource' => App\Filament\Resources\InvoiceResource::class,
];

foreach ($resources as $name => $class) {
    try {
        if (class_exists($class)) {
            echo "   {$name}: EXISTS\n";
        } else {
            echo "   {$name}: NOT FOUND\n";
        }
    } catch (Exception $e) {
        echo "   {$name}: ERROR - " . $e->getMessage() . "\n";
    }
}

echo "\n=== Test Complete ===\n";