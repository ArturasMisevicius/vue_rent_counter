<?php

use App\Enums\SubscriptionAccessMode;
use App\Enums\SubscriptionStatus;
use App\Models\Building;
use App\Models\Meter;
use App\Models\Organization;
use App\Models\Property;
use App\Models\Subscription;
use App\Models\User;
use App\Support\Admin\SubscriptionEnforcement\OrganizationSubscriptionAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('marks an active subscription below limits as active', function () {
    $organization = Organization::factory()->create();

    Subscription::factory()->for($organization)->active()->create([
        'property_limit_snapshot' => 10,
        'tenant_limit_snapshot' => 25,
        'meter_limit_snapshot' => 50,
        'invoice_limit_snapshot' => 100,
    ]);

    $state = app(OrganizationSubscriptionAccess::class)->forOrganization($organization);

    expect($state->mode)->toBe(SubscriptionAccessMode::ACTIVE)
        ->and($state->limitHits)->toBe([]);
});

it('marks a property-limit exhaustion as limit blocked', function () {
    $organization = Organization::factory()->create();
    $building = Building::factory()->for($organization)->create();

    Subscription::factory()->for($organization)->active()->create([
        'property_limit_snapshot' => 2,
        'tenant_limit_snapshot' => 25,
    ]);

    Property::factory()->count(2)->for($organization)->for($building)->create();

    $state = app(OrganizationSubscriptionAccess::class)->forOrganization($organization);

    expect($state->mode)->toBe(SubscriptionAccessMode::LIMIT_BLOCKED)
        ->and($state->isLimitBlocked('properties'))->toBeTrue();
});

it('marks a tenant-limit exhaustion as limit blocked', function () {
    $organization = Organization::factory()->create();

    Subscription::factory()->for($organization)->active()->create([
        'property_limit_snapshot' => 10,
        'tenant_limit_snapshot' => 2,
    ]);

    User::factory()->tenant()->count(2)->create([
        'organization_id' => $organization->id,
    ]);

    $state = app(OrganizationSubscriptionAccess::class)->forOrganization($organization);

    expect($state->mode)->toBe(SubscriptionAccessMode::LIMIT_BLOCKED)
        ->and($state->isLimitBlocked('tenants'))->toBeTrue();
});

it('marks an expired subscription inside the grace period as read only', function () {
    $organization = Organization::factory()->create();

    Subscription::factory()->for($organization)->create([
        'status' => SubscriptionStatus::EXPIRED,
        'expires_at' => now()->subDays(3),
    ]);

    $state = app(OrganizationSubscriptionAccess::class)->forOrganization($organization);

    expect($state->mode)->toBe(SubscriptionAccessMode::GRACE_READ_ONLY)
        ->and($state->canWrite())->toBeFalse();
});

it('marks an expired subscription after the grace period as post-grace read only', function () {
    config()->set('tenanto.subscription.grace_period_days', 7);

    $organization = Organization::factory()->create();
    $building = Building::factory()->for($organization)->create();
    $property = Property::factory()->for($organization)->for($building)->create();

    Subscription::factory()->for($organization)->create([
        'status' => SubscriptionStatus::EXPIRED,
        'expires_at' => now()->subDays(10),
    ]);

    Meter::factory()->for($organization)->for($property)->create();

    $state = app(OrganizationSubscriptionAccess::class)->forOrganization($organization);

    expect($state->mode)->toBe(SubscriptionAccessMode::POST_GRACE_READ_ONLY)
        ->and($state->hidesWriteActions())->toBeTrue();
});
