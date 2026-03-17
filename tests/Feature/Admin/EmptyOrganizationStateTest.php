<?php

use App\Models\Organization;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('shows guided empty states on first-run organization list pages', function () {
    $organization = Organization::factory()->create();
    $admin = User::factory()->admin()->create([
        'organization_id' => $organization->id,
    ]);

    Subscription::factory()->for($organization)->active()->create([
        'property_limit_snapshot' => 10,
        'tenant_limit_snapshot' => 10,
    ]);

    $this->actingAs($admin)
        ->get(route('filament.admin.resources.buildings.index'))
        ->assertSuccessful()
        ->assertSeeText(__('admin.buildings.empty_state.heading'))
        ->assertSeeText(__('admin.buildings.empty_state.description'))
        ->assertSee(route('filament.admin.resources.buildings.create'), false);

    $this->actingAs($admin)
        ->get(route('filament.admin.resources.properties.index'))
        ->assertSuccessful()
        ->assertSeeText(__('admin.properties.empty_state.heading'))
        ->assertSeeText(__('admin.properties.empty_state.description'))
        ->assertSee(route('filament.admin.resources.properties.create'), false);

    $this->actingAs($admin)
        ->get(route('filament.admin.resources.tenants.index'))
        ->assertSuccessful()
        ->assertSeeText(__('admin.tenants.empty_state.heading'))
        ->assertSeeText(__('admin.tenants.empty_state.description'))
        ->assertSee(route('filament.admin.resources.tenants.create'), false);

    $this->actingAs($admin)
        ->get(route('filament.admin.resources.meters.index'))
        ->assertSuccessful()
        ->assertSeeText(__('admin.meters.empty_state.heading'))
        ->assertSeeText(__('admin.meters.empty_state.description'))
        ->assertSee(route('filament.admin.resources.meters.create'), false);
});
