<?php

use App\Models\MeterReading;
use App\Models\MeterReadingAudit;
use App\Models\User;
use App\Models\Meter;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Create a user to act as the authenticated user
    $this->user = User::factory()->create([
        'role' => 'manager',
        'tenant_id' => 1,
    ]);
    $this->actingAs($this->user);
});

test('updating meter reading value creates audit record', function () {
    // Create a meter reading
    $meter = Meter::factory()->create(['tenant_id' => 1]);
    $reading = MeterReading::factory()->create([
        'meter_id' => $meter->id,
        'value' => 1000.00,
        'tenant_id' => 1,
    ]);

    // Update the reading value
    $reading->change_reason = 'Correcting data entry error';
    $reading->value = 1050.00;
    $reading->save();

    // Verify audit record was created
    expect($reading->auditTrail()->count())->toBe(1);

    $audit = $reading->auditTrail()->first();
    expect($audit->old_value)->toBe('1000.00');
    expect($audit->new_value)->toBe('1050.00');
    expect($audit->changed_by_user_id)->toBe($this->user->id);
    expect($audit->change_reason)->toBe('Correcting data entry error');
});

test('updating meter reading without changing value does not create audit record', function () {
    // Create a meter reading
    $meter = Meter::factory()->create(['tenant_id' => 1]);
    $reading = MeterReading::factory()->create([
        'meter_id' => $meter->id,
        'value' => 1000.00,
        'tenant_id' => 1,
    ]);

    // Update other fields but not value
    $reading->zone = 'day';
    $reading->save();

    // Verify no audit record was created
    expect($reading->auditTrail()->count())->toBe(0);
});

test('audit record stores correct old and new values', function () {
    // Create a meter reading
    $meter = Meter::factory()->create(['tenant_id' => 1]);
    $reading = MeterReading::factory()->create([
        'meter_id' => $meter->id,
        'value' => 500.50,
        'tenant_id' => 1,
    ]);

    // Update the reading value
    $reading->change_reason = 'Meter was read incorrectly';
    $reading->value = 525.75;
    $reading->save();

    // Verify audit record has correct values
    $audit = $reading->auditTrail()->first();
    expect($audit->old_value)->toBe('500.50');
    expect($audit->new_value)->toBe('525.75');
});

test('multiple updates create multiple audit records', function () {
    // Create a meter reading
    $meter = Meter::factory()->create(['tenant_id' => 1]);
    $reading = MeterReading::factory()->create([
        'meter_id' => $meter->id,
        'value' => 1000.00,
        'tenant_id' => 1,
    ]);

    // First update
    $reading->change_reason = 'First correction';
    $reading->value = 1050.00;
    $reading->save();

    // Second update
    $reading->change_reason = 'Second correction';
    $reading->value = 1075.00;
    $reading->save();

    // Verify two audit records were created
    expect($reading->auditTrail()->count())->toBe(2);

    $audits = $reading->auditTrail()->orderBy('created_at')->get();
    
    // First audit
    expect($audits[0]->old_value)->toBe('1000.00');
    expect($audits[0]->new_value)->toBe('1050.00');
    expect($audits[0]->change_reason)->toBe('First correction');
    
    // Second audit
    expect($audits[1]->old_value)->toBe('1050.00');
    expect($audits[1]->new_value)->toBe('1075.00');
    expect($audits[1]->change_reason)->toBe('Second correction');
});

test('audit record uses default reason when none provided', function () {
    // Create a meter reading
    $meter = Meter::factory()->create(['tenant_id' => 1]);
    $reading = MeterReading::factory()->create([
        'meter_id' => $meter->id,
        'value' => 1000.00,
        'tenant_id' => 1,
    ]);

    // Update without setting change_reason
    $reading->value = 1050.00;
    $reading->save();

    // Verify audit record was created with default reason
    $audit = $reading->auditTrail()->first();
    expect($audit->change_reason)->toBe('No reason provided');
});

test('audit record captures authenticated user', function () {
    // Create a different user
    $anotherUser = User::factory()->create([
        'role' => 'admin',
        'tenant_id' => 1,
    ]);

    // Create a meter reading
    $meter = Meter::factory()->create(['tenant_id' => 1]);
    $reading = MeterReading::factory()->create([
        'meter_id' => $meter->id,
        'value' => 1000.00,
        'tenant_id' => 1,
    ]);

    // Update as the first user
    $reading->change_reason = 'Updated by manager';
    $reading->value = 1050.00;
    $reading->save();

    // Switch to another user
    $this->actingAs($anotherUser);

    // Update again
    $reading->change_reason = 'Updated by admin';
    $reading->value = 1100.00;
    $reading->save();

    // Verify both audit records have correct users
    $audits = $reading->auditTrail()->orderBy('created_at')->get();
    
    expect($audits[0]->changed_by_user_id)->toBe($this->user->id);
    expect($audits[1]->changed_by_user_id)->toBe($anotherUser->id);
});

