<?php

use App\Models\Building;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Models\MeterReadingAudit;
use App\Models\Organization;
use App\Models\OrganizationActivityLog;
use App\Models\OrganizationInvitation;
use App\Models\Property;
use App\Models\Provider;
use App\Models\Subscription;
use App\Models\Tariff;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('test database seeder seeds all models via factories', function () {
    $this->seed(\Database\Seeders\TestDatabaseSeeder::class);

    expect(Organization::count())->toBeGreaterThan(0)
        ->and(OrganizationInvitation::count())->toBeGreaterThan(0)
        ->and(OrganizationActivityLog::count())->toBeGreaterThan(0)
        ->and(Provider::count())->toBeGreaterThanOrEqual(3)
        ->and(Building::count())->toBeGreaterThan(0)
        ->and(Property::count())->toBeGreaterThan(0)
        ->and(User::count())->toBeGreaterThan(0)
        ->and(Subscription::count())->toBeGreaterThan(0)
        ->and(Tenant::count())->toBeGreaterThan(0)
        ->and(Meter::count())->toBeGreaterThan(0)
        ->and(MeterReading::count())->toBeGreaterThan(0)
        ->and(MeterReadingAudit::count())->toBeGreaterThan(0)
        ->and(Tariff::count())->toBeGreaterThan(0)
        ->and(Invoice::count())->toBeGreaterThan(0)
        ->and(InvoiceItem::count())->toBeGreaterThan(0);

    $invoice = Invoice::first();
    expect($invoice)->not->toBeNull()
        ->and($invoice->items()->count())->toBeGreaterThan(0);
});
