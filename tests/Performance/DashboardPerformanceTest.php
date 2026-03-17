<?php

use App\Enums\InvoiceStatus;
use App\Enums\SecurityViolationSeverity;
use App\Enums\SecurityViolationType;
use App\Enums\SubscriptionPlan;
use App\Models\Building;
use App\Models\Invoice;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Models\Organization;
use App\Models\Property;
use App\Models\PropertyAssignment;
use App\Models\SecurityViolation;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function (): void {
    config()->set('cache.default', 'array');
    Cache::flush();
});

it('keeps the warmed admin dashboard under the query budget', function () {
    [$admin] = seedAdminDashboardPerformanceData();

    $this->actingAs($admin)
        ->get(route('filament.admin.pages.organization-dashboard'))
        ->assertSuccessful();

    DB::flushQueryLog();
    DB::enableQueryLog();

    $this->actingAs($admin)
        ->get(route('filament.admin.pages.organization-dashboard'))
        ->assertSuccessful();

    $queryCount = collect(DB::getQueryLog())
        ->filter(fn (array $query): bool => str_starts_with(strtolower($query['query']), 'select'))
        ->count();

    DB::disableQueryLog();

    expect($queryCount)->toBeLessThan(10);
});

it('keeps the warmed superadmin dashboard under the query budget', function () {
    [$superadmin] = seedSuperadminDashboardPerformanceData();

    $this->actingAs($superadmin)
        ->get(route('filament.admin.pages.platform-dashboard'))
        ->assertSuccessful();

    DB::flushQueryLog();
    DB::enableQueryLog();

    $this->actingAs($superadmin)
        ->get(route('filament.admin.pages.platform-dashboard'))
        ->assertSuccessful();

    $queryCount = collect(DB::getQueryLog())
        ->filter(fn (array $query): bool => str_starts_with(strtolower($query['query']), 'select'))
        ->count();

    DB::disableQueryLog();

    expect($queryCount)->toBeLessThan(15);
});

/**
 * @return array{0: User}
 */
function seedAdminDashboardPerformanceData(): array
{
    $organization = Organization::factory()->create();
    $admin = User::factory()->admin()->create([
        'organization_id' => $organization->id,
    ]);

    Subscription::factory()->for($organization)->active()->create([
        'property_limit_snapshot' => 10,
        'tenant_limit_snapshot' => 25,
        'meter_limit_snapshot' => 50,
        'invoice_limit_snapshot' => 100,
    ]);

    $building = Building::factory()->for($organization)->create();

    $properties = Property::factory()
        ->count(4)
        ->for($organization)
        ->for($building)
        ->create();

    $tenants = User::factory()
        ->count(3)
        ->tenant()
        ->create([
            'organization_id' => $organization->id,
        ]);

    foreach ($tenants as $index => $tenant) {
        PropertyAssignment::factory()
            ->for($organization)
            ->for($properties[$index])
            ->for($tenant, 'tenant')
            ->create();
    }

    foreach ($properties as $index => $property) {
        $tenant = $tenants[$index % $tenants->count()];

        Invoice::factory()
            ->count(2)
            ->for($organization)
            ->for($property)
            ->for($tenant, 'tenant')
            ->sequence(
                [
                    'status' => InvoiceStatus::FINALIZED,
                    'paid_at' => null,
                    'amount_paid' => 0,
                    'due_date' => now()->addDays(7),
                ],
                [
                    'status' => InvoiceStatus::PAID,
                    'paid_at' => now()->subDay(),
                    'amount_paid' => 250,
                    'total_amount' => 250,
                    'due_date' => now()->subDays(3),
                ],
            )
            ->create();

        $meter = Meter::factory()
            ->for($organization)
            ->for($property)
            ->create([
                'name' => 'Meter '.$index,
            ]);

        MeterReading::factory()
            ->for($organization)
            ->for($property)
            ->for($meter)
            ->for($admin, 'submittedBy')
            ->create([
                'reading_date' => now()->subDays(25 + $index)->toDateString(),
            ]);
    }

    return [$admin];
}

/**
 * @return array{0: User}
 */
function seedSuperadminDashboardPerformanceData(): array
{
    $superadmin = User::factory()->superadmin()->create();

    $organizations = Organization::factory()->count(6)->create();

    foreach ($organizations as $index => $organization) {
        $subscription = Subscription::factory()
            ->for($organization)
            ->active()
            ->create([
                'plan' => $index % 2 === 0 ? SubscriptionPlan::BASIC : SubscriptionPlan::PROFESSIONAL,
                'expires_at' => now()->addDays(5 + $index),
            ]);

        SubscriptionPayment::factory()
            ->for($organization)
            ->for($subscription)
            ->create([
                'amount' => 9900 + ($index * 1000),
                'paid_at' => now()->subDays($index),
            ]);

        SecurityViolation::factory()->create([
            'organization_id' => $organization->id,
            'type' => SecurityViolationType::AUTHENTICATION,
            'severity' => $index % 2 === 0 ? SecurityViolationSeverity::HIGH : SecurityViolationSeverity::MEDIUM,
            'summary' => 'Violation '.$index,
            'occurred_at' => now()->subHours($index + 1),
        ]);
    }

    return [$superadmin];
}
