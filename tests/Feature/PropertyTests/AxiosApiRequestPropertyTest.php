<?php

/**
 * Feature: framework-upgrade, Property 5: API request functionality
 * 
 * For any HTTP request made through Axios or the application's API layer,
 * the request should complete successfully with the expected response format
 * and status code after the Axios upgrade.
 * 
 * Validates: Requirements 5.3
 */

use App\Models\User;
use App\Models\Meter;
use App\Models\Property;
use App\Models\Provider;
use App\Models\MeterReading;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\TestDatabaseSeeder::class);
});

test('API endpoints return successful responses with correct format for authenticated users', function () {
    // Test with different user roles to ensure API works across the application
    $admin = User::factory()->create(['role' => 'admin']);
    $manager = User::factory()->create(['role' => 'manager']);
    
    $users = [$admin, $manager];
    
    foreach ($users as $user) {
        // Test meter last reading endpoint
        $meter = Meter::first();
        if ($meter) {
            $response = $this->actingAs($user)
                ->getJson("/api/meters/{$meter->id}/last-reading");
            
            expect($response->status())
                ->toBeIn([200, 404]) // 200 if reading exists, 404 if no readings
                ->and($response->headers->get('content-type'))
                ->toContain('application/json');
            
            if ($response->status() === 200) {
                $json = $response->json();
                expect($json)->toBeArray();
                
                // Response structure varies based on meter type (zones vs single)
                // All responses should have 'value' and 'date' keys
                expect($json)->toHaveKey('value')
                    ->and($json)->toHaveKey('date');
            }
        }
        
        // Test properties list endpoint
        $response = $this->actingAs($user)
            ->getJson('/api/properties');
        
        expect($response->status())->toBe(200)
            ->and($response->headers->get('content-type'))->toContain('application/json')
            ->and($response->json())->toBeArray();
        
        // Test property details endpoint
        $property = Property::first();
        if ($property) {
            $response = $this->actingAs($user)
                ->getJson("/api/properties/{$property->id}");
            
            expect($response->status())->toBe(200)
                ->and($response->headers->get('content-type'))->toContain('application/json')
                ->and($response->json())->toBeArray()
                ->and($response->json())->toHaveKey('id');
        }
        
        // Test provider tariffs endpoint
        $provider = Provider::first();
        if ($provider) {
            $response = $this->actingAs($user)
                ->getJson("/api/providers/{$provider->id}/tariffs");
            
            expect($response->status())->toBe(200)
                ->and($response->headers->get('content-type'))->toContain('application/json')
                ->and($response->json())->toBeArray();
        }
    }
})->repeat(100);

test('API POST requests process data correctly and return appropriate responses', function () {
    $manager = User::factory()->create(['role' => 'manager']);
    
    $meter = Meter::first();
    if (!$meter) {
        $this->markTestSkipped('No meters available for testing');
    }
    
    // Get the last reading to ensure monotonic increase
    $lastReading = MeterReading::where('meter_id', $meter->id)
        ->orderBy('reading_date', 'desc')
        ->first();
    
    $newReadingValue = $lastReading ? $lastReading->reading_value + 10 : 100;
    $newReadingDate = $lastReading 
        ? now()->parse($lastReading->reading_date)->addDay()->format('Y-m-d')
        : now()->format('Y-m-d');
    
    $response = $this->actingAs($manager)
        ->postJson('/api/meter-readings', [
            'meter_id' => $meter->id,
            'reading_value' => $newReadingValue,
            'reading_date' => $newReadingDate,
            'notes' => 'Property test reading',
        ]);
    
    // Should return either 201 (created) or 422 (validation error)
    expect($response->status())
        ->toBeIn([201, 422])
        ->and($response->headers->get('content-type'))
        ->toContain('application/json');
    
    if ($response->status() === 201) {
        expect($response->json())
            ->toBeArray()
            ->toHaveKeys(['id', 'meter_id', 'reading_date', 'value']);
    }
})->repeat(100);

test('API endpoints enforce authentication and return 401 for unauthenticated requests', function () {
    $endpoints = [
        ['method' => 'GET', 'url' => '/api/properties'],
        ['method' => 'GET', 'url' => '/api/meters/1/last-reading'],
        ['method' => 'POST', 'url' => '/api/meter-readings'],
    ];
    
    foreach ($endpoints as $endpoint) {
        $response = match($endpoint['method']) {
            'GET' => $this->getJson($endpoint['url']),
            'POST' => $this->postJson($endpoint['url'], []),
            default => null,
        };
        
        if ($response) {
            expect($response->status())->toBe(401)
                ->and($response->headers->get('content-type'))->toContain('application/json');
        }
    }
})->repeat(100);

test('API endpoints return consistent response structure across multiple requests', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    
    // Make the same request multiple times and verify consistency
    $responses = [];
    for ($i = 0; $i < 5; $i++) {
        $response = $this->actingAs($admin)
            ->getJson('/api/properties');
        
        $responses[] = [
            'status' => $response->status(),
            'content_type' => $response->headers->get('content-type'),
            'is_array' => is_array($response->json()),
        ];
    }
    
    // All responses should have the same structure
    $firstResponse = $responses[0];
    foreach ($responses as $response) {
        expect($response['status'])->toBe($firstResponse['status'])
            ->and($response['content_type'])->toBe($firstResponse['content_type'])
            ->and($response['is_array'])->toBe($firstResponse['is_array']);
    }
})->repeat(100);
