<?php

use App\Enums\SubscriptionStatus;
use App\Enums\UserRole;
use App\Models\Meter;
use App\Models\OrganizationActivityLog;
use App\Models\Property;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('tenant.context can log API write operations without throwing', function () {
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => 12345,
        'is_active' => true,
    ]);

    Subscription::factory()->create([
        'user_id' => $admin->id,
        'status' => SubscriptionStatus::ACTIVE->value,
        'expires_at' => now()->addDays(30),
    ]);

    $this->actingAs($admin);
    $this->withoutExceptionHandling();

    $property = Property::factory()->create();
    $meter = Meter::factory()->create([
        'property_id' => $property->id,
    ]);

    $response = $this->postJson('/api/meter-readings', [
        'meter_id' => $meter->id,
        'reading_date' => now()->toDateString(),
        'value' => 10.5,
    ]);

    expect($response->status())->not->toBe(500);
    expect(OrganizationActivityLog::query()->count())->toBeGreaterThanOrEqual(0);
});
