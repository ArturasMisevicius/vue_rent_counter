<?php

use App\Enums\SubscriptionDuration;
use App\Enums\SubscriptionPlan;
use App\Enums\SubscriptionStatus;
use App\Filament\Actions\Superadmin\Organizations\ExportOrganizationsSummaryAction;
use App\Models\Building;
use App\Models\Organization;
use App\Models\Property;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('exports the selected organizations with visible summary columns', function () {
    $selectedOrganization = Organization::factory()->create([
        'name' => 'Aurora Estates',
        'slug' => 'aurora-estates',
    ]);

    $selectedOwner = User::factory()->admin()->create([
        'organization_id' => $selectedOrganization->id,
        'email' => 'owner@aurora.test',
    ]);

    $selectedOrganization->forceFill([
        'owner_user_id' => $selectedOwner->id,
    ])->save();

    $selectedSubscription = Subscription::factory()->for($selectedOrganization)->create([
        'plan' => SubscriptionPlan::PROFESSIONAL,
        'status' => SubscriptionStatus::ACTIVE,
        'is_trial' => false,
    ]);

    SubscriptionPayment::factory()->create([
        'organization_id' => $selectedOrganization->id,
        'subscription_id' => $selectedSubscription->id,
        'duration' => SubscriptionDuration::QUARTERLY,
        'amount' => 150.00,
        'currency' => 'EUR',
        'paid_at' => now()->subDay(),
    ]);

    $selectedBuilding = Building::factory()->create([
        'organization_id' => $selectedOrganization->id,
    ]);

    Property::factory()->create([
        'organization_id' => $selectedOrganization->id,
        'building_id' => $selectedBuilding->id,
    ]);

    User::factory()->count(2)->manager()->create([
        'organization_id' => $selectedOrganization->id,
    ]);

    $secondOrganization = Organization::factory()->create([
        'name' => 'Beacon Holdings',
        'slug' => 'beacon-holdings',
    ]);

    $secondOwner = User::factory()->admin()->create([
        'organization_id' => $secondOrganization->id,
        'email' => 'owner@beacon.test',
    ]);

    $secondOrganization->forceFill([
        'owner_user_id' => $secondOwner->id,
    ])->save();

    $secondSubscription = Subscription::factory()->for($secondOrganization)->create([
        'plan' => SubscriptionPlan::BASIC,
        'status' => SubscriptionStatus::ACTIVE,
        'is_trial' => false,
    ]);

    SubscriptionPayment::factory()->create([
        'organization_id' => $secondOrganization->id,
        'subscription_id' => $secondSubscription->id,
        'duration' => SubscriptionDuration::MONTHLY,
        'amount' => 99.00,
        'currency' => 'EUR',
        'paid_at' => now()->subDays(2),
    ]);

    User::factory()->manager()->create([
        'organization_id' => $secondOrganization->id,
    ]);

    $excludedOrganization = Organization::factory()->create([
        'name' => 'Clearwater Estates',
        'slug' => 'clearwater-estates',
    ]);

    $path = app(ExportOrganizationsSummaryAction::class)->handle(
        collect([$selectedOrganization, $secondOrganization]),
        ['name', 'owner.email', 'status', 'currentSubscription.plan', 'properties_count', 'users_count', 'mrr_display', 'created_at'],
    );

    $rows = array_map('str_getcsv', file($path, FILE_IGNORE_NEW_LINES) ?: []);

    expect($rows[0])->toBe([
        'Name',
        'Owner email',
        'Status',
        'Plan',
        'Properties',
        'Users',
        'MRR',
        'Created',
    ])->and($rows)->toHaveCount(3)
        ->and($rows[1][0])->toBe('Aurora Estates')
        ->and($rows[1][1])->toBe('owner@aurora.test')
        ->and($rows[1][4])->toBe('1')
        ->and($rows[1][5])->toBe('3')
        ->and($rows[1][6])->toBe('EUR 50.00')
        ->and($rows[2][0])->toBe('Beacon Holdings')
        ->and($rows[2][1])->toBe('owner@beacon.test')
        ->and($rows[2][5])->toBe('2')
        ->and(collect($rows)->flatten())->not->toContain($excludedOrganization->name);

    @unlink($path);
});
