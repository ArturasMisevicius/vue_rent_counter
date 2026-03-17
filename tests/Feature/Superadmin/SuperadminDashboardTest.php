<?php

use App\Enums\SecurityViolationSeverity;
use App\Enums\SecurityViolationType;
use App\Enums\SubscriptionPlan;
use App\Models\Organization;
use App\Models\SecurityViolation;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('shows the superadmin dashboard metrics', function () {
    $superadmin = User::factory()->superadmin()->create();

    Organization::factory()->create([
        'name' => 'Northwind Towers',
    ]);

    $expiringOrganization = Organization::factory()->create([
        'name' => 'Aurora Offices',
    ]);

    $recentOrganization = Organization::factory()->create([
        'name' => 'Harbor Homes',
    ]);

    $basicSubscription = Subscription::factory()
        ->for($expiringOrganization)
        ->active()
        ->create([
            'plan' => SubscriptionPlan::BASIC,
            'expires_at' => now()->addDays(5),
        ]);

    $professionalSubscription = Subscription::factory()
        ->for($recentOrganization)
        ->active()
        ->create([
            'plan' => SubscriptionPlan::PROFESSIONAL,
            'expires_at' => now()->addDays(20),
        ]);

    SubscriptionPayment::factory()
        ->for($expiringOrganization)
        ->for($basicSubscription)
        ->create([
            'amount' => 9900,
            'paid_at' => now(),
        ]);

    SubscriptionPayment::factory()
        ->for($recentOrganization)
        ->for($professionalSubscription)
        ->create([
            'amount' => 19900,
            'paid_at' => now(),
        ]);

    SecurityViolation::factory()->create([
        'organization_id' => $recentOrganization->id,
        'type' => SecurityViolationType::AUTHENTICATION,
        'severity' => SecurityViolationSeverity::HIGH,
        'summary' => 'Repeated failed login attempts',
        'occurred_at' => now()->subHours(2),
    ]);

    SecurityViolation::factory()->create([
        'organization_id' => $recentOrganization->id,
        'type' => SecurityViolationType::SUSPICIOUS_IP,
        'severity' => SecurityViolationSeverity::MEDIUM,
        'summary' => 'Suspicious IP rotation detected',
        'occurred_at' => now()->subDay(),
    ]);

    $this->actingAs($superadmin)
        ->get(route('filament.admin.pages.platform-dashboard'))
        ->assertSuccessful()
        ->assertSeeText('Total Organizations')
        ->assertSeeText('Active Subscriptions')
        ->assertSeeText('Platform Revenue This Month')
        ->assertSeeText('Security Violations (7 Days)')
        ->assertSeeText('Revenue by Plan')
        ->assertSeeText('Expiring Subscriptions')
        ->assertSeeText('Recent Security Violations')
        ->assertSeeText('Recently Created Organizations')
        ->assertSeeText('Basic')
        ->assertSeeText('Professional')
        ->assertSeeText($expiringOrganization->name)
        ->assertSeeText($recentOrganization->name)
        ->assertSeeText('Repeated failed login attempts')
        ->assertSeeText('3')
        ->assertSeeText('2');
});

it('keeps the platform dashboard restricted to superadmins', function () {
    $organization = Organization::factory()->create();
    $admin = User::factory()->admin()->create([
        'organization_id' => $organization->id,
    ]);

    $this->actingAs($admin)
        ->get(route('filament.admin.pages.platform-dashboard'))
        ->assertForbidden();
});
