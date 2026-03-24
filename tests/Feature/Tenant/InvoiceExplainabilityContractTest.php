<?php

declare(strict_types=1);

use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Models\Building;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\Organization;
use App\Models\Property;
use App\Models\PropertyAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('keeps tenant history, admin invoice view, and invoice pdf aligned to one invoice breakdown', function (): void {
    $organization = Organization::factory()->create();
    $building = Building::factory()->for($organization)->create([
        'name' => 'North Hall',
    ]);
    $property = Property::factory()->for($organization)->for($building)->create([
        'name' => 'A-12',
    ]);
    $tenant = User::factory()->tenant()->create([
        'organization_id' => $organization->id,
        'name' => 'Taylor Tenant',
    ]);
    $admin = User::factory()->admin()->create([
        'organization_id' => $organization->id,
    ]);

    PropertyAssignment::factory()
        ->for($organization)
        ->for($property)
        ->for($tenant, 'tenant')
        ->create([
            'assigned_at' => now()->subMonth(),
            'unassigned_at' => null,
        ]);

    $invoice = Invoice::factory()
        ->for($organization)
        ->for($property)
        ->for($tenant, 'tenant')
        ->create([
            'invoice_number' => 'INV-EXPLAIN-001',
            'status' => InvoiceStatus::PARTIALLY_PAID,
            'currency' => 'EUR',
            'total_amount' => 145.30,
            'amount_paid' => 20.00,
            'paid_amount' => 20.00,
            'billing_period_start' => '2026-01-01',
            'billing_period_end' => '2026-01-31',
            'due_date' => '2026-02-14',
            'items' => [
                [
                    'description' => 'Water usage',
                    'quantity' => '12.000',
                    'unit' => 'm3',
                    'unit_price' => '2.5000',
                    'total' => '30.00',
                ],
                [
                    'description' => 'Service charge',
                    'quantity' => '1.000',
                    'unit' => null,
                    'unit_price' => '115.3000',
                    'total' => '115.30',
                ],
            ],
        ]);

    InvoicePayment::query()->create([
        'invoice_id' => $invoice->id,
        'organization_id' => $organization->id,
        'recorded_by_user_id' => $admin->id,
        'amount' => '20.00',
        'method' => PaymentMethod::BANK_TRANSFER,
        'reference' => 'PAY-20',
        'paid_at' => now()->subDay(),
        'notes' => 'Partial settlement',
    ]);

    $this->actingAs($tenant)
        ->get(route('filament.admin.pages.tenant-invoice-history'))
        ->assertSuccessful()
        ->assertSeeText($invoice->invoice_number)
        ->assertSeeText('Water usage')
        ->assertSeeText('Service charge')
        ->assertSeeText('PAY-20')
        ->assertSeeText('EUR 20.00')
        ->assertSeeText('EUR 125.30');

    $this->actingAs($admin)
        ->get(route('filament.admin.resources.invoices.view', $invoice))
        ->assertSuccessful()
        ->assertSeeText($invoice->invoice_number)
        ->assertSeeText('Water usage')
        ->assertSeeText('Service charge')
        ->assertSeeText('PAY-20')
        ->assertSeeText('EUR 20.00')
        ->assertSeeText('EUR 125.30');

    $pdf = $this->actingAs($tenant)
        ->get(route('tenant.invoices.download', $invoice))
        ->assertDownload('inv-explain-001.pdf')
        ->streamedContent();

    expect($pdf)
        ->toContain('INV-EXPLAIN-001')
        ->toContain('Water usage')
        ->toContain('Service charge')
        ->toContain('EUR 20.00');
});
