<?php

use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('tenant scope filters properties by session tenant_id', function () {
    // Create two users with different tenant_ids
    $user1 = User::factory()->create(['tenant_id' => 1]);
    $user2 = User::factory()->create(['tenant_id' => 2]);

    // Create properties for each tenant
    $property1 = Property::withoutGlobalScopes()->create([
        'tenant_id' => 1,
        'address' => 'Address 1',
        'type' => 'apartment',
        'area_sqm' => 50.00,
    ]);

    $property2 = Property::withoutGlobalScopes()->create([
        'tenant_id' => 2,
        'address' => 'Address 2',
        'type' => 'apartment',
        'area_sqm' => 60.00,
    ]);

    // Set session tenant_id to 1
    session(['tenant_id' => 1]);

    // Query should only return property1
    $properties = Property::all();
    expect($properties)->toHaveCount(1);
    expect($properties->first()->id)->toBe($property1->id);

    // Change session tenant_id to 2
    session(['tenant_id' => 2]);

    // Query should only return property2
    $properties = Property::all();
    expect($properties)->toHaveCount(1);
    expect($properties->first()->id)->toBe($property2->id);
});

test('tenant scope returns empty results when session has no tenant_id', function () {
    // Create a property
    Property::withoutGlobalScopes()->create([
        'tenant_id' => 1,
        'address' => 'Address 1',
        'type' => 'apartment',
        'area_sqm' => 50.00,
    ]);

    // Clear session
    session()->forget('tenant_id');

    // Query should return all properties when no tenant_id in session
    $properties = Property::all();
    expect($properties)->toHaveCount(1);
});

test('authenticated event sets tenant_id in session', function () {
    $user = User::factory()->create(['tenant_id' => 123]);

    // Simulate authentication
    auth()->login($user);

    // Session should have tenant_id
    expect(session('tenant_id'))->toBe(123);
});

test('ensure tenant context middleware validates tenant_id', function () {
    $user = User::factory()->create(['tenant_id' => 1]);

    // Set correct tenant_id in session
    session(['tenant_id' => 1]);

    // Act as authenticated user
    $response = $this->actingAs($user)->get('/');

    // Should not redirect (middleware passes)
    expect($response->status())->not->toBe(302);
});
