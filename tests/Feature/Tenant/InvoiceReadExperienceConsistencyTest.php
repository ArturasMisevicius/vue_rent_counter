<?php

declare(strict_types=1);

use App\Enums\InvoiceStatus;
use App\Livewire\Tenant\InvoiceHistory;
use App\Models\Building;
use App\Models\Invoice;
use App\Models\Organization;
use App\Models\Property;
use App\Models\PropertyAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('keeps the tenant invoice resource scoped to the same workspace invoices as tenant invoice history', function (): void {
    $organization = Organization::factory()->create();
    $building = Building::factory()->for($organization)->create();
    $currentProperty = Property::factory()->for($organization)->for($building)->create([
        'name' => 'Current Property',
    ]);
    $previousProperty = Property::factory()->for($organization)->for($building)->create([
        'name' => 'Previous Property',
    ]);

    $tenant = User::factory()->tenant()->create([
        'organization_id' => $organization->id,
        'name' => 'Taylor Tenant',
    ]);

    PropertyAssignment::factory()
        ->for($organization)
        ->for($currentProperty)
        ->for($tenant, 'tenant')
        ->create([
            'assigned_at' => now()->subMonth(),
            'unassigned_at' => null,
        ]);

    $currentInvoice = Invoice::factory()
        ->for($organization)
        ->for($currentProperty)
        ->for($tenant, 'tenant')
        ->create([
            'invoice_number' => 'INV-CURRENT-001',
            'status' => InvoiceStatus::FINALIZED,
        ]);

    $previousInvoice = Invoice::factory()
        ->for($organization)
        ->for($previousProperty)
        ->for($tenant, 'tenant')
        ->create([
            'invoice_number' => 'INV-PREVIOUS-001',
            'status' => InvoiceStatus::FINALIZED,
        ]);

    $historyInvoiceNumbers = collect(
        Livewire::actingAs($tenant)
            ->test(InvoiceHistory::class)
            ->instance()
            ->invoices
            ->items(),
    )->pluck('invoice_number')->all();

    expect($historyInvoiceNumbers)->toBe([
        $currentInvoice->invoice_number,
    ]);

    $this->actingAs($tenant)
        ->get(route('filament.admin.resources.invoices.index'))
        ->assertSuccessful()
        ->assertSeeText($currentInvoice->invoice_number)
        ->assertDontSee('>'.$previousInvoice->invoice_number.'<', false);

    $this->actingAs($tenant)
        ->get(route('filament.admin.resources.invoices.view', $currentInvoice))
        ->assertSuccessful()
        ->assertSeeText($currentInvoice->invoice_number);

    $this->actingAs($tenant)
        ->get(route('filament.admin.resources.invoices.view', $previousInvoice))
        ->assertNotFound();
});
