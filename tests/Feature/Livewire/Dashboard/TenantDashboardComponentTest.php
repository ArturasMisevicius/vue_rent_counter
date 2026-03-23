<?php

use App\Enums\InvoiceStatus;
use App\Enums\MeterType;
use App\Filament\Support\Tenant\Portal\TenantHomePresenter;
use App\Livewire\Pages\Dashboard\TenantDashboard;
use App\Models\Building;
use App\Models\Invoice;
use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Support\TenantPortalFactory;

uses(RefreshDatabase::class);

it('renders the tenant dashboard component for tenants', function () {
    $fixture = TenantPortalFactory::new()
        ->withAssignedProperty()
        ->withUnpaidInvoices(1)
        ->withMeters(2)
        ->withReadings()
        ->create();

    $fixture->meters[0]->forceFill(['type' => MeterType::WATER])->save();
    $fixture->meters[1]->forceFill(['type' => MeterType::ELECTRICITY])->save();

    Livewire::actingAs($fixture->user)
        ->test(TenantDashboard::class)
        ->assertSeeText('Outstanding Balance')
        ->assertSeeText('This Month')
        ->assertSeeText('Current Month Consumption')
        ->assertSeeText('Recent Readings')
        ->assertSeeText('Assigned Property')
        ->assertSeeText('Apartment 12')
        ->assertSeeText('123 Garden Street')
        ->assertSeeHtml('wire:poll.120s');
});

it('renders the forbidden experience when an admin tries to render the tenant dashboard component', function () {
    $admin = User::factory()->admin()->create();

    Livewire::actingAs($admin)
        ->test(TenantDashboard::class)
        ->assertStatus(403)
        ->assertSeeText('You do not have permission to view this page')
        ->assertSeeText('403');
});

it('returns the same computed summary payload as the tenant home presenter', function () {
    $fixture = TenantPortalFactory::new()
        ->withAssignedProperty()
        ->withUnpaidInvoices(1)
        ->withMeters(2)
        ->withReadings()
        ->create();

    $component = Livewire::actingAs($fixture->user)->test(TenantDashboard::class);

    expect($component->instance()->summary())
        ->toEqual(app(TenantHomePresenter::class)->for($fixture->user));
});

it('shows an empty-state message when the tenant has no assigned property yet', function () {
    $fixture = TenantPortalFactory::new()->create();

    Livewire::actingAs($fixture->user)
        ->test(TenantDashboard::class)
        ->assertSeeText('No property assigned yet')
        ->assertSeeText('administrator assigns your property and meters');
});

it('scopes outstanding totals to the tenant assigned property', function () {
    $fixture = TenantPortalFactory::new()
        ->withAssignedProperty()
        ->withUnpaidInvoices(1)
        ->create();

    $otherBuilding = Building::factory()
        ->for($fixture->organization)
        ->create();

    $otherProperty = Property::factory()
        ->for($fixture->organization)
        ->for($otherBuilding)
        ->create();

    Invoice::factory()
        ->for($fixture->organization)
        ->for($otherProperty)
        ->for($fixture->user, 'tenant')
        ->create([
            'invoice_number' => 'UNPAID-OUTSIDE-PROPERTY-001',
            'status' => InvoiceStatus::FINALIZED,
            'total_amount' => 999.00,
            'amount_paid' => 0,
            'due_date' => now()->addDays(14)->toDateString(),
            'finalized_at' => now()->subDays(1),
        ]);

    $component = Livewire::actingAs($fixture->user)->test(TenantDashboard::class);

    expect((float) $component->instance()->summary()['outstanding_total'])
        ->toBe((float) $fixture->invoices->sum(fn (Invoice $invoice): float => (float) $invoice->outstanding_balance));
});
