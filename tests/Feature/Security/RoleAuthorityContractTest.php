<?php

declare(strict_types=1);

use App\Filament\Pages\Settings;
use App\Models\Organization;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('derives platform authority from the role contract instead of the legacy superadmin flag', function () {
    $workspaceOrganization = Organization::factory()->create();
    $subscriptionOrganization = Organization::factory()->create();
    $legacyFlagUser = User::factory()->admin()->create([
        'organization_id' => $workspaceOrganization->id,
        'is_super_admin' => true,
    ]);
    $subscription = Subscription::factory()->for($subscriptionOrganization)->create();

    expect($legacyFlagUser->isSuperadmin())->toBeFalse()
        ->and(Gate::forUser($legacyFlagUser)->allows('viewAny', Organization::class))->toBeFalse()
        ->and(Gate::forUser($legacyFlagUser)->allows('viewAny', Subscription::class))->toBeFalse()
        ->and(Gate::forUser($legacyFlagUser)->allows('view', $subscriptionOrganization))->toBeFalse()
        ->and(Gate::forUser($legacyFlagUser)->allows('view', $subscription))->toBeFalse();
});

it('retains platform authority for the superadmin role even when the legacy flag is false', function () {
    $organization = Organization::factory()->create();
    $subscription = Subscription::factory()->for($organization)->create();
    $superadmin = User::factory()->superadmin()->create([
        'is_super_admin' => false,
    ]);

    expect($superadmin->isSuperadmin())->toBeTrue()
        ->and(Gate::forUser($superadmin)->allows('viewAny', Organization::class))->toBeTrue()
        ->and(Gate::forUser($superadmin)->allows('viewAny', Subscription::class))->toBeTrue()
        ->and(Gate::forUser($superadmin)->allows('view', $organization))->toBeTrue()
        ->and(Gate::forUser($superadmin)->allows('view', $subscription))->toBeTrue();
});

it('forbids managers from mutating organization billing settings', function () {
    $organization = Organization::factory()->create();
    $manager = User::factory()->manager()->create([
        'organization_id' => $organization->id,
    ]);

    Livewire::actingAs($manager)
        ->test(Settings::class)
        ->set('organizationForm.billing_contact_name', 'Manager Override')
        ->set('organizationForm.billing_contact_email', 'manager@example.com')
        ->set('organizationForm.billing_contact_phone', '+37060000000')
        ->set('organizationForm.payment_instructions', 'Forbidden')
        ->set('organizationForm.invoice_footer', 'Forbidden')
        ->call('saveOrganizationSettings')
        ->assertForbidden();
});

it('forbids managers from renewing the organization subscription from settings', function () {
    $organization = Organization::factory()->create();
    $manager = User::factory()->manager()->create([
        'organization_id' => $organization->id,
    ]);

    Livewire::actingAs($manager)
        ->test(Settings::class)
        ->call('renewSubscription')
        ->assertForbidden();
});
