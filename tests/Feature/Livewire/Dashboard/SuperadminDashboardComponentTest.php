<?php

use App\Enums\ProjectStatus;
use App\Enums\SecurityViolationSeverity;
use App\Enums\SecurityViolationType;
use App\Enums\SubscriptionPlan;
use App\Filament\Support\Superadmin\Dashboard\PlatformDashboardData;
use App\Livewire\Pages\Dashboard\SuperadminDashboard;
use App\Models\Organization;
use App\Models\Project;
use App\Models\SecurityViolation;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('renders the superadmin dashboard component for superadmins', function () {
    $superadmin = seedSuperadminDashboardComponentData();
    $dashboard = app(PlatformDashboardData::class)->for($superadmin);

    Auth::login($superadmin);

    $component = Livewire::test(SuperadminDashboard::class)
        ->assertSeeText(__('dashboard.title'))
        ->assertSeeText(__('dashboard.platform_metrics.total_organizations'))
        ->assertSeeText(__('dashboard.platform_metrics.active_subscriptions'))
        ->assertSeeText(__('dashboard.platform_metrics.platform_revenue_this_month'))
        ->assertSeeText(__('dashboard.platform_metrics.security_violations_last_7_days'))
        ->assertSeeText((string) $dashboard['metrics'][0]['value'])
        ->assertSeeText((string) $dashboard['metrics'][1]['value'])
        ->assertSeeText((string) $dashboard['metrics'][2]['value'])
        ->assertSeeText((string) $dashboard['metrics'][3]['value'])
        ->assertSeeText((string) $dashboard['metrics'][0]['trend'])
        ->assertSeeText(__('dashboard.platform_sections.revenue_by_plan'))
        ->assertSeeText(__('dashboard.platform_sections.expiring_subscriptions'))
        ->assertSeeText(__('dashboard.platform_sections.stalled_projects'))
        ->assertSeeText(__('dashboard.platform_sections.recent_security_violations'))
        ->assertSeeText(__('dashboard.platform_sections.recent_organizations'))
        ->assertSeeText(__('dashboard.platform_actions.export_csv'))
        ->assertSeeText(__('dashboard.platform_recent_organizations.columns.name'))
        ->assertSeeText(__('dashboard.platform_recent_organizations.columns.owner_email'))
        ->assertSeeText(__('dashboard.platform_recent_organizations.columns.plan_type'))
        ->assertSeeText(__('dashboard.platform_recent_organizations.columns.subscription_status'))
        ->assertSeeText(__('dashboard.platform_recent_organizations.columns.properties_count'))
        ->assertSeeText(__('dashboard.platform_recent_organizations.columns.tenants_count'))
        ->assertSeeText(__('dashboard.platform_recent_organizations.columns.date_created'))
        ->assertSeeText('Permit Review Retrofit')
        ->assertDontSeeText('Fresh Access Review')
        ->assertSeeText((string) $dashboard['recentSecurityViolations'][0]['type'])
        ->assertSeeText('Aurora Offices')
        ->assertSeeText('Harbor Homes')
        ->assertSeeHtml('wire:poll.visible.60s="refreshDashboardOnInterval"')
        ->assertDontSeeText('Organizations · Properties · Managers');

    if ($dashboard['expiringSubscriptions']['has_more']) {
        $component->assertSeeText(__('dashboard.platform_actions.view_all'));
    } else {
        $component->assertDontSeeText(__('dashboard.platform_actions.view_all'));
    }
});

it('renders the forbidden experience when a workspace admin tries to render the superadmin dashboard component', function () {
    $admin = User::factory()->admin()->create();

    Auth::login($admin);

    Livewire::test(SuperadminDashboard::class)
        ->assertStatus(403)
        ->assertSeeText('You do not have permission to view this page')
        ->assertSeeText('403');
});

