<?php

use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('renders the impersonation banner on tenant pages when impersonation metadata exists', function () {
    $impersonator = User::factory()->admin()->create();
    $tenant = User::factory()->tenant()->create();

    $this->actingAs($tenant)
        ->withSession(impersonationSessionFor($impersonator))
        ->get(route('tenant.home'))
        ->assertSuccessful()
        ->assertSeeText('You are impersonating this account')
        ->assertSeeText($impersonator->name)
        ->assertSeeText($impersonator->email);
});

it('renders the impersonation banner on admin pages when impersonation metadata exists', function () {
    $organization = Organization::factory()->create();
    $impersonator = User::factory()->superadmin()->create();
    $manager = User::factory()->manager()->create([
        'organization_id' => $organization->id,
    ]);

    $this->actingAs($manager)
        ->withSession(impersonationSessionFor($impersonator))
        ->get(route('filament.admin.pages.organization-dashboard'))
        ->assertSuccessful()
        ->assertSeeText('You are impersonating this account')
        ->assertSeeText($impersonator->name)
        ->assertSeeText($impersonator->email);
});

it('clears impersonation state and returns to the impersonators dashboard', function () {
    $organization = Organization::factory()->create();
    $impersonator = User::factory()->admin()->create([
        'organization_id' => $organization->id,
    ]);
    $tenant = User::factory()->tenant()->create([
        'organization_id' => $organization->id,
    ]);

    $this->actingAs($tenant)
        ->withSession(impersonationSessionFor($impersonator))
        ->post(route('impersonation.stop'))
        ->assertRedirect(route('filament.admin.pages.organization-dashboard'));

    $this->assertAuthenticatedAs($impersonator);

    expect(session()->missing('impersonator_id'))->toBeTrue()
        ->and(session()->missing('impersonator_name'))->toBeTrue()
        ->and(session()->missing('impersonator_email'))->toBeTrue();
});

it('does not render the banner when no impersonation session exists', function () {
    $tenant = User::factory()->tenant()->create();

    $this->actingAs($tenant)
        ->get(route('tenant.home'))
        ->assertSuccessful()
        ->assertDontSeeText('You are impersonating this account');
});

function impersonationSessionFor(User $impersonator): array
{
    return [
        'impersonator_id' => $impersonator->id,
        'impersonator_name' => $impersonator->name,
        'impersonator_email' => $impersonator->email,
    ];
}
