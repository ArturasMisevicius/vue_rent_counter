<?php

declare(strict_types=1);

use App\Models\Meter;
use App\Models\MeterReading;
use App\Models\Organization;
use App\Models\Property;
use App\Models\User;
use App\Services\UniversalReadingCollector;
use App\Enums\InputMethod;
use App\Enums\ValidationStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

/**
 * Property 12: Mobile Offline Synchronization
 * 
 * Property-based tests for mobile offline data collection and synchronization capabilities.
 * These tests verify that mobile meter reading collection maintains data integrity
 * during offline operations and successful synchronization without GPS tracking.
 */

it('offline readings always sync successfully when connection restored', function () {
    $collector = app(UniversalReadingCollector::class);
    
    // Property: All offline readings sync successfully when connection is restored
    for ($i = 0; $i < 50; $i++) {
        $tenant = Organization::factory()->create();
        $property = Property::factory()->create(['tenant_id' => $tenant->id]);
        $meter = Meter::factory()->create([
            'property_id' => $property->id,
            'tenant_id' => $tenant->id,
        ]);
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        
        // Simulate offline readings collection
        $offlineReadings = [];
        $readingCount = fake()->numberBetween(1, 5);
        
        for ($j = 0; $j < $readingCount; $j++) {
            $offlineReadings[] = [
                'meter_id' => $meter->id,
                'value' => fake()->randomFloat(2, 10, 1000),
                'reading_values' => json_encode([
                    'main' => fake()->randomFloat(2, 10, 1000),
                    'timestamp' => now()->subMinutes($j * 30)->toISOString(),
                ]),
                'input_method' => InputMethod::MANUAL->value,
                'validation_status' => ValidationStatus::PENDING->value,
                'entered_by' => $user->id,
                'reading_date' => now()->subMinutes($j * 30),
                'offline_id' => 'offline_' . uniqid(),
            ];
        }
        
        // Store offline readings in cache (simulating browser storage)
        $cacheKey = "offline_readings_{$user->id}";
        Cache::put($cacheKey, $offlineReadings, 3600);
        
        // Sync offline readings
        $syncResult = $collector->syncOfflineReadings($user->id);
        
        // Property assertions
        expect($syncResult['success'])->toBeTrue();
        expect($syncResult['synced_count'])->toBe($readingCount);
        expect($syncResult['failed_count'])->toBe(0);
        
        // Verify all readings were created
        $syncedReadings = MeterReading::where('meter_id', $meter->id)->get();
        expect($syncedReadings)->toHaveCount($readingCount);
        
        // Verify offline cache is cleared
        expect(Cache::has($cacheKey))->toBeFalse();
        
        // Clean up
        $meter->readings()->delete();
        $meter->delete();
        $property->delete();
        $user->delete();
        $tenant->delete();
    }
});

it('data integrity preserved during sync conflicts', function () {
    $collector = app(UniversalReadingCollector::class);
    
    // Property: When sync conflicts occur, data integrity is always preserved
    for ($i = 0; $i < 25; $i++) {
        $tenant = Organization::factory()->create();
        $property = Property::factory()->create(['tenant_id' => $tenant->id]);
        $meter = Meter::factory()->create([
            'property_id' => $property->id,
            'tenant_id' => $tenant->id,
        ]);
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        
        // Create an existing reading (simulating server-side reading)
        $existingReading = MeterReading::factory()->create([
            'meter_id' => $meter->id,
            'tenant_id' => $tenant->id,
            'entered_by' => $user->id,
            'reading_date' => now()->subHour(),
            'value' => 100.0,
        ]);
        
        // Create conflicting offline reading (same time period)
        $conflictingReading = [
            'meter_id' => $meter->id,
            'value' => 150.0, // Different value
            'reading_date' => $existingReading->reading_date,
            'input_method' => InputMethod::MANUAL->value,
            'entered_by' => $user->id,
            'offline_id' => 'conflict_' . uniqid(),
        ];
        
        // Store in offline cache
        $cacheKey = "offline_readings_{$user->id}";
        Cache::put($cacheKey, [$conflictingReading], 3600);
        
        // Sync with conflict resolution
        $syncResult = $collector->syncOfflineReadings($user->id);
        
        // Property: Conflict resolution preserves data integrity
        expect($syncResult['success'])->toBeTrue();
        expect($syncResult['conflicts_resolved'])->toBeGreaterThan(0);
        
        // Verify original reading is preserved and conflict is handled
        $readings = MeterReading::where('meter_id', $meter->id)
            ->orderBy('created_at')
            ->get();
        
        expect($readings)->toHaveCount(2); // Original + conflict resolution
        expect($readings->first()->value)->toBe(100.0); // Original preserved
        
        // Clean up
        $meter->readings()->delete();
        $meter->delete();
        $property->delete();
        $user->delete();
        $tenant->delete();
    }
});

