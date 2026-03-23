<?php

use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('only allows superadmins to reach organizations control-plane pages', function () {
    $superadmin = User::factory()->superadmin()->create();
    $organization = Organization::factory()->create();
    $admin = User::factory()->admin()->create([
        'organization_id' => $organization->id,
    ]);

    $this->actingAs($superadmin)
        ->get(route('filament.admin.resources.organizations.index'))
        ->assertSuccessful()
        ->assertSeeText('Organizations')
        ->assertSeeText($organization->name);

    $this->actingAs($superadmin)
        ->get(route('filament.admin.resources.organizations.create'))
        ->assertSuccessful()
        ->assertSeeText('Create organization')
        ->assertSeeText('Owner email')
        ->assertSeeText('Subscription plan');

    $this->actingAs($superadmin)
        ->get(route('filament.admin.resources.organizations.view', $organization))
        ->assertSuccessful()
        ->assertSeeText('Organization Overview')
        ->assertSeeText($organization->slug);

    $this->actingAs($superadmin)
        ->get(route('filament.admin.resources.organizations.view', $organization).'?relation=1')
        ->assertSuccessful();

    $this->actingAs($superadmin)
        ->get(route('filament.admin.resources.organizations.edit', $organization))
        ->assertSuccessful()
        ->assertSeeText('Save changes')
        ->assertSeeText($organization->name);

    $this->actingAs($admin)
        ->get(route('filament.admin.resources.organizations.index'))
        ->assertForbidden();

    $this->actingAs($admin)
        ->get(route('filament.admin.resources.organizations.view', $organization))
        ->assertForbidden();
});
