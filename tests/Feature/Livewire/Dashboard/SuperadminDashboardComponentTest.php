<?php

use App\Enums\SecurityViolationSeverity;
use App\Enums\SecurityViolationType;
use App\Enums\SubscriptionPlan;
use App\Filament\Support\Superadmin\Dashboard\PlatformDashboardData;
use App\Livewire\Pages\Dashboard\SuperadminDashboard;
use App\Models\Organization;
use App\Models\SecurityViolation;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('renders the superadmin dashboard component for superadmins', function () {
    $superadmin = seedSuperadminDashboardComponentData();

    Livewire::actingAs($superadmin)
        ->test(SuperadminDashboard::class)
        ->assertSeeText('Total Organizations')
        ->assertSeeText('Active Subscriptions')
        ->assertSeeText('Platform Revenue This Month')
        ->assertSeeText('Security Violations (7 Days)')
        ->assertSeeText('Revenue by Plan')
        ->assertSeeText('Expiring Subscriptions')
        ->assertSeeText('Recent Security Violations')
        ->assertSeeText('Recently Created Organizations')
        ->assertSeeText('Total Properties')
        ->assertSeeText('Active Managers')
        ->assertSeeText('Organizations · Properties · Managers')
        ->assertSeeText('Repeated failed login attempts')
        ->assertSeeText('Aurora Offices')
        ->assertSeeText('Harbor Homes')
        ->assertSeeHtml('wire:poll.60s');
});

it('renders the forbidden experience when a workspace admin tries to render the superadmin dashboard component', function () {
    $admin = User::factory()->admin()->create();

    Livewire::actingAs($admin)
        ->test(SuperadminDashboard::class)
        ->assertStatus(403)
        ->assertSeeText('You do not have permission to view this page')
        ->assertSeeText('403');
});

it('returns the same computed dashboard payload as the platform dashboard data service', function () {
    $superadmin = seedSuperadminDashboardComponentData();

    $component = Livewire::actingAs($superadmin)->test(SuperadminDashboard::class);

    expect($component->instance()->dashboard())
        ->toEqual(app(PlatformDashboardData::class)->for($superadmin));
});

function seedSuperadminDashboardComponentData(): User
{
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

    return $superadmin;
}