it('photo readings always include valid metadata', function () {
    $collector = app(UniversalReadingCollector::class);
    Storage::fake('local');
    
    // Property: Photo readings always include complete metadata
    for ($i = 0; $i < 20; $i++) {
        $tenant = Organization::factory()->create();
        $property = Property::factory()->create(['tenant_id' => $tenant->id]);
        $meter = Meter::factory()->create([
            'property_id' => $property->id,
            'tenant_id' => $tenant->id,
        ]);
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        
        // Create fake photo file
        $photoPath = 'meter_photos/' . uniqid() . '.jpg';
        Storage::put($photoPath, 'fake photo content');
        
        $readingData = [
            'meter_id' => $meter->id,
            'value' => fake()->randomFloat(2, 10, 1000),
            'input_method' => InputMethod::PHOTO_OCR,
            'photo_path' => $photoPath,
            'entered_by' => $user->id,
            'reading_date' => now(),
            'reading_values' => json_encode([
                'ocr_confidence' => fake()->randomFloat(2, 0.8, 1.0),
                'photo_metadata' => [
                    'timestamp' => now()->toISOString(),
                    'device_info' => 'Test Device',
                    'resolution' => '1920x1080',
                ],
            ]),
        ];
        
        $result = $collector->collectReading($readingData);
        
        // Property: Photo readings must have complete metadata
        expect($result['success'])->toBeTrue();
        
        $reading = MeterReading::find($result['reading_id']);
        expect($reading->photo_path)->toBe($photoPath);
        expect($reading->input_method)->toBe(InputMethod::PHOTO_OCR);
        
        $readingValues = json_decode($reading->reading_values, true);
        expect($readingValues)->toHaveKey('ocr_confidence');
        expect($readingValues)->toHaveKey('photo_metadata');
        expect($readingValues['photo_metadata'])->toHaveKey('timestamp');
        
        // Verify photo file exists
        expect(Storage::exists($photoPath))->toBeTrue();
        
        // Clean up
        Storage::delete($photoPath);
        $reading->delete();
        $meter->delete();
        $property->delete();
        $user->delete();
        $tenant->delete();
    }
});

it('offline queue maintains chronological order', function () {
    $collector = app(UniversalReadingCollector::class);
    
    // Property: Offline readings maintain chronological order during sync
    for ($i = 0; $i < 15; $i++) {
        $tenant = Organization::factory()->create();
        $property = Property::factory()->create(['tenant_id' => $tenant->id]);
        $meter = Meter::factory()->create([
            'property_id' => $property->id,
            'tenant_id' => $tenant->id,
        ]);
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        
        // Create multiple offline readings with different timestamps
        $offlineReadings = [];
        $baseTime = now()->subHours(2);
        
        for ($j = 0; $j < 5; $j++) {
            $offlineReadings[] = [
                'meter_id' => $meter->id,
                'value' => 100 + ($j * 10), // Increasing values
                'reading_date' => $baseTime->copy()->addMinutes($j * 15),
                'input_method' => InputMethod::MANUAL->value,
                'entered_by' => $user->id,
                'offline_id' => 'order_' . $j,
            ];
        }
        
        // Shuffle the array to simulate out-of-order collection
        shuffle($offlineReadings);
        
        // Store in cache
        $cacheKey = "offline_readings_{$user->id}";
        Cache::put($cacheKey, $offlineReadings, 3600);
        
        // Sync readings
        $syncResult = $collector->syncOfflineReadings($user->id);
        
        // Property: Readings are stored in chronological order
        expect($syncResult['success'])->toBeTrue();
        
        $syncedReadings = MeterReading::where('meter_id', $meter->id)
            ->orderBy('reading_date')
            ->get();
        
        expect($syncedReadings)->toHaveCount(5);
        
        // Verify chronological order and increasing values
        for ($j = 0; $j < 4; $j++) {
            expect($syncedReadings[$j]->reading_date)
                ->toBeLessThan($syncedReadings[$j + 1]->reading_date);
            expect($syncedReadings[$j]->value)
                ->toBeLessThan($syncedReadings[$j + 1]->value);
        }
        
        // Clean up
        $meter->readings()->delete();
        $meter->delete();
        $property->delete();
        $user->delete();
        $tenant->delete();
    }
});