it('returns the same computed dashboard payload as the platform dashboard data service', function () {
    $superadmin = seedSuperadminDashboardComponentData();

    Auth::login($superadmin);

    $component = Livewire::test(SuperadminDashboard::class);

    expect($component->instance()->dashboard())
        ->toEqual(app(PlatformDashboardData::class)->for($superadmin));
});

it('includes only stalled on-hold projects whose hold reason is older than thirty days', function () {
    $superadmin = seedSuperadminDashboardComponentData();

    $dashboard = app(PlatformDashboardData::class)->for($superadmin);

    expect($dashboard['stalledProjects']['rows'])
        ->toHaveCount(1)
        ->and($dashboard['stalledProjects']['rows'][0])
        ->toMatchArray([
            'name' => 'Permit Review Retrofit',
            'organization' => 'Cedar Point Offices',
        ])
        ->and($dashboard['stalledProjects']['rows'][0]['stale_age'])
        ->not->toBe('');
});

it('excludes on-hold projects whose hold reason was updated exactly thirty days ago', function () {
    Carbon::setTestNow(now()->startOfSecond());

    $superadmin = seedSuperadminDashboardComponentData();

    $organization = Organization::factory()->create([
        'name' => 'Boundary Hold Org',
    ]);

    Project::factory()->create([
        'organization_id' => $organization->id,
        'name' => 'Exact Boundary Hold',
        'status' => ProjectStatus::ON_HOLD,
        'metadata' => [
            'on_hold_reason' => 'Boundary check',
            'on_hold_started_at' => now()->subDays(30)->toDateTimeString(),
            'on_hold_reason_updated_at' => now()->subDays(30)->toDateTimeString(),
        ],
    ]);

    $dashboard = app(PlatformDashboardData::class)->for($superadmin);

    expect(collect($dashboard['stalledProjects']['rows'])->pluck('name'))
        ->not->toContain('Exact Boundary Hold');

    Carbon::setTestNow();
});

it('refreshes translated superadmin dashboard copy when the shell locale changes', function () {
    $superadmin = seedSuperadminDashboardComponentData();

    Auth::login($superadmin);

    $component = Livewire::test(SuperadminDashboard::class)
        ->assertSeeText(__('dashboard.platform_metrics.total_organizations', [], 'en'));

    $superadmin->forceFill([
        'locale' => 'lt',
    ])->save();

    Auth::setUser($superadmin->fresh());
    app()->setLocale('lt');

    $component
        ->dispatch('shell-locale-updated')
        ->assertSeeText(__('dashboard.platform_metrics.total_organizations', [], 'lt'))
        ->assertSeeText(__('dashboard.platform_sections.stalled_projects', [], 'lt'))
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

    $stalledProjectOrganization = Organization::factory()->create([
        'name' => 'Cedar Point Offices',
        'created_at' => now()->subDays(12),
    ]);

    $freshHoldOrganization = Organization::factory()->create([
        'name' => 'Maple Yard Residences',
        'created_at' => now()->subDays(8),
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

    Project::factory()->create([
        'organization_id' => $stalledProjectOrganization->id,
        'name' => 'Permit Review Retrofit',
        'status' => ProjectStatus::ON_HOLD,
        'metadata' => [
            'on_hold_reason' => 'Waiting for permit approval',
            'on_hold_started_at' => now()->subDays(45)->toDateTimeString(),
            'on_hold_reason_updated_at' => now()->subDays(45)->toDateTimeString(),
        ],
    ]);

    Project::factory()->create([
        'organization_id' => $freshHoldOrganization->id,
        'name' => 'Fresh Access Review',
        'status' => ProjectStatus::ON_HOLD,
        'metadata' => [
            'on_hold_reason' => 'Awaiting contractor confirmation',
            'on_hold_started_at' => now()->subDays(10)->toDateTimeString(),
            'on_hold_reason_updated_at' => now()->subDays(10)->toDateTimeString(),
        ],
    ]);

    return $superadmin;
}
