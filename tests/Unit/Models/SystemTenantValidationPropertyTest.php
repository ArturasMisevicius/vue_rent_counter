<?php

declare(strict_types=1);

use App\Enums\SystemSubscriptionPlan;
use App\Enums\SystemTenantStatus;
use App\Models\SystemTenant;
use App\Models\User;

/**
 * Feature: superadmin-tenant-control, Property 4: Tenant Creation Validation
 * 
 * Property: For any tenant creation attempt, the system should require tenant name, 
 * primary contact email, and subscription plan selection before allowing creation
 * 
 * Validates: Requirements 2.1
 */
it('validates required fields for system tenant creation', function () {
    // Property: For any valid tenant data with required fields, creation should succeed
    $validData = [
        'name' => fake()->company(),
        'primary_contact_email' => fake()->email(),
        'subscription_plan' => fake()->randomElement(SystemSubscriptionPlan::cases()),
    ];
    
    $tenant = SystemTenant::create($validData);
    
    expect($tenant)->toBeInstanceOf(SystemTenant::class)
        ->and($tenant->name)->toBe($validData['name'])
        ->and($tenant->primary_contact_email)->toBe($validData['primary_contact_email'])
        ->and($tenant->subscription_plan)->toBe($validData['subscription_plan'])
        ->and($tenant->status)->toBe(SystemTenantStatus::PENDING)
        ->and($tenant->slug)->not()->toBeEmpty();
})->repeat(100);

it('rejects system tenant creation without required name field', function () {
    // Property: For any tenant data missing required name, creation should fail
    $invalidData = [
        'primary_contact_email' => fake()->email(),
        'subscription_plan' => fake()->randomElement(SystemSubscriptionPlan::cases()),
    ];
    
    expect(fn() => SystemTenant::create($invalidData))
        ->toThrow(\Illuminate\Database\QueryException::class);
})->repeat(50);

it('rejects system tenant creation without required email field', function () {
    // Property: For any tenant data missing required email, creation should fail
    $invalidData = [
        'name' => fake()->company(),
        'subscription_plan' => fake()->randomElement(SystemSubscriptionPlan::cases()),
    ];
    
    expect(fn() => SystemTenant::create($invalidData))
        ->toThrow(\Illuminate\Database\QueryException::class);
})->repeat(50);

it('generates unique slugs for system tenants with same names', function () {
    // Property: For any tenants with identical names, slugs should be unique
    $name = fake()->company();
    
    $tenant1 = SystemTenant::create([
        'name' => $name,
        'primary_contact_email' => fake()->email(),
        'subscription_plan' => fake()->randomElement(SystemSubscriptionPlan::cases()),
    ]);
    
    $tenant2 = SystemTenant::create([
        'name' => $name,
        'primary_contact_email' => fake()->email(),
        'subscription_plan' => fake()->randomElement(SystemSubscriptionPlan::cases()),
    ]);
    
    expect($tenant1->slug)->not()->toBe($tenant2->slug)
        ->and($tenant1->name)->toBe($tenant2->name);
})->repeat(25);

it('sets default resource quotas based on subscription plan', function () {
    // Property: For any tenant with a subscription plan, default quotas should be set
    $plan = fake()->randomElement(SystemSubscriptionPlan::cases());
    
    $tenant = SystemTenant::create([
        'name' => fake()->company(),
        'primary_contact_email' => fake()->email(),
        'subscription_plan' => $plan,
    ]);
    
    $expectedQuotas = $plan->getDefaultQuotas();
    
    expect($tenant->resource_quotas)->toBe($expectedQuotas);
})->repeat(50);

it('validates subscription plan transitions', function () {
    // Property: For any tenant, status transitions should follow business rules
    $tenant = SystemTenant::factory()->create([
        'status' => SystemTenantStatus::PENDING,
    ]);
    
    // Should be able to activate from pending
    expect($tenant->status->canTransitionTo(SystemTenantStatus::ACTIVE))->toBeTrue()
        ->and($tenant->status->canTransitionTo(SystemTenantStatus::CANCELLED))->toBeTrue()
        ->and($tenant->status->canTransitionTo(SystemTenantStatus::SUSPENDED))->toBeFalse();
    
    $tenant->activate();
    expect($tenant->status)->toBe(SystemTenantStatus::ACTIVE);
    
    // Should be able to suspend from active
    expect($tenant->status->canTransitionTo(SystemTenantStatus::SUSPENDED))->toBeTrue()
        ->and($tenant->status->canTransitionTo(SystemTenantStatus::CANCELLED))->toBeTrue()
        ->and($tenant->status->canTransitionTo(SystemTenantStatus::PENDING))->toBeFalse();
})->repeat(25);