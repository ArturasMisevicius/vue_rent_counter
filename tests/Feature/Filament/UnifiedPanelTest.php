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
        ->assertSeeText(__('shell.navigation.groups.properties'))
        ->assertSeeText(__('shell.navigation.groups.billing'))
        ->assertSeeText(__('shell.navigation.groups.reports'))
        ->assertSeeText(__('shell.navigation.groups.account'))
        ->assertSeeText('Buildings')
        ->assertSeeText('Properties')
        ->assertSeeText('Tenants')
        ->assertSeeText('Meters')
        ->assertSeeText('Meter Readings')
        ->assertSeeText('Invoices')
        ->assertSeeText('Tariffs')
        ->assertSeeText('Providers')
        ->assertSeeText('Service Configurations')
        ->assertSeeText('Utility Services')
        ->assertSeeText('Reports')
        ->assertSeeText('Profile')
        ->assertSeeText('Settings')
        ->assertSeeText(__('shell.navigation.items.organization_users'))
        ->assertSee(route('filament.admin.resources.organization-users.index'), false)
        ->assertSeeText('Logout')
        ->assertDontSeeText('Organizations')
        ->assertDontSee(route('filament.admin.resources.users.index'), false)
        ->assertDontSeeText('Subscriptions')
        ->assertDontSeeText('Translations')
        ->assertDontSeeText('Audit Logs')
        ->assertDontSeeText('Platform Notifications')
        ->assertDontSeeText('Languages')
        ->assertDontSeeText('Security Violations')
        ->assertDontSeeText('Integration Health');
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