it('network failure recovery preserves all data', function () {
    $collector = app(UniversalReadingCollector::class);
    
    // Property: Network failures during sync preserve all data for retry
    for ($i = 0; $i < 10; $i++) {
        $tenant = Organization::factory()->create();
        $property = Property::factory()->create(['tenant_id' => $tenant->id]);
        $meter = Meter::factory()->create([
            'property_id' => $property->id,
            'tenant_id' => $tenant->id,
        ]);
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        
        // Create offline readings
        $offlineReadings = [];
        $readingCount = fake()->numberBetween(3, 8);
        
        for ($j = 0; $j < $readingCount; $j++) {
            $offlineReadings[] = [
                'meter_id' => $meter->id,
                'value' => fake()->randomFloat(2, 10, 1000),
                'reading_date' => now()->subMinutes($j * 10),
                'input_method' => InputMethod::MANUAL->value,
                'entered_by' => $user->id,
                'offline_id' => 'network_' . $j,
            ];
        }
        
        // Store in cache
        $cacheKey = "offline_readings_{$user->id}";
        Cache::put($cacheKey, $offlineReadings, 3600);
        
        // Simulate partial sync failure (sync some, fail others)
        $partialSyncCount = intval($readingCount / 2);
        
        // First sync attempt (partial success)
        $firstSyncResult = $collector->syncOfflineReadings($user->id, $partialSyncCount);
        
        // Property: Partial sync preserves remaining data
        expect($firstSyncResult['success'])->toBeTrue();
        expect($firstSyncResult['synced_count'])->toBe($partialSyncCount);
        
        // Verify remaining readings still in cache
        $remainingReadings = Cache::get($cacheKey, []);
        expect($remainingReadings)->toHaveCount($readingCount - $partialSyncCount);
        
        // Second sync attempt (complete remaining)
        $secondSyncResult = $collector->syncOfflineReadings($user->id);
        
        expect($secondSyncResult['success'])->toBeTrue();
        expect($secondSyncResult['synced_count'])->toBe($readingCount - $partialSyncCount);
        
        // Verify all readings are now synced
        $totalReadings = MeterReading::where('meter_id', $meter->id)->count();
        expect($totalReadings)->toBe($readingCount);
        
        // Verify cache is cleared
        expect(Cache::has($cacheKey))->toBeFalse();
        
        // Clean up
        $meter->readings()->delete();
        $meter->delete();
        $property->delete();
        $user->delete();
        $tenant->delete();
    }
});

