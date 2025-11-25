<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Artisan;

beforeEach(function () {
    // Ensure tables exist
    if (!Schema::hasTable('meter_readings')) {
        Artisan::call('migrate', ['--path' => 'database/migrations/0001_01_01_000009_create_meter_readings_table.php']);
    }
    if (!Schema::hasTable('meters')) {
        Artisan::call('migrate', ['--path' => 'database/migrations/0001_01_01_000008_create_meters_table.php']);
    }
    if (!Schema::hasTable('providers')) {
        Artisan::call('migrate', ['--path' => 'database/migrations/0001_01_01_000006_create_providers_table.php']);
    }
});

test('billing service performance indexes migration creates all required indexes', function () {
    // Run the migration
    Artisan::call('migrate', ['--path' => 'database/migrations/2025_11_25_060200_add_billing_service_performance_indexes.php']);
    
    $connection = Schema::getConnection();
    
    // Check meter_readings indexes
    $meterReadingsIndexes = $connection->getDoctrineSchemaManager()->listTableIndexes('meter_readings');
    expect(isset($meterReadingsIndexes['meter_readings_meter_date_zone_index']))->toBeTrue()
        ->and(isset($meterReadingsIndexes['meter_readings_reading_date_index']))->toBeTrue();
    
    // Check meters indexes
    $metersIndexes = $connection->getDoctrineSchemaManager()->listTableIndexes('meters');
    expect(isset($metersIndexes['meters_property_type_index']))->toBeTrue();
    
    // Check providers indexes
    $providersIndexes = $connection->getDoctrineSchemaManager()->listTableIndexes('providers');
    expect(isset($providersIndexes['providers_service_type_index']))->toBeTrue();
});

test('billing service performance indexes migration is idempotent', function () {
    // Run migration twice
    Artisan::call('migrate', ['--path' => 'database/migrations/2025_11_25_060200_add_billing_service_performance_indexes.php']);
    Artisan::call('migrate', ['--path' => 'database/migrations/2025_11_25_060200_add_billing_service_performance_indexes.php']);
    
    $connection = Schema::getConnection();
    
    // Verify indexes still exist and no duplicates
    $meterReadingsIndexes = $connection->getDoctrineSchemaManager()->listTableIndexes('meter_readings');
    expect(isset($meterReadingsIndexes['meter_readings_meter_date_zone_index']))->toBeTrue();
});

test('billing service performance indexes migration rollback removes all indexes', function () {
    // Run migration
    Artisan::call('migrate', ['--path' => 'database/migrations/2025_11_25_060200_add_billing_service_performance_indexes.php']);
    
    // Rollback
    Artisan::call('migrate:rollback', ['--path' => 'database/migrations/2025_11_25_060200_add_billing_service_performance_indexes.php']);
    
    $connection = Schema::getConnection();
    
    // Check indexes are removed
    $meterReadingsIndexes = $connection->getDoctrineSchemaManager()->listTableIndexes('meter_readings');
    expect(isset($meterReadingsIndexes['meter_readings_meter_date_zone_index']))->toBeFalse()
        ->and(isset($meterReadingsIndexes['meter_readings_reading_date_index']))->toBeFalse();
    
    $metersIndexes = $connection->getDoctrineSchemaManager()->listTableIndexes('meters');
    expect(isset($metersIndexes['meters_property_type_index']))->toBeFalse();
    
    $providersIndexes = $connection->getDoctrineSchemaManager()->listTableIndexes('providers');
    expect(isset($providersIndexes['providers_service_type_index']))->toBeFalse();
});

test('meter_readings_meter_date_zone_index covers correct columns', function () {
    Artisan::call('migrate', ['--path' => 'database/migrations/2025_11_25_060200_add_billing_service_performance_indexes.php']);
    
    $connection = Schema::getConnection();
    $indexes = $connection->getDoctrineSchemaManager()->listTableIndexes('meter_readings');
    $index = $indexes['meter_readings_meter_date_zone_index'];
    
    $columns = $index->getColumns();
    expect($columns)->toContain('meter_id')
        ->and($columns)->toContain('reading_date')
        ->and($columns)->toContain('zone');
});

test('meters_property_type_index covers correct columns', function () {
    Artisan::call('migrate', ['--path' => 'database/migrations/2025_11_25_060200_add_billing_service_performance_indexes.php']);
    
    $connection = Schema::getConnection();
    $indexes = $connection->getDoctrineSchemaManager()->listTableIndexes('meters');
    $index = $indexes['meters_property_type_index'];
    
    $columns = $index->getColumns();
    expect($columns)->toContain('property_id')
        ->and($columns)->toContain('type');
});
