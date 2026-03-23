<?php

use App\Enums\UserRole;
use App\Models\Organization;
use App\Models\Property;
use App\Models\PropertyAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('syncs tenant organization id from active property assignments when tenant org is missing', function () {
    $organization = Organization::factory()->create();
    $property = Property::factory()->create([
        'organization_id' => $organization->id,
    ]);
    $tenant = User::factory()->tenant()->create([
        'organization_id' => null,
    ]);

    PropertyAssignment::factory()
        ->for($organization)
        ->for($property)
        ->for($tenant, 'tenant')
        ->create([
            'assigned_at' => now()->subDay(),
            'unassigned_at' => null,
        ]);

    expect($tenant->fresh()?->organization_id)->toBe($organization->id);
});

it('does not sync tenant organization id from inactive historical assignments', function () {
    $organization = Organization::factory()->create();
    $property = Property::factory()->create([
        'organization_id' => $organization->id,
    ]);
    $tenant = User::factory()->tenant()->create([
        'organization_id' => null,
    ]);

    PropertyAssignment::factory()
        ->for($organization)
        ->for($property)
        ->for($tenant, 'tenant')
        ->create([
            'assigned_at' => now()->subDays(30),
            'unassigned_at' => now()->subDay(),
        ]);

    expect($tenant->fresh()?->organization_id)->toBeNull();
});

it('does not mutate organization id for non-tenant users', function () {
    $organization = Organization::factory()->create();
    $property = Property::factory()->create([
        'organization_id' => $organization->id,
    ]);
    $user = User::factory()->create([
        'role' => UserRole::ADMIN,
        'organization_id' => null,
    ]);

    PropertyAssignment::factory()
        ->for($organization)
        ->for($property)
        ->for($user, 'tenant')
        ->create([
            'assigned_at' => now()->subDay(),
            'unassigned_at' => null,
        ]);

    expect($user->fresh()?->organization_id)->toBeNull();
});

it('does not override an existing tenant organization assignment', function () {
    $originalOrganization = Organization::factory()->create();
    $foreignOrganization = Organization::factory()->create();
    $property = Property::factory()->create([
        'organization_id' => $foreignOrganization->id,
    ]);
    $tenant = User::factory()->tenant()->create([
        'organization_id' => $originalOrganization->id,
    ]);

    PropertyAssignment::factory()
        ->for($foreignOrganization)
        ->for($property)
        ->for($tenant, 'tenant')
        ->create([
            'assigned_at' => now()->subDay(),
            'unassigned_at' => null,
        ]);

    expect($tenant->fresh()?->organization_id)->toBe($originalOrganization->id);
});