it('validation status preserved during offline sync', function () {
    $collector = app(UniversalReadingCollector::class);
    
    // Property: Validation status is correctly preserved during offline synchronization
    for ($i = 0; $i < 20; $i++) {
        $tenant = Organization::factory()->create();
        $property = Property::factory()->create(['tenant_id' => $tenant->id]);
        $meter = Meter::factory()->create([
            'property_id' => $property->id,
            'tenant_id' => $tenant->id,
        ]);
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        
        // Create offline readings with different validation statuses
        $offlineReadings = [];
        $validationStatuses = [
            ValidationStatus::PENDING,
            ValidationStatus::VALIDATED,
            ValidationStatus::REQUIRES_REVIEW,
        ];
        
        foreach ($validationStatuses as $status) {
            $offlineReadings[] = [
                'meter_id' => $meter->id,
                'value' => fake()->randomFloat(2, 10, 1000),
                'reading_date' => now()->subMinutes(fake()->numberBetween(10, 60)),
                'input_method' => InputMethod::MANUAL->value,
                'validation_status' => $status->value,
                'entered_by' => $user->id,
                'offline_id' => 'validation_' . $status->value,
            ];
        }
        
        // Store in cache
        $cacheKey = "offline_readings_{$user->id}";
        Cache::put($cacheKey, $offlineReadings, 3600);
        
        // Sync readings
        $syncResult = $collector->syncOfflineReadings($user->id);
        
        // Property: All readings sync successfully with preserved validation status
        expect($syncResult['success'])->toBeTrue();
        expect($syncResult['synced_count'])->toBe(count($validationStatuses));
        
        // Verify validation statuses are preserved
        $syncedReadings = MeterReading::where('meter_id', $meter->id)->get();
        expect($syncedReadings)->toHaveCount(count($validationStatuses));
        
        foreach ($syncedReadings as $reading) {
            expect(in_array($reading->validation_status, $validationStatuses))->toBeTrue();
        }
        
        // Clean up
        $meter->readings()->delete();
        $meter->delete();
        $property->delete();
        $user->delete();
        $tenant->delete();
    }
});

it('multi-value readings sync correctly', function () {
    $collector = app(UniversalReadingCollector::class);
    
    // Property: Multi-value readings maintain structure during offline sync
    for ($i = 0; $i < 15; $i++) {
        $tenant = Organization::factory()->create();
        $property = Property::factory()->create(['tenant_id' => $tenant->id]);
        $meter = Meter::factory()->create([
            'property_id' => $property->id,
            'tenant_id' => $tenant->id,
            'supports_zones' => true,
            'reading_structure' => [
                'fields' => [
                    ['name' => 'day', 'type' => 'number', 'required' => true],
                    ['name' => 'night', 'type' => 'number', 'required' => true],
                ],
            ],
        ]);
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        
        // Create offline multi-value readings
        $offlineReadings = [];
        $readingCount = fake()->numberBetween(2, 4);
        
        for ($j = 0; $j < $readingCount; $j++) {
            $dayValue = fake()->randomFloat(2, 100, 500);
            $nightValue = fake()->randomFloat(2, 50, 200);
            
            $offlineReadings[] = [
                'meter_id' => $meter->id,
                'value' => $dayValue + $nightValue, // Total value
                'reading_values' => [
                    'day' => $dayValue,
                    'night' => $nightValue,
                ],
                'reading_date' => now()->subMinutes($j * 30),
                'input_method' => InputMethod::MANUAL->value,
                'entered_by' => $user->id,
                'offline_id' => 'multi_' . $j,
            ];
        }
        
        // Store in cache
        $cacheKey = "offline_readings_{$user->id}";
        Cache::put($cacheKey, $offlineReadings, 3600);
        
        // Sync readings
        $syncResult = $collector->syncOfflineReadings($user->id);
        
        // Property: Multi-value readings sync with preserved structure
        expect($syncResult['success'])->toBeTrue();
        expect($syncResult['synced_count'])->toBe($readingCount);
        
        // Verify multi-value structure is preserved
        $syncedReadings = MeterReading::where('meter_id', $meter->id)->get();
        expect($syncedReadings)->toHaveCount($readingCount);
        
        foreach ($syncedReadings as $reading) {
            expect($reading->isMultiValue())->toBeTrue();
            expect($reading->reading_values)->toHaveKey('day');
            expect($reading->reading_values)->toHaveKey('night');
            expect($reading->getEffectiveValue())->toBeGreaterThan(0);
        }
        
        // Clean up
        $meter->readings()->delete();
        $meter->delete();
        $property->delete();
        $user->delete();
        $tenant->delete();
    }
});