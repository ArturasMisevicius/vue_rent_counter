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
use Illuminate\Support\Facades\Auth;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('renders the superadmin dashboard component for superadmins', function () {
    $superadmin = seedSuperadminDashboardComponentData();
    $dashboard = app(PlatformDashboardData::class)->for($superadmin);

    $component = Livewire::actingAs($superadmin)
        ->test(SuperadminDashboard::class)
        ->assertSeeText('Dashboard')
        ->assertSeeText('Total Organizations')
        ->assertSeeText('Active Subscriptions')
        ->assertSeeText('Platform Revenue This Month')
        ->assertSeeText('Security Violations (7 Days)')
        ->assertSeeText((string) $dashboard['metrics'][0]['value'])
        ->assertSeeText((string) $dashboard['metrics'][1]['value'])
        ->assertSeeText((string) $dashboard['metrics'][2]['value'])
        ->assertSeeText((string) $dashboard['metrics'][3]['value'])
        ->assertSeeText((string) $dashboard['metrics'][0]['trend'])
        ->assertSeeText('Revenue by Plan — Last 12 Months')
        ->assertSeeText('Subscriptions Expiring in 30 Days')
        ->assertSeeText('Recent Security Violations')
        ->assertSeeText('Recently Created Organizations')
        ->assertSeeText('Export as CSV')
        ->assertSeeText('Organization Name')
        ->assertSeeText('Owner Email')
        ->assertSeeText('Plan Type')
        ->assertSeeText('Subscription Status')
        ->assertSeeText('Number of Properties')
        ->assertSeeText('Number of Tenants')
        ->assertSeeText('Date Created')
        ->assertSeeText((string) $dashboard['recentSecurityViolations'][0]['type'])
        ->assertSeeText('Aurora Offices')
        ->assertSeeText('Harbor Homes')
        ->assertSeeHtml('wire:poll.visible.60s="refreshDashboardOnInterval"')
        ->assertDontSeeText('Organizations · Properties · Managers');

    if ($dashboard['expiringSubscriptions']['has_more']) {
        $component->assertSeeText('View All');
    } else {
        $component->assertDontSeeText('View All');
    }
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

it('refreshes translated superadmin dashboard copy when the shell locale changes', function () {
    $superadmin = seedSuperadminDashboardComponentData();

    $component = Livewire::actingAs($superadmin)
        ->test(SuperadminDashboard::class)
        ->assertSeeText(__('dashboard.platform_metrics.total_organizations', [], 'en'));

    $superadmin->forceFill([
        'locale' => 'lt',
    ])->save();

    Auth::setUser($superadmin->fresh());
    app()->setLocale('lt');

    $component
        ->dispatch('shell-locale-updated')
        ->assertSeeText(__('dashboard.platform_metrics.total_organizations', [], 'lt'))
        ->assertSeeText(__('dashboard.platform_sections.recent_organizations', [], 'lt'));
});

function seedSuperadminDashboardComponentData(): User
{
    $superadmin = User::factory()->superadmin()->create();

    $northwindOwner = User::factory()->create([
        'email' => 'owner@northwind.test',
    ]);

    $northwind = Organization::factory()->create([
        'name' => 'Northwind Towers',
        'owner_user_id' => $northwindOwner->id,
        'created_at' => now()->subMonth(),
    ]);

    User::factory()->tenant()->count(2)->create([
        'organization_id' => $northwind->id,
    ]);

    $auroraOwner = User::factory()->create([
        'email' => 'owner@aurora.test',
    ]);

    $harborOwner = User::factory()->create([
        'email' => 'owner@harbor.test',
    ]);

    $expiringOrganization = Organization::factory()->create([
        'name' => 'Aurora Offices',
        'owner_user_id' => $auroraOwner->id,
        'created_at' => now()->subDays(3),
    ]);

    $recentOrganization = Organization::factory()->create([
        'name' => 'Harbor Homes',
        'owner_user_id' => $harborOwner->id,
        'created_at' => now()->subDay(),
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
            'amount' => 99.00,
            'paid_at' => now(),
        ]);

    SubscriptionPayment::factory()
        ->for($recentOrganization)
        ->for($professionalSubscription)
        ->create([
            'amount' => 199.00,
            'paid_at' => now(),
        ]);

    SubscriptionPayment::factory()
        ->for($expiringOrganization)
        ->for($basicSubscription)
        ->create([
            'amount' => 99.00,
            'paid_at' => now()->subMonth(),
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
