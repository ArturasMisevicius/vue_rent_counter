<?php

declare(strict_types=1);

use App\Models\Activity;
use App\Models\AuditLog;
use App\Models\Building;
use App\Models\Comment;
use App\Models\EnhancedTask;
use App\Models\IntegrationHealthCheck;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Models\Organization;
use App\Models\OrganizationActivityLog;
use App\Models\PlatformNotification;
use App\Models\PlatformNotificationRecipient;
use App\Models\Property;
use App\Models\Provider;
use App\Models\SecurityViolation;
use App\Models\Subscription;
use App\Models\SystemConfiguration;
use App\Models\SystemHealthMetric;
use App\Models\Tag;
use App\Models\Tariff;
use App\Models\Tenant;
use App\Models\Translation;
use App\Models\User;
use Database\Seeders\ComprehensiveTenantSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('comprehensive tenant seeder creates core graph for tenant 1', function (): void {
    $this->seed(ComprehensiveTenantSeeder::class);

    $admin = User::query()->where('email', 'admin@example.com')->first();

    expect($admin)->not->toBeNull()
        ->and($admin?->tenant_id)->toBe(1)
        ->and(Organization::query()->whereKey(1)->exists())->toBeTrue()
        ->and(Subscription::query()->where('user_id', $admin?->id)->exists())->toBeTrue()
        ->and(Building::query()->where('tenant_id', 1)->count())->toBe(2)
        ->and(Property::query()->where('tenant_id', 1)->count())->toBe(4)
        ->and(Tenant::query()->where('tenant_id', 1)->count())->toBe(2)
        ->and(Meter::query()->where('tenant_id', 1)->count())->toBe(6)
        ->and(MeterReading::query()->where('tenant_id', 1)->count())->toBe(18)
        ->and(Invoice::query()->where('tenant_id', 1)->count())->toBe(4)
        ->and(InvoiceItem::query()->whereHas('invoice', fn ($q) => $q->where('tenant_id', 1))->count())->toBe(12);

    $invoice = Invoice::query()->where('tenant_id', 1)->first();

    expect($invoice)->not->toBeNull()
        ->and($invoice?->tenant)->not->toBeNull()
        ->and($invoice?->items()->count())->toBe(3);
});

test('comprehensive tenant seeder creates auxiliary graph coverage', function (): void {
    $this->seed(ComprehensiveTenantSeeder::class);

    expect(AuditLog::query()->where('tenant_id', 1)->count())->toBeGreaterThan(0)
        ->and(Activity::query()->count())->toBeGreaterThan(0)
        ->and(SystemConfiguration::query()->where('key', 'billing.default_currency')->exists())->toBeTrue()
        ->and(SystemHealthMetric::query()->count())->toBeGreaterThan(0)
        ->and(IntegrationHealthCheck::query()->where('service_name', 'billing-api')->exists())->toBeTrue()
        ->and(SecurityViolation::query()->count())->toBeGreaterThan(0)
        ->and(PlatformNotification::query()->count())->toBeGreaterThan(0)
        ->and(PlatformNotificationRecipient::query()->count())->toBeGreaterThan(0)
        ->and(EnhancedTask::query()->count())->toBeGreaterThan(0)
        ->and(Comment::query()->count())->toBeGreaterThan(0)
        ->and(Tag::query()->where('tenant_id', 1)->count())->toBeGreaterThan(0)
        ->and(Translation::query()->where('group', 'seeding')->count())->toBeGreaterThan(0)
        ->and(OrganizationActivityLog::query()->where('action', 'seeded_comprehensive_dataset')->count())->toBeGreaterThan(0);
});

test('comprehensive tenant seeder is safely re-runnable', function (): void {
    $this->seed(ComprehensiveTenantSeeder::class);
    $this->seed(ComprehensiveTenantSeeder::class);

    expect(User::query()->where('email', 'admin@example.com')->count())->toBe(1)
        ->and(User::query()->where('email', 'manager@example.com')->count())->toBe(1)
        ->and(User::query()->where('email', 'superadmin@example.com')->count())->toBe(1)
        ->and(Organization::query()->whereKey(1)->count())->toBe(1)
        ->and(Provider::query()->where('name', 'Ignitis')->count())->toBe(1)
        ->and(Provider::query()->where('name', 'Vilniaus Vandenys')->count())->toBe(1)
        ->and(Provider::query()->where('name', 'Vilniaus Energija')->count())->toBe(1)
        ->and(Tariff::query()->where('name', 'Ignitis Standard Time-of-Use')->count())->toBe(1)
        ->and(Tariff::query()->where('name', 'VV Standard Water Rates')->count())->toBe(1)
        ->and(Tariff::query()->where('name', 'VE Heating Standard')->count())->toBe(1)
        ->and(Building::query()->where('tenant_id', 1)->count())->toBe(2)
        ->and(Property::query()->where('tenant_id', 1)->count())->toBe(4)
        ->and(Tenant::query()->where('tenant_id', 1)->count())->toBe(2)
        ->and(Meter::query()->where('tenant_id', 1)->count())->toBe(6)
        ->and(Invoice::query()->where('tenant_id', 1)->count())->toBe(4);
});
