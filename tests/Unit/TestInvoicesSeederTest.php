<?php

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Tenant;
use Carbon\Carbon;
use Database\Seeders\ProvidersSeeder;
use Database\Seeders\TestBuildingsSeeder;
use Database\Seeders\TestInvoicesSeeder;
use Database\Seeders\TestMeterReadingsSeeder;
use Database\Seeders\TestMetersSeeder;
use Database\Seeders\TestPropertiesSeeder;
use Database\Seeders\TestTariffsSeeder;
use Database\Seeders\TestTenantsSeeder;
use Database\Seeders\UsersSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Seed all prerequisite data
    $this->seed(ProvidersSeeder::class);
    $this->seed(UsersSeeder::class);
    $this->seed(TestBuildingsSeeder::class);
    $this->seed(TestPropertiesSeeder::class);
    $this->seed(TestTenantsSeeder::class);
    $this->seed(TestMetersSeeder::class);
    $this->seed(TestMeterReadingsSeeder::class);
    $this->seed(TestTariffsSeeder::class);
});

test('seeder creates invoices for all tenants', function () {
    $tenantCount = Tenant::count();
    
    $this->seed(TestInvoicesSeeder::class);
    
    // Each tenant should have 3 invoices (draft, finalized, paid)
    expect(Invoice::count())->toBe($tenantCount * 3);
});

test('seeder creates draft invoice for current month', function () {
    $this->seed(TestInvoicesSeeder::class);
    
    $draftInvoices = Invoice::where('status', InvoiceStatus::DRAFT)->get();
    
    expect($draftInvoices->count())->toBeGreaterThan(0);
    
    foreach ($draftInvoices as $invoice) {
        expect($invoice->billing_period_start->format('Y-m'))
            ->toBe(Carbon::now()->format('Y-m'));
        expect($invoice->finalized_at)->toBeNull();
    }
});

test('seeder creates finalized invoice for last month', function () {
    $this->seed(TestInvoicesSeeder::class);
    
    $finalizedInvoices = Invoice::where('status', InvoiceStatus::FINALIZED)->get();
    
    expect($finalizedInvoices->count())->toBeGreaterThan(0);
    
    foreach ($finalizedInvoices as $invoice) {
        expect($invoice->billing_period_start->format('Y-m'))
            ->toBe(Carbon::now()->subMonth()->format('Y-m'));
        expect($invoice->finalized_at)->not->toBeNull();
    }
});

test('seeder creates paid invoice for 2 months ago', function () {
    $this->seed(TestInvoicesSeeder::class);
    
    $paidInvoices = Invoice::where('status', InvoiceStatus::PAID)->get();
    
    expect($paidInvoices->count())->toBeGreaterThan(0);
    
    foreach ($paidInvoices as $invoice) {
        expect($invoice->billing_period_start->format('Y-m'))
            ->toBe(Carbon::now()->subMonths(2)->format('Y-m'));
        expect($invoice->finalized_at)->not->toBeNull();
    }
});

test('seeder creates invoice items with realistic consumption', function () {
    $this->seed(TestInvoicesSeeder::class);
    
    $items = InvoiceItem::all();
    
    expect($items->count())->toBeGreaterThan(0);
    
    foreach ($items as $item) {
        expect($item->quantity)->toBeGreaterThan(0);
        expect($item->unit_price)->toBeGreaterThan(0);
        expect($item->total)->toBeGreaterThan(0);
        expect($item->description)->not->toBeEmpty();
        expect($item->unit)->not->toBeEmpty();
    }
});

test('seeder snapshots tariff rates in invoice items', function () {
    $this->seed(TestInvoicesSeeder::class);
    
    $items = InvoiceItem::all();
    
    foreach ($items as $item) {
        expect($item->meter_reading_snapshot)->toBeArray();
        expect($item->meter_reading_snapshot)->toHaveKey('meter_id');
        expect($item->meter_reading_snapshot)->toHaveKey('meter_serial');
        expect($item->meter_reading_snapshot)->toHaveKey('current_reading');
        expect($item->meter_reading_snapshot)->toHaveKey('previous_reading');
        expect($item->meter_reading_snapshot)->toHaveKey('reading_date');
    }
});

test('seeder calculates total amount from invoice items', function () {
    $this->seed(TestInvoicesSeeder::class);
    
    $invoices = Invoice::with('items')->get();
    
    foreach ($invoices as $invoice) {
        $calculatedTotal = $invoice->items->sum('total');
        // Use tolerance for floating point comparison
        expect(abs((float) $invoice->total_amount - (float) $calculatedTotal))->toBeLessThan(0.01);
    }
});

test('seeder creates electricity items with day and night zones', function () {
    $this->seed(TestInvoicesSeeder::class);
    
    $electricityItems = InvoiceItem::where('description', 'like', 'Electricity%')->get();
    
    expect($electricityItems->count())->toBeGreaterThan(0);
    
    $dayItems = $electricityItems->filter(fn($item) => str_contains($item->description, 'Day'));
    $nightItems = $electricityItems->filter(fn($item) => str_contains($item->description, 'Night'));
    
    expect($dayItems->count())->toBeGreaterThan(0);
    expect($nightItems->count())->toBeGreaterThan(0);
});

test('seeder creates water and heating items', function () {
    $this->seed(TestInvoicesSeeder::class);
    
    $waterItems = InvoiceItem::where('description', 'like', '%Water%')->get();
    $heatingItems = InvoiceItem::where('description', 'Heating')->get();
    
    expect($waterItems->count())->toBeGreaterThan(0);
    expect($heatingItems->count())->toBeGreaterThan(0);
});
