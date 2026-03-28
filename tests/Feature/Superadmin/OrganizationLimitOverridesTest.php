<?php

use App\Enums\AuditLogAction;
use App\Enums\SubscriptionAccessMode;
use App\Enums\SubscriptionPlan;
use App\Enums\SubscriptionStatus;
use App\Filament\Actions\Superadmin\Organizations\OverrideOrganizationLimitsAction;
use App\Jobs\Superadmin\Organizations\ExpireOrganizationLimitOverridesJob;
use App\Models\AuditLog;
use App\Models\Building;
use App\Models\Organization;
use App\Models\OrganizationLimitOverride;
use App\Models\Property;
use App\Models\Subscription;
use App\Models\User;
use App\Services\SubscriptionChecker;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('prefers an active limit override over the subscription snapshot for access checks', function () {
    [$organization] = seedOrganizationForLimitOverrides();

    $building = Building::factory()->create([
        'organization_id' => $organization->id,
    ]);

    Property::factory()->count(10)->create([
        'organization_id' => $organization->id,
        'building_id' => $building->id,
    ]);

    OrganizationLimitOverride::factory()->create([
        'organization_id' => $organization->id,
        'dimension' => 'properties',
        'value' => 12,
        'reason' => 'Seasonal growth buffer',
        'expires_at' => now()->addDay(),
    ]);

    $organization = $organization->fresh();
    $accessState = app(SubscriptionChecker::class)->accessStateForOrganization($organization);

    expect($organization->effectivePropertyLimit())->toBe(12)
        ->and($accessState->mode)->toBe(SubscriptionAccessMode::ACTIVE)
        ->and($accessState->limits['properties'])->toBe(12)
        ->and($accessState->isLimitBlocked('properties'))->toBeFalse();
});

it('expires org limit overrides and reverts to subscription limits', function () {
    [$organization] = seedOrganizationForLimitOverrides();

    $building = Building::factory()->create([
        'organization_id' => $organization->id,
    ]);

    Property::factory()->count(10)->create([
        'organization_id' => $organization->id,
        'building_id' => $building->id,
    ]);

    $override = OrganizationLimitOverride::factory()->create([
        'organization_id' => $organization->id,
        'dimension' => 'properties',
        'value' => 12,
        'reason' => 'Migration buffer',
        'expires_at' => now()->addMinute(),
    ]);

    expect($organization->fresh()->effectivePropertyLimit())->toBe(12);

    $this->travel(2)->minutes();

    app(ExpireOrganizationLimitOverridesJob::class)->handle();

    $organization = $organization->fresh();
    $accessState = app(SubscriptionChecker::class)->accessStateForOrganization($organization);

    expect($organization->effectivePropertyLimit())->toBe(SubscriptionPlan::BASIC->limits()['properties'])
        ->and($accessState->mode)->toBe(SubscriptionAccessMode::LIMIT_BLOCKED)
        ->and($accessState->limits['properties'])->toBe(SubscriptionPlan::BASIC->limits()['properties'])
        ->and(OrganizationLimitOverride::query()->whereKey($override->id)->exists())->toBeFalse();
});

it('stores organization limit overrides through the superadmin action with audit metadata', function () {
    $superadmin = User::factory()->superadmin()->create();
    [$organization] = seedOrganizationForLimitOverrides();

    $this->actingAs($superadmin);

    $override = app(OverrideOrganizationLimitsAction::class)->handle(
        $organization->fresh(),
        'properties',
        99,
        'Enterprise migration grace',
        now()->addDays(7),
    );

    expect($override->organization_id)->toBe($organization->id)
        ->and($override->dimension)->toBe('properties')
        ->and($override->value)->toBe(99)
        ->and($organization->fresh()->effectivePropertyLimit())->toBe(99);

    $auditLog = AuditLog::query()
        ->where('organization_id', $organization->id)
        ->where('action', AuditLogAction::UPDATED)
        ->latest('id')
        ->first();

    expect($auditLog)->not->toBeNull()
        ->and($auditLog?->actor_user_id)->toBe($superadmin->id)
        ->and($auditLog?->metadata)->toMatchArray([
            'reason' => 'Enterprise migration grace',
            'dimension' => 'properties',
            'value' => 99,
        ]);
});

function seedOrganizationForLimitOverrides(): array
{
    $organization = Organization::factory()->create([
        'name' => 'Northwind Towers',
    ]);

    $owner = User::factory()->admin()->create([
        'organization_id' => $organization->id,
        'name' => 'Olivia Owner',
        'email' => 'owner@northwind.test',
        'email_verified_at' => now(),
    ]);

    $organization->forceFill([
        'owner_user_id' => $owner->id,
    ])->save();

    Subscription::factory()->for($organization)->active()->create([
        'plan' => SubscriptionPlan::BASIC,
        'status' => SubscriptionStatus::ACTIVE,
        'is_trial' => false,
        'property_limit_snapshot' => SubscriptionPlan::BASIC->limits()['properties'],
        'tenant_limit_snapshot' => SubscriptionPlan::BASIC->limits()['tenants'],
        'meter_limit_snapshot' => SubscriptionPlan::BASIC->limits()['meters'],
        'invoice_limit_snapshot' => SubscriptionPlan::BASIC->limits()['invoices'],
    ]);

    return [$organization->fresh(), $owner->fresh()];
}
