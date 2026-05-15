<?php

use App\Livewire\Shell\OnboardingWizard;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('shows the onboarding wizard to organization users until they finish it', function () {
    $admin = createOrgWithAdmin()['admin'];

    $this->actingAs($admin)
        ->get('/app')
        ->assertSuccessful()
        ->assertSee('data-onboarding-wizard-root', false)
        ->assertSee('data-onboarding-wizard-panel', false)
        ->assertSee('data-onboarding-tour-button', false)
        ->assertSeeText(__('onboarding.tour.title'))
        ->assertSeeText(__('onboarding.tour.roles.admin.steps.workspace.title'));

    Livewire::actingAs($admin)
        ->test(OnboardingWizard::class)
        ->assertSet('isEligible', true)
        ->assertSet('isOpen', true)
        ->call('next')
        ->assertSet('stepIndex', 1)
        ->call('goTo', 99)
        ->assertSet('stepIndex', 4)
        ->call('finish')
        ->assertSet('isOpen', false)
        ->assertSet('stepIndex', 0);

    expect($admin->fresh()->onboarding_tour_completed_at)->not->toBeNull();

    $this->actingAs($admin->fresh())
        ->get('/app')
        ->assertSuccessful()
        ->assertSee('data-onboarding-wizard-root', false)
        ->assertDontSee('data-onboarding-wizard-panel', false)
        ->assertSee('data-onboarding-tour-button', false);
});

it('does not show the onboarding wizard or guide button to superadmins', function () {
    $superadmin = User::factory()->superadmin()->create();

    $this->actingAs($superadmin)
        ->get('/app')
        ->assertSuccessful()
        ->assertDontSee('data-onboarding-wizard-root', false)
        ->assertDontSee('data-onboarding-wizard-panel', false)
        ->assertDontSee('data-onboarding-tour-button', false);

    Livewire::actingAs($superadmin)
        ->test(OnboardingWizard::class)
        ->assertSet('isEligible', false)
        ->assertSet('isOpen', false);
});

it('uses role-specific localized copy for tenants', function () {
    $admin = createOrgWithAdmin()['admin'];
    $tenant = createTenantInOrg($admin)['tenant'];
    $tenant->forceFill(['locale' => 'lt'])->save();

    $this->actingAs($tenant->fresh())
        ->get('/app')
        ->assertSuccessful()
        ->assertSee('data-onboarding-wizard-panel', false)
        ->assertSeeText(__('onboarding.tour.title', [], 'lt'))
        ->assertSeeText(__('onboarding.tour.roles.tenant.steps.workspace.title', [], 'lt'))
        ->assertSeeText(__('onboarding.tour.actions.open', [], 'lt'));
});

it('allows users to postpone the onboarding wizard for the current session', function () {
    $admin = createOrgWithAdmin()['admin'];

    Livewire::actingAs($admin)
        ->test(OnboardingWizard::class)
        ->assertSet('isOpen', true)
        ->call('dismiss')
        ->assertSet('isOpen', false);

    expect($admin->fresh()->onboarding_tour_completed_at)->toBeNull()
        ->and((bool) session()->get('onboarding_tour_dismissed', false))->toBeTrue();
});
