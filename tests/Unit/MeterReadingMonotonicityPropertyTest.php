<?php

use App\Models\Meter;
use App\Models\MeterReading;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// Feature: vilnius-utilities-billing, Property 1: Meter reading monotonicity
// Validates: Requirements 1.2
test('meter readings must be monotonically increasing', function () {
    // Set up tenant context
    $tenantId = fake()->numberBetween(1, 1000);
    session(['tenant_id' => $tenantId]);
    
    // Create a user for entered_by field
    $user = User::factory()->create(['tenant_id' => $tenantId]);
    
    // Create a meter with a previous reading
    $meter = Meter::factory()->create(['tenant_id' => $tenantId]);
    $previousValue = fake()->randomFloat(2, 1000, 5000);
    
    MeterReading::factory()->create([
        'tenant_id' => $tenantId,
        'meter_id' => $meter->id,
        'value' => $previousValue,
        'reading_date' => now()->subDays(1),
        'entered_by' => $user->id,
    ]);
    
    // Property: Any reading lower than previous should be rejected
    $invalidReading = fake()->randomFloat(2, 0, $previousValue - 0.01);
    
    // Create the request and validate
    $request = new \App\Http\Requests\StoreMeterReadingRequest();
    $request->replace([
        'meter_id' => $meter->id,
        'value' => $invalidReading,
        'reading_date' => now()->format('Y-m-d'),
        'entered_by' => $user->id,
    ]);
    
    // Get validator instance
    $validator = \Illuminate\Support\Facades\Validator::make(
        $request->all(),
        $request->rules()
    );
    
    // Run the withValidator callback to trigger monotonicity check
    $request->withValidator($validator);
    
    // Property: Validation should fail
    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('value'))->toBeTrue();
})->repeat(100);
