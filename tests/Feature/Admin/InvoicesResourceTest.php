<?php

use App\Enums\InvoiceStatus;
use App\Models\Building;
use App\Models\Invoice;
use App\Models\Organization;
use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('shows organization-scoped invoice resource pages to admin and manager users', function () {
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

    $invoice = Invoice::factory()
        ->for($organization)
        ->for($property)
        ->for($tenant, 'tenant')
        ->create([
            'invoice_number' => 'INV-300001',
            'status' => InvoiceStatus::FINALIZED,
            'currency' => 'EUR',
            'total_amount' => 145.30,
            'amount_paid' => 20.00,
        ]);

    $otherOrganization = Organization::factory()->create();
    $otherBuilding = Building::factory()->for($otherOrganization)->create();
    $otherProperty = Property::factory()->for($otherOrganization)->for($otherBuilding)->create();
    $otherTenant = User::factory()->tenant()->create([
        'organization_id' => $otherOrganization->id,
    ]);

    $otherInvoice = Invoice::factory()
        ->for($otherOrganization)
        ->for($otherProperty)
        ->for($otherTenant, 'tenant')
        ->create([
            'invoice_number' => 'INV-HIDDEN-001',
        ]);

    $admin = User::factory()->admin()->create([
        'organization_id' => $organization->id,
    ]);

    $manager = User::factory()->manager()->create([
        'organization_id' => $organization->id,
    ]);

    $superadmin = User::factory()->superadmin()->create();

    $this->actingAs($admin)
        ->get(route('filament.admin.resources.invoices.index'))
        ->assertSuccessful()
        ->assertSeeText('Invoices')
        ->assertSeeText($invoice->invoice_number)
        ->assertSeeText($tenant->name)
        ->assertSeeText($property->name)
        ->assertDontSeeText($otherInvoice->invoice_number);

    $this->actingAs($admin)
        ->get(route('filament.admin.resources.invoices.view', $invoice))
        ->assertSuccessful()
        ->assertSeeText('Invoice Details')
        ->assertSeeText($invoice->invoice_number)
        ->assertSeeText($building->name)
        ->assertSeeText('EUR 145.30');

    $this->actingAs($manager)
        ->get(route('filament.admin.resources.invoices.index'))
        ->assertSuccessful()
        ->assertSeeText($invoice->invoice_number);

    $this->actingAs($manager)
        ->get(route('filament.admin.resources.invoices.view', $invoice))
        ->assertSuccessful()
        ->assertSeeText($invoice->invoice_number);

    $this->actingAs($admin)
        ->get(route('filament.admin.resources.invoices.edit', $invoice))
        ->assertSuccessful()
        ->assertSeeText($invoice->invoice_number)
        ->assertSeeText('Amount Paid')
        ->assertSeeText('Payment Reference');

    $this->actingAs($admin)
        ->get(route('filament.admin.resources.invoices.view', $otherInvoice))
        ->assertNotFound();

    $this->actingAs($superadmin)
        ->get(route('filament.admin.resources.invoices.index'))
        ->assertSuccessful()
        ->assertDontSeeText($invoice->invoice_number);
});

it('shows tenants only their own invoices inside the shared panel invoice resource', function () {
    $organization = Organization::factory()->create();
    $building = Building::factory()->for($organization)->create();
    $property = Property::factory()->for($organization)->for($building)->create();
    $tenant = User::factory()->tenant()->create([
        'organization_id' => $organization->id,
        'name' => 'Tenant One',
    ]);
    $otherTenant = User::factory()->tenant()->create([
        'organization_id' => $organization->id,
        'name' => 'Tenant Two',
    ]);

    $invoice = Invoice::factory()
        ->for($organization)
        ->for($property)
        ->for($tenant, 'tenant')
        ->create([
            'invoice_number' => 'INV-TENANT-1',
            'status' => InvoiceStatus::FINALIZED,
        ]);

    $otherInvoice = Invoice::factory()
        ->for($organization)
        ->for($property)
        ->for($otherTenant, 'tenant')
        ->create([
            'invoice_number' => 'INV-TENANT-2',
            'status' => InvoiceStatus::FINALIZED,
        ]);

    $this->actingAs($tenant)
        ->get(route('filament.admin.resources.invoices.index'))
        ->assertSuccessful()
        ->assertSeeText($invoice->invoice_number)
        ->assertDontSeeText($otherInvoice->invoice_number);

    $this->actingAs($tenant)
        ->get(route('filament.admin.resources.invoices.view', $invoice))
        ->assertSuccessful()
        ->assertSeeText($invoice->invoice_number);

    $this->actingAs($tenant)
        ->get(route('filament.admin.resources.invoices.view', $otherInvoice))
        ->assertNotFound();
});
