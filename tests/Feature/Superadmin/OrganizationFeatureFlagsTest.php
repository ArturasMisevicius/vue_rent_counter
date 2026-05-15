<?php

use App\Enums\AuditLogAction;
use App\Filament\Actions\Superadmin\Organizations\ToggleOrganizationFeatureAction;
use App\Filament\Support\Features\OrganizationFeatureCatalog;
use App\Models\AuditLog;
use App\Models\Organization;
use App\Models\OrganizationFeatureOverride;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Pennant\Feature;

uses(RefreshDatabase::class);

it('resolves organization feature flags from the latest override', function () {
    $organization = Organization::factory()->create([
        'name' => 'Northwind Towers',
    ]);

    OrganizationFeatureOverride::factory()->create([
        'organization_id' => $organization->id,
        'feature' => 'advanced_reporting',
        'enabled' => true,
        'reason' => 'Pilot cohort rollout',
    ]);

    expect($organization->fresh()->featureEnabled('advanced_reporting'))->toBeTrue()
        ->and($organization->fresh()->featureEnabled('resident_app'))->toBeFalse();

    OrganizationFeatureOverride::factory()->create([
        'organization_id' => $organization->id,
        'feature' => 'advanced_reporting',
        'enabled' => false,
        'reason' => 'Rollback',
    ]);

    expect($organization->fresh()->featureEnabled('advanced_reporting', true))->toBeFalse();
});

it('toggles organization feature flags through the superadmin action with audit metadata', function () {
    $superadmin = User::factory()->superadmin()->create();
    $organization = Organization::factory()->create([
        'name' => 'Northwind Towers',
    ]);

    $this->actingAs($superadmin);

    $override = app(ToggleOrganizationFeatureAction::class)->handle(
        $organization->fresh(),
        'Advanced Reporting',
        true,
        'Pilot rollout',
    );

    expect($override->organization_id)->toBe($organization->id)
        ->and($override->feature)->toBe('advanced_reporting')
        ->and($override->enabled)->toBeTrue()
        ->and($organization->fresh()->featureEnabled('advanced_reporting'))->toBeTrue()
        ->and(Feature::for($organization->fresh())->active('advanced_reporting'))->toBeTrue();

    $auditLog = AuditLog::query()
        ->where('organization_id', $organization->id)
        ->where('action', AuditLogAction::UPDATED)
        ->latest('id')
        ->first();

    expect($auditLog)->not->toBeNull()
        ->and($auditLog?->actor_user_id)->toBe($superadmin->id)
        ->and($auditLog?->metadata)->toMatchArray([
            'reason' => 'Pilot rollout',
            'feature' => 'advanced_reporting',
            'enabled' => true,
        ]);
});

it('resolves catalog feature defaults through pennant by active subscription plan', function () {
    $starterOrganization = Organization::factory()->create();
    $professionalOrganization = Organization::factory()->create();

    Subscription::factory()
        ->starter()
        ->active()
        ->for($starterOrganization)
        ->create();

    Subscription::factory()
        ->professional()
        ->active()
        ->for($professionalOrganization)
        ->create();

    expect($starterOrganization->fresh()->featureEnabled(OrganizationFeatureCatalog::ADVANCED_REPORTING))->toBeFalse()
        ->and($professionalOrganization->fresh()->featureEnabled(OrganizationFeatureCatalog::ADVANCED_REPORTING))->toBeTrue()
        ->and(Feature::for($professionalOrganization->fresh())->active(OrganizationFeatureCatalog::ADVANCED_REPORTING))->toBeTrue();
});

it('localizes organization feature option labels for Filament actions', function () {
    app()->setLocale('lt');

    expect(OrganizationFeatureCatalog::label(OrganizationFeatureCatalog::ADVANCED_REPORTING))
        ->toBe(__('superadmin.organizations.features.advanced_reporting', [], 'lt'))
        ->not->toBe('Advanced reporting')
        ->and(OrganizationFeatureCatalog::options()[OrganizationFeatureCatalog::BULK_INVOICING])
        ->toBe(__('superadmin.organizations.features.bulk_invoicing', [], 'lt'))
        ->not->toBe('Bulk invoicing');
});
