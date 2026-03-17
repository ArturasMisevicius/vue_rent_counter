<?php

use App\Enums\SubscriptionPlan;
use App\Enums\SubscriptionStatus;
use App\Models\Organization;
use App\Models\SecurityViolation;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('shows the superadmin dashboard metrics', function () {
    $superadmin = User::factory()->superadmin()->create();

    $oakResidences = Organization::factory()->create([
        'name' => 'Oak Residences',
        'slug' => 'oak-residences',
        'created_at' => now()->subDays(2),
    ]);
    $pineTower = Organization::factory()->create([
        'name' => 'Pine Tower',
        'slug' => 'pine-tower',
        'created_at' => now()->subDay(),
    ]);
    Organization::factory()->create([
        'name' => 'Cedar Gardens',
        'slug' => 'cedar-gardens',
        'created_at' => now(),
    ]);

    $basicSubscription = Subscription::factory()->create([
        'organization_id' => $oakResidences->id,
        'plan' => SubscriptionPlan::BASIC,
        'plan_name_snapshot' => SubscriptionPlan::BASIC->label(),
        'limits_snapshot' => SubscriptionPlan::BASIC->limitsSnapshot(),
        'status' => SubscriptionStatus::ACTIVE,
        'is_trial' => false,
        'expires_at' => now()->addDays(5),
    ]);

    $professionalSubscription = Subscription::factory()->create([
        'organization_id' => $pineTower->id,
        'plan' => SubscriptionPlan::PROFESSIONAL,
        'plan_name_snapshot' => SubscriptionPlan::PROFESSIONAL->label(),
        'limits_snapshot' => SubscriptionPlan::PROFESSIONAL->limitsSnapshot(),
        'status' => SubscriptionStatus::ACTIVE,
        'is_trial' => false,
        'expires_at' => now()->addDays(10),
    ]);

    SubscriptionPayment::factory()->create([
        'subscription_id' => $basicSubscription->id,
        'organization_id' => $oakResidences->id,
        'amount' => 9900,
        'paid_at' => now(),
    ]);
    SubscriptionPayment::factory()->create([
        'subscription_id' => $professionalSubscription->id,
        'organization_id' => $pineTower->id,
        'amount' => 24900,
        'paid_at' => now(),
    ]);

    SecurityViolation::factory()->create([
        'organization_id' => $pineTower->id,
        'description' => 'Repeated failed login attempts',
        'occurred_at' => now()->subHours(2),
    ]);
    SecurityViolation::factory()->create([
        'organization_id' => $oakResidences->id,
        'description' => 'Suspicious login location detected',
        'occurred_at' => now()->subHour(),
    ]);

    $this->actingAs($superadmin)
        ->get(route('filament.admin.pages.platform-dashboard'))
        ->assertOk()
        ->assertSeeText('Total Organizations')
        ->assertSeeText('Active Subscriptions')
        ->assertSeeText('Platform Revenue This Month')
        ->assertSeeText('Security Violations (7 Days)')
        ->assertSeeText('Revenue By Plan')
        ->assertSeeText('Basic')
        ->assertSeeText('Professional')
        ->assertSeeText('Expiring Subscriptions')
        ->assertSeeText('Oak Residences')
        ->assertSeeText('Recent Security Violations')
        ->assertSeeText('Repeated failed login attempts')
        ->assertSeeText('Recently Created Organizations')
        ->assertSeeText('Cedar Gardens');
});
