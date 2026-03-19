<?php

use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('allows a superadmin to access the unified app panel and see the platform navigation group', function () {
    $user = User::factory()->superadmin()->create();

    $this->actingAs($user)
        ->get('/app')
        ->assertSuccessful()
        ->assertSeeText(__('shell.navigation.groups.platform'));
});

it('allows an admin to access the unified app panel without platform navigation items', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->admin()->create([
        'organization_id' => $organization->id,
    ]);

    $this->actingAs($user)
        ->get('/app')
        ->assertSuccessful()
        ->assertDontSeeText(__('shell.navigation.groups.platform'))
        ->assertSeeText(__('shell.navigation.groups.properties'));
});

it('allows a tenant to access the unified app panel without properties or billing navigation groups', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->tenant()->create([
        'organization_id' => $organization->id,
    ]);

    $this->actingAs($user)
        ->get('/app')
        ->assertSuccessful()
        ->assertSeeText(__('tenant.navigation.home'))
        ->assertSeeText(__('shell.navigation.groups.my_home'))
        ->assertDontSeeText(__('shell.navigation.groups.properties'))
        ->assertDontSeeText(__('shell.navigation.groups.billing'));
});

it('redirects unauthenticated panel access to the panel login page', function () {
    $this->get('/app')
        ->assertRedirect('/app/login');
});
