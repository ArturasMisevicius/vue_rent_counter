<?php

use App\Enums\AuditLogAction;
use App\Enums\SubscriptionPlan;
use App\Enums\SubscriptionStatus;
use App\Filament\Actions\Superadmin\Organizations\ForceOrganizationPlanChangeAction;
use App\Models\AuditLog;
use App\Models\Building;
use App\Models\Organization;
use App\Models\Property;
use App\Models\Subscription;
use App\Models\User;
use App\Notifications\Superadmin\OrganizationPlanChangedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

it('blocks force plan change when org usage exceeds the target plan', function () {
    [$organization] = seedOrganizationWithSubscription(SubscriptionPlan::PROFESSIONAL);

    $building = Building::factory()->create([
        'organization_id' => $organization->id,
    ]);

    Property::factory()->count(11)->create([
        'organization_id' => $organization->id,
        'building_id' => $building->id,
    ]);

    expect(fn () => app(ForceOrganizationPlanChangeAction::class)->handle(
        $organization->fresh(),
        SubscriptionPlan::BASIC,
        'Support downgrade',
    ))->toThrow(ValidationException::class);
});

it('force changes the current plan and notifies the org owner', function () {
    Notification::fake();

    $superadmin = User::factory()->superadmin()->create();
    [$organization, $owner, $subscription] = seedOrganizationWithSubscription(SubscriptionPlan::BASIC);

    $this->actingAs($superadmin);

    $updatedSubscription = app(ForceOrganizationPlanChangeAction::class)->handle(
        $organization->fresh(),
        SubscriptionPlan::PROFESSIONAL,
        'Support upgrade',
    );

    expect($updatedSubscription->fresh()->plan)->toBe(SubscriptionPlan::PROFESSIONAL)
        ->and($updatedSubscription->fresh()->status)->toBe(SubscriptionStatus::ACTIVE)
        ->and($updatedSubscription->fresh()->property_limit_snapshot)->toBe(SubscriptionPlan::PROFESSIONAL->limits()['properties'])
        ->and($updatedSubscription->fresh()->tenant_limit_snapshot)->toBe(SubscriptionPlan::PROFESSIONAL->limits()['tenants']);

    Notification::assertSentTo($owner, OrganizationPlanChangedNotification::class, function (OrganizationPlanChangedNotification $notification, array $channels) use ($organization): bool {
        return $channels === ['database']
            && $notification->organization->is($organization)
            && $notification->oldPlan === SubscriptionPlan::BASIC
            && $notification->newPlan === SubscriptionPlan::PROFESSIONAL
            && $notification->reason === 'Support upgrade';
    });

    $auditLog = AuditLog::query()
        ->where('organization_id', $organization->id)
        ->where('action', AuditLogAction::UPDATED)
        ->latest('id')
        ->first();

    expect($auditLog)->not->toBeNull()
        ->and($auditLog?->actor_user_id)->toBe($superadmin->id)
        ->and($auditLog?->metadata)->toMatchArray([
            'reason' => 'Support upgrade',
            'old_plan' => SubscriptionPlan::BASIC->value,
            'new_plan' => SubscriptionPlan::PROFESSIONAL->value,
        ]);
});

function seedOrganizationWithSubscription(SubscriptionPlan $plan): array
{
    $organization = Organization::factory()->create([
        'name' => 'Northwind Towers',
    ]);

    $owner = User::factory()->admin()->create([
        'organization_id' => $organization->id,
        'name' => 'Olivia Owner',
        'email' => 'owner@northwind.test',
    ]);

    $organization->forceFill([
        'owner_user_id' => $owner->id,
    ])->save();

    $subscription = Subscription::factory()->for($organization)->active()->create([
        'plan' => $plan,
        'status' => SubscriptionStatus::ACTIVE,
        'is_trial' => false,
        'property_limit_snapshot' => $plan->limits()['properties'],
        'tenant_limit_snapshot' => $plan->limits()['tenants'],
        'meter_limit_snapshot' => $plan->limits()['meters'],
        'invoice_limit_snapshot' => $plan->limits()['invoices'],
    ]);

    return [$organization->fresh(), $owner->fresh(), $subscription->fresh()];
}
