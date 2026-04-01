<?php

use App\Enums\ProjectStatus;
use App\Enums\SecurityViolationSeverity;
use App\Enums\SecurityViolationType;
use App\Enums\SubscriptionPlan;
use App\Filament\Support\Superadmin\Dashboard\PlatformDashboardData;
use App\Models\Organization;
use App\Models\Project;
use App\Models\SecurityViolation;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

uses(RefreshDatabase::class);

it('shows the superadmin dashboard metrics', function () {
    $superadmin = User::factory()->superadmin()->create();

    $northwindOwner = User::factory()->create([
        'email' => 'owner@northwind.test',
    ]);

    Organization::factory()->create([
        'name' => 'Northwind Towers',
        'owner_user_id' => $northwindOwner->id,
        'created_at' => now()->subMonth(),
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

    $dashboard = app(PlatformDashboardData::class)->for($superadmin);

    actingAs($superadmin);

    get(route('filament.admin.pages.platform-dashboard'))
        ->assertSuccessful()
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
        ->assertSeeText('Basic')
        ->assertSeeText('Professional')
        ->assertSeeText('Permit Review Retrofit')
        ->assertSeeText($expiringOrganization->name)
        ->assertSeeText($recentOrganization->name)
        ->assertSeeText('Authentication')
        ->assertDontSeeText(__('dashboard.platform_actions.view_all'));
});

it('streams the recent organizations csv export for superadmins', function () {
    $superadmin = User::factory()->superadmin()->create();
    $owner = User::factory()->create([
        'email' => 'owner@aurora.test',
    ]);

    $organization = Organization::factory()->create([
        'name' => 'Aurora Offices',
        'owner_user_id' => $owner->id,
    ]);

    Subscription::factory()
        ->for($organization)
        ->active()
        ->create([
            'plan' => SubscriptionPlan::BASIC,
        ]);

    actingAs($superadmin);

    $response = get(route('filament.admin.pages.platform-dashboard.recent-organizations-export'));

    $response->assertOk();

    ob_start();
    $response->sendContent();
    $csv = (string) ob_get_clean();
    $rows = array_map(
        static fn (string $line): array => str_getcsv($line),
        array_values(array_filter(
            preg_split('/\r\n|\r|\n/', trim($csv)) ?: [],
            static fn (string $line): bool => $line !== '',
        )),
    );

    expect($response->headers->get('content-type'))->toContain('text/csv')
        ->and($rows[0] ?? null)->toBe([__('dashboard.platform_sections.recent_organizations')])
        ->and($rows[1] ?? null)->toBe([
            __('dashboard.platform_recent_organizations.columns.name'),
            __('dashboard.platform_recent_organizations.columns.owner_email'),
            __('dashboard.platform_recent_organizations.columns.plan_type'),
            __('dashboard.platform_recent_organizations.columns.subscription_status'),
            __('dashboard.platform_recent_organizations.columns.properties_count'),
            __('dashboard.platform_recent_organizations.columns.tenants_count'),
            __('dashboard.platform_recent_organizations.columns.date_created'),
        ])
        ->and($csv)->toContain('Aurora Offices')
        ->and($csv)->toContain('owner@aurora.test');
});

it('shows a filtered subscriptions link when more than five organizations are expiring soon', function () {
    $superadmin = User::factory()->superadmin()->create();

    foreach (range(1, 6) as $index) {
        $organization = Organization::factory()->create([
            'name' => "Expiring Org {$index}",
            'created_at' => now()->subDays($index),
        ]);

        Subscription::factory()
            ->for($organization)
            ->active()
            ->create([
                'plan' => SubscriptionPlan::BASIC,
                'expires_at' => now()->addDays($index),
            ]);
    }

    $filteredSubscriptionsUrl = route('filament.admin.resources.subscriptions.index', [
        'tableFilters' => [
            'expiring_soon' => [
                'isActive' => true,
            ],
        ],
    ]);

    actingAs($superadmin);

    get(route('filament.admin.pages.platform-dashboard'))
        ->assertSuccessful()
        ->assertSeeText(__('dashboard.platform_actions.view_all'))
        ->assertSee($filteredSubscriptionsUrl, false);
});

it('keeps the platform dashboard restricted to superadmins', function () {
    $organization = Organization::factory()->create();
    $admin = User::factory()->admin()->create([
        'organization_id' => $organization->id,
    ]);

    actingAs($admin);

    get(route('filament.admin.pages.platform-dashboard'))
        ->assertForbidden();
});
