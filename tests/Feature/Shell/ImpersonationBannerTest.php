<?php

use App\Models\Organization;
use App\Models\User;
use App\Support\Auth\ImpersonationManager;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('renders the impersonation banner in the shared app frame', function () {
    $superadmin = User::factory()->superadmin()->create([
        'name' => 'Sarah Superadmin',
        'email' => 'sarah@example.com',
    ]);

    $tenant = User::factory()->tenant()->create();

    $this->actingAs($tenant)
        ->withSession([
            ImpersonationManager::IMPERSONATOR_ID => $superadmin->getKey(),
            ImpersonationManager::IMPERSONATOR_EMAIL => $superadmin->email,
            ImpersonationManager::IMPERSONATOR_NAME => $superadmin->name,
        ])
        ->get(route('tenant.home'))
        ->assertSuccessful()
        ->assertSeeText('You are currently viewing this account as Sarah Superadmin (sarah@example.com).')
        ->assertSeeText('Stop Impersonating');
});

it('renders the impersonation banner on admin panel pages', function () {
    $superadmin = User::factory()->superadmin()->create([
        'name' => 'Sarah Superadmin',
        'email' => 'sarah@example.com',
    ]);

    $manager = User::factory()->manager()->create([
        'organization_id' => Organization::factory(),
    ]);

    $this->actingAs($manager)
        ->withSession([
            ImpersonationManager::IMPERSONATOR_ID => $superadmin->getKey(),
            ImpersonationManager::IMPERSONATOR_EMAIL => $superadmin->email,
            ImpersonationManager::IMPERSONATOR_NAME => $superadmin->name,
        ])
        ->get(route('filament.admin.pages.organization-dashboard'))
        ->assertSuccessful()
        ->assertSeeText('Stop Impersonating');
});

it('stops impersonation and redirects back to the real users dashboard', function () {
    $superadmin = User::factory()->superadmin()->create([
        'name' => 'Sarah Superadmin',
        'email' => 'sarah@example.com',
    ]);

    $tenant = User::factory()->tenant()->create();

    $this->actingAs($tenant)
        ->withSession([
            ImpersonationManager::IMPERSONATOR_ID => $superadmin->getKey(),
            ImpersonationManager::IMPERSONATOR_EMAIL => $superadmin->email,
            ImpersonationManager::IMPERSONATOR_NAME => $superadmin->name,
        ])
        ->post(route('impersonation.stop'))
        ->assertRedirect(route('filament.admin.pages.platform-dashboard'));

    $this->assertAuthenticatedAs($superadmin);

    expect(session()->has(ImpersonationManager::IMPERSONATOR_ID))->toBeFalse();
});

it('does not render the banner outside impersonation', function () {
    $tenant = User::factory()->tenant()->create();

    $this->actingAs($tenant)
        ->get(route('tenant.home'))
        ->assertSuccessful()
        ->assertDontSeeText('Stop Impersonating');
});
