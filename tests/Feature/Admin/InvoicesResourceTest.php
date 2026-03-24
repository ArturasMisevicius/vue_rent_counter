<?php

use App\Enums\InvoiceStatus;
use App\Filament\Resources\Invoices\Pages\CreateInvoice;
use App\Filament\Resources\Invoices\Pages\ListInvoices;
use App\Filament\Resources\Invoices\Pages\ViewInvoice;
use App\Models\Building;
use App\Models\Invoice;
use App\Models\InvoiceEmailLog;
use App\Models\InvoicePayment;
use App\Models\InvoiceReminderLog;
use App\Models\Organization;
use App\Models\Property;
use App\Models\PropertyAssignment;
use App\Models\User;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

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
        ->get(route('filament.admin.resources.invoices.create'))
        ->assertSuccessful()
        ->assertSeeText('New Invoice')
        ->assertSeeText('Save as Draft')
        ->assertSeeText('Generate and Finalize')
        ->assertSeeText('Invoice Details')
        ->assertSeeText('Tenant')
        ->assertSeeText('Billing Period From')
        ->assertSeeText('Billing Period To');

    $this->actingAs($admin)
        ->get(route('filament.admin.resources.invoices.view', $invoice))
        ->assertSuccessful()
        ->assertSeeText($invoice->invoice_number)
        ->assertSeeText('Charges')
        ->assertSeeText('Payment History')
        ->assertSeeText('Email History')
        ->assertSeeText($property->name)
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
        ->assertSeeText($invoice->invoice_number)
        ->assertSeeText($otherInvoice->invoice_number);
});

it('shows tenants only their current-workspace invoices inside the shared panel invoice resource', function () {
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

    PropertyAssignment::factory()
        ->for($organization)
        ->for($property)
        ->for($tenant, 'tenant')
        ->create([
            'assigned_at' => now()->subWeek(),
            'unassigned_at' => null,
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

it('shows organization context on the invoices list for superadmins while keeping admins scoped', function () {
    $organizationA = Organization::factory()->create([
        'name' => 'Northwind Estates',
    ]);
    $organizationB = Organization::factory()->create([
        'name' => 'Aurora Towers',
    ]);

    $buildingA = Building::factory()->for($organizationA)->create();
    $buildingB = Building::factory()->for($organizationB)->create();

    $propertyA = Property::factory()->for($organizationA)->for($buildingA)->create([
        'name' => 'A-12',
    ]);
    $propertyB = Property::factory()->for($organizationB)->for($buildingB)->create([
        'name' => 'B-24',
    ]);

    $tenantA = User::factory()->tenant()->create([
        'organization_id' => $organizationA->id,
        'name' => 'Taylor Tenant',
    ]);
    $tenantB = User::factory()->tenant()->create([
        'organization_id' => $organizationB->id,
        'name' => 'Jordan Tenant',
    ]);

    $invoiceA = Invoice::factory()->for($organizationA)->for($propertyA)->for($tenantA, 'tenant')->create([
        'invoice_number' => 'INV-NORTH-001',
        'status' => InvoiceStatus::FINALIZED,
    ]);
    $invoiceB = Invoice::factory()->for($organizationB)->for($propertyB)->for($tenantB, 'tenant')->create([
        'invoice_number' => 'INV-AURORA-001',
        'status' => InvoiceStatus::PAID,
    ]);

    $admin = User::factory()->admin()->create([
        'organization_id' => $organizationA->id,
    ]);
    $superadmin = User::factory()->superadmin()->create();

    $this->actingAs($admin)
        ->get(route('filament.admin.resources.invoices.index'))
        ->assertSuccessful()
        ->assertSeeText('Invoices')
        ->assertSeeText($invoiceA->invoice_number)
        ->assertDontSeeText($invoiceB->invoice_number);

    $this->actingAs($superadmin)
        ->get(route('filament.admin.resources.invoices.index'))
        ->assertSuccessful()
        ->assertSeeText('Invoices')
        ->assertSeeText($invoiceA->invoice_number)
        ->assertSeeText($invoiceB->invoice_number)
        ->assertSeeText($organizationA->name)
        ->assertSeeText($organizationB->name);

    $this->actingAs($superadmin);

    Livewire::test(ListInvoices::class)
        ->assertTableColumnExists('organization.name', fn (TextColumn $column): bool => $column->getLabel() === 'Organization')
        ->assertTableFilterExists('organization', fn (SelectFilter $filter): bool => $filter->getLabel() === 'Organization')
        ->assertTableColumnStateSet('organization.name', $organizationA->name, $invoiceA)
        ->assertTableColumnStateSet('organization.name', $organizationB->name, $invoiceB)
        ->assertTableColumnStateSet('status', InvoiceStatus::FINALIZED, $invoiceA)
        ->assertCanSeeTableRecords([$invoiceA, $invoiceB])
        ->filterTable('organization', (string) $organizationA->getKey())
        ->assertCanSeeTableRecords([$invoiceA])
        ->assertCanNotSeeTableRecords([$invoiceB]);
});

it('exposes the invoices list contract with quick tabs, filters, and status-scoped row actions', function () {
    $organizationA = Organization::factory()->create([
        'name' => 'Northwind Estates',
    ]);
    $organizationB = Organization::factory()->create([
        'name' => 'Aurora Towers',
    ]);

    $buildingA = Building::factory()->for($organizationA)->create([
        'name' => 'North Hall',
    ]);
    $buildingB = Building::factory()->for($organizationB)->create([
        'name' => 'Aurora Block',
    ]);

    $draftProperty = Property::factory()->for($organizationA)->for($buildingA)->create([
        'name' => 'A-12',
    ]);
    $awaitingProperty = Property::factory()->for($organizationA)->for($buildingA)->create([
        'name' => 'A-14',
    ]);
    $overdueProperty = Property::factory()->for($organizationA)->for($buildingA)->create([
        'name' => 'A-16',
    ]);
    $paidProperty = Property::factory()->for($organizationA)->for($buildingA)->create([
        'name' => 'A-18',
    ]);
    $otherProperty = Property::factory()->for($organizationB)->for($buildingB)->create([
        'name' => 'B-24',
    ]);

    $draftTenant = User::factory()->tenant()->create([
        'organization_id' => $organizationA->id,
        'name' => 'Taylor Draft',
    ]);
    $awaitingTenant = User::factory()->tenant()->create([
        'organization_id' => $organizationA->id,
        'name' => 'Jordan Awaiting',
    ]);
    $overdueTenant = User::factory()->tenant()->create([
        'organization_id' => $organizationA->id,
        'name' => 'Casey Overdue',
    ]);
    $paidTenant = User::factory()->tenant()->create([
        'organization_id' => $organizationA->id,
        'name' => 'Morgan Paid',
    ]);
    $otherTenant = User::factory()->tenant()->create([
        'organization_id' => $organizationB->id,
        'name' => 'Riley Other',
    ]);

    $draftInvoice = Invoice::factory()->for($organizationA)->for($draftProperty)->for($draftTenant, 'tenant')->create([
        'invoice_number' => 'INV-DRAFT-001',
        'status' => InvoiceStatus::DRAFT,
        'billing_period_start' => now()->subMonth()->startOfMonth(),
        'billing_period_end' => now()->subMonth()->endOfMonth(),
        'finalized_at' => null,
        'due_date' => now()->addDays(10)->toDateString(),
        'paid_at' => null,
        'total_amount' => 95.50,
        'amount_paid' => 0,
        'paid_amount' => 0,
    ]);

    $awaitingInvoice = Invoice::factory()->for($organizationA)->for($awaitingProperty)->for($awaitingTenant, 'tenant')->create([
        'invoice_number' => 'INV-AWAIT-001',
        'status' => InvoiceStatus::FINALIZED,
        'billing_period_start' => now()->startOfMonth(),
        'billing_period_end' => now()->endOfMonth(),
        'finalized_at' => now()->subDays(2),
        'due_date' => now()->addDays(7)->toDateString(),
        'paid_at' => null,
        'total_amount' => 145.20,
        'amount_paid' => 0,
        'paid_amount' => 0,
    ]);

    $overdueInvoice = Invoice::factory()->for($organizationA)->for($overdueProperty)->for($overdueTenant, 'tenant')->create([
        'invoice_number' => 'INV-OVERDUE-001',
        'status' => InvoiceStatus::FINALIZED,
        'billing_period_start' => now()->subMonths(2)->startOfMonth(),
        'billing_period_end' => now()->subMonths(2)->endOfMonth(),
        'finalized_at' => now()->subMonth(),
        'due_date' => now()->subDays(5)->toDateString(),
        'paid_at' => null,
        'last_reminder_sent_at' => null,
        'total_amount' => 188.80,
        'amount_paid' => 0,
        'paid_amount' => 0,
    ]);

    $paidInvoice = Invoice::factory()->for($organizationA)->for($paidProperty)->for($paidTenant, 'tenant')->create([
        'invoice_number' => 'INV-PAID-001',
        'status' => InvoiceStatus::PAID,
        'billing_period_start' => now()->subMonths(3)->startOfMonth(),
        'billing_period_end' => now()->subMonths(3)->endOfMonth(),
        'finalized_at' => now()->subMonths(2),
        'due_date' => now()->subMonths(2)->addDays(14)->toDateString(),
        'paid_at' => now()->subDays(1),
        'total_amount' => 210.00,
        'amount_paid' => 210.00,
        'paid_amount' => 210.00,
    ]);

    $otherInvoice = Invoice::factory()->for($organizationB)->for($otherProperty)->for($otherTenant, 'tenant')->create([
        'invoice_number' => 'INV-OTHER-001',
        'status' => InvoiceStatus::FINALIZED,
        'billing_period_start' => now()->startOfMonth(),
        'billing_period_end' => now()->endOfMonth(),
        'finalized_at' => now()->subDay(),
        'due_date' => now()->addDays(10)->toDateString(),
    ]);

    $admin = User::factory()->admin()->create([
        'organization_id' => $organizationA->id,
    ]);
    $superadmin = User::factory()->superadmin()->create();

    $this->actingAs($admin)
        ->get(route('filament.admin.resources.invoices.index'))
        ->assertSuccessful()
        ->assertSeeText('Invoices')
        ->assertSeeText('New Invoice')
        ->assertSeeText('Generate Bulk')
        ->assertSeeText('All Invoices')
        ->assertSeeText('Drafts')
        ->assertSeeText('Awaiting Payment')
        ->assertSeeText('Overdue')
        ->assertSee('Search by tenant name')
        ->assertSeeText('Invoice Number')
        ->assertSeeText('Tenant')
        ->assertSeeText('Billing Period')
        ->assertSeeText('Amount')
        ->assertSeeText('Issued Date')
        ->assertSeeText('Paid Date')
        ->assertSeeText($draftInvoice->invoice_number)
        ->assertSeeText($awaitingInvoice->invoice_number)
        ->assertSeeText($overdueInvoice->invoice_number)
        ->assertSeeText($paidInvoice->invoice_number)
        ->assertSeeText($draftTenant->name)
        ->assertSeeText($awaitingProperty->name)
        ->assertDontSeeText($otherInvoice->invoice_number);

    $this->actingAs($superadmin);

    Livewire::test(ListInvoices::class)
        ->assertActionVisible('create')
        ->assertActionVisible('generateBulk')
        ->assertSet('activeTab', 'all')
        ->assertTableColumnExists('organization.name', fn (TextColumn $column): bool => $column->getLabel() === 'Organization')
        ->assertTableColumnExists('invoice_number', fn (TextColumn $column): bool => $column->getLabel() === 'Invoice Number')
        ->assertTableColumnExists('tenant.name', fn (TextColumn $column): bool => $column->getLabel() === 'Tenant')
        ->assertTableColumnExists('billing_period_start', fn (TextColumn $column): bool => $column->getLabel() === 'Billing Period')
        ->assertTableColumnExists('total_amount', fn (TextColumn $column): bool => $column->getLabel() === 'Amount')
        ->assertTableColumnExists('status', fn (TextColumn $column): bool => $column->getLabel() === 'Status')
        ->assertTableColumnExists('finalized_at', fn (TextColumn $column): bool => $column->getLabel() === 'Issued Date')
        ->assertTableColumnExists('paid_at', fn (TextColumn $column): bool => $column->getLabel() === 'Paid Date')
        ->assertTableFilterExists('organization', fn (SelectFilter $filter): bool => $filter->getLabel() === 'Organization')
        ->assertTableFilterExists('status', fn (SelectFilter $filter): bool => $filter->getLabel() === 'Status' && $filter->isMultiple())
        ->assertTableFilterExists('property_id', fn (SelectFilter $filter): bool => $filter->getLabel() === 'Property')
        ->assertTableFilterExists('billing_period', fn (Filter $filter): bool => $filter->getLabel() === 'Billing Period')
        ->assertTableColumnStateSet('organization.name', $organizationA->name, $draftInvoice)
        ->assertTableColumnStateSet('organization.name', $organizationB->name, $otherInvoice)
        ->assertTableColumnStateSet('status', InvoiceStatus::DRAFT, $draftInvoice)
        ->assertTableColumnStateSet('status', InvoiceStatus::FINALIZED, $awaitingInvoice)
        ->assertTableColumnStateSet('status', InvoiceStatus::OVERDUE, $overdueInvoice)
        ->assertTableColumnStateSet('status', InvoiceStatus::PAID, $paidInvoice)
        ->assertCanSeeTableRecords([$draftInvoice, $awaitingInvoice, $overdueInvoice, $paidInvoice, $otherInvoice])
        ->assertTableActionExists('edit', record: $draftInvoice)
        ->assertTableActionExists('finalize', record: $draftInvoice)
        ->assertTableActionExists('delete', record: $draftInvoice)
        ->assertTableActionHidden('view', record: $draftInvoice)
        ->assertTableActionExists('view', record: $awaitingInvoice)
        ->assertTableActionExists('processPayment', record: $awaitingInvoice)
        ->assertTableActionExists('sendEmail', record: $awaitingInvoice)
        ->assertTableActionExists('downloadPdf', record: $awaitingInvoice)
        ->assertTableActionHidden('delete', record: $awaitingInvoice)
        ->assertTableActionExists('sendReminder', record: $overdueInvoice)
        ->assertTableActionExists('processPayment', record: $overdueInvoice)
        ->assertTableActionExists('sendEmail', record: $overdueInvoice)
        ->assertTableActionExists('downloadPdf', record: $overdueInvoice)
        ->assertTableActionExists('view', record: $paidInvoice)
        ->assertTableActionExists('sendEmail', record: $paidInvoice)
        ->assertTableActionExists('downloadPdf', record: $paidInvoice)
        ->assertTableActionHidden('processPayment', record: $paidInvoice)
        ->assertTableActionHidden('sendReminder', record: $paidInvoice)
        ->set('activeTab', 'drafts')
        ->assertCanSeeTableRecords([$draftInvoice])
        ->assertCanNotSeeTableRecords([$awaitingInvoice, $overdueInvoice, $paidInvoice, $otherInvoice])
        ->set('activeTab', 'awaiting_payment')
        ->assertCanSeeTableRecords([$awaitingInvoice, $otherInvoice])
        ->assertCanNotSeeTableRecords([$draftInvoice, $overdueInvoice, $paidInvoice])
        ->set('activeTab', 'overdue')
        ->assertCanSeeTableRecords([$overdueInvoice])
        ->assertCanNotSeeTableRecords([$draftInvoice, $awaitingInvoice, $paidInvoice, $otherInvoice])
        ->set('activeTab', 'all')
        ->filterTable('property_id', (string) $awaitingProperty->getKey())
        ->assertCanSeeTableRecords([$awaitingInvoice])
        ->assertCanNotSeeTableRecords([$draftInvoice, $overdueInvoice, $paidInvoice, $otherInvoice])
        ->resetTableFilters()
        ->filterTable('billing_period', [
            'billing_period_from' => now()->subMonth()->startOfMonth()->toDateString(),
            'billing_period_to' => now()->endOfMonth()->toDateString(),
        ])
        ->assertCanSeeTableRecords([$draftInvoice, $awaitingInvoice, $otherInvoice])
        ->assertCanNotSeeTableRecords([$overdueInvoice, $paidInvoice])
        ->searchTable('Jordan Awaiting')
        ->assertCanSeeTableRecords([$awaitingInvoice])
        ->assertCanNotSeeTableRecords([$draftInvoice, $overdueInvoice, $paidInvoice, $otherInvoice]);
});

it('renders the single invoice create page contract for admins', function () {
    $organization = Organization::factory()->create();
    $building = Building::factory()->for($organization)->create();
    $property = Property::factory()->for($organization)->for($building)->create();
    $tenant = User::factory()->tenant()->create([
        'organization_id' => $organization->id,
        'name' => 'Taylor Tenant',
        'email' => 'taylor@example.test',
    ]);

    PropertyAssignment::factory()
        ->for($organization)
        ->for($property)
        ->for($tenant, 'tenant')
        ->create([
            'assigned_at' => now()->subMonth(),
            'unassigned_at' => null,
        ]);

    $admin = User::factory()->admin()->create([
        'organization_id' => $organization->id,
    ]);

    $this->actingAs($admin)
        ->get(route('filament.admin.resources.invoices.create'))
        ->assertSuccessful()
        ->assertSeeText('New Invoice')
        ->assertSeeText('Save as Draft')
        ->assertSeeText('Generate and Finalize')
        ->assertSeeText('Cancel')
        ->assertSeeText('Invoice Details')
        ->assertSeeText('Tenant')
        ->assertSeeText('Billing Period From')
        ->assertSeeText('Billing Period To');

    $this->actingAs($admin);

    Livewire::test(CreateInvoice::class)
        ->assertFormFieldExists('tenant_user_id', fn (Select $field): bool => $field->getLabel() === 'Tenant')
        ->assertFormFieldExists('billing_period_start', fn (DatePicker $field): bool => $field->getLabel() === 'Billing Period From')
        ->assertFormFieldExists('billing_period_end', fn (DatePicker $field): bool => $field->getLabel() === 'Billing Period To')
        ->assertFormFieldExists('items', fn (Repeater $field): bool => $field->getLabel() === 'Line Items')
        ->assertFormFieldExists('adjustments', fn (Repeater $field): bool => $field->getLabel() === 'Adjustments')
        ->assertFormFieldExists('notes', fn (Textarea $field): bool => $field->getLabel() === 'Invoice Notes');
});

it('generates line items and saves a single invoice draft from the create page', function () {
    $organization = Organization::factory()->create();
    $building = Building::factory()->for($organization)->create();
    $property = Property::factory()->for($organization)->for($building)->create([
        'name' => 'Apartment 4B',
    ]);
    $tenant = User::factory()->tenant()->create([
        'organization_id' => $organization->id,
        'name' => 'Taylor Tenant',
        'email' => 'taylor@example.test',
    ]);

    PropertyAssignment::factory()
        ->for($organization)
        ->for($property)
        ->for($tenant, 'tenant')
        ->create([
            'assigned_at' => now()->subMonths(2),
            'unassigned_at' => null,
        ]);

    $admin = User::factory()->admin()->create([
        'organization_id' => $organization->id,
    ]);

    $this->actingAs($admin);

    $component = Livewire::test(CreateInvoice::class)
        ->fillForm([
            'tenant_user_id' => $tenant->id,
            'billing_period_start' => now()->startOfMonth()->toDateString(),
            'billing_period_end' => now()->endOfMonth()->toDateString(),
        ])
        ->call('generateLineItems')
        ->assertHasNoFormErrors()
        ->assertSet('data.line_items_generated', true)
        ->fillForm([
            'items' => [[
                'description' => 'Water usage',
                'period' => now()->startOfMonth()->format('F Y').' - '.now()->endOfMonth()->format('F Y'),
                'unit' => 'm³',
                'quantity' => '12.00',
                'rate' => '3.50',
                'total' => '42.00',
            ]],
            'adjustments' => [[
                'label' => 'Maintenance fee',
                'amount' => '15.00',
            ]],
            'notes' => 'Draft invoice notes',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $invoice = Invoice::query()
        ->with(['invoiceItems'])
        ->sole();

    $component->assertRedirect(route('filament.admin.resources.invoices.view', $invoice));

    expect($invoice->organization_id)->toBe($organization->id)
        ->and($invoice->property_id)->toBe($property->id)
        ->and($invoice->tenant_user_id)->toBe($tenant->id)
        ->and($invoice->status)->toBe(InvoiceStatus::DRAFT)
        ->and($invoice->notes)->toBe('Draft invoice notes')
        ->and($invoice->total_amount)->toBe('57.00')
        ->and($invoice->invoiceItems)->toHaveCount(2)
        ->and($invoice->invoice_number)->toMatch('/^INV-\d{4}-\d{4}$/');
});

it('can generate and finalize a single invoice directly from the create page', function () {
    $organization = Organization::factory()->create();
    $building = Building::factory()->for($organization)->create();
    $property = Property::factory()->for($organization)->for($building)->create([
        'name' => 'Office 3A',
    ]);
    $tenant = User::factory()->tenant()->create([
        'organization_id' => $organization->id,
        'name' => 'Jordan Tenant',
        'email' => 'jordan@example.test',
    ]);

    PropertyAssignment::factory()
        ->for($organization)
        ->for($property)
        ->for($tenant, 'tenant')
        ->create([
            'assigned_at' => now()->subMonths(3),
            'unassigned_at' => null,
        ]);

    $admin = User::factory()->admin()->create([
        'organization_id' => $organization->id,
    ]);

    $this->actingAs($admin);

    $component = Livewire::test(CreateInvoice::class)
        ->fillForm([
            'tenant_user_id' => $tenant->id,
            'billing_period_start' => now()->addMonth()->startOfMonth()->toDateString(),
            'billing_period_end' => now()->addMonth()->endOfMonth()->toDateString(),
        ])
        ->call('generateLineItems')
        ->assertHasNoFormErrors()
        ->fillForm([
            'items' => [[
                'description' => 'Electricity usage',
                'period' => now()->addMonth()->startOfMonth()->format('F Y').' - '.now()->addMonth()->endOfMonth()->format('F Y'),
                'unit' => 'kWh',
                'quantity' => '18.00',
                'rate' => '4.00',
                'total' => '72.00',
            ]],
            'adjustments' => [[
                'label' => 'Welcome credit',
                'amount' => '-12.00',
            ]],
            'notes' => 'Finalize immediately',
        ])
        ->call('generateAndFinalize')
        ->assertHasNoFormErrors();

    $invoice = Invoice::query()
        ->with(['invoiceItems'])
        ->sole();

    $component->assertRedirect(route('filament.admin.resources.invoices.view', $invoice));

    expect($invoice->status)->toBe(InvoiceStatus::FINALIZED)
        ->and($invoice->finalized_at)->not->toBeNull()
        ->and($invoice->total_amount)->toBe('60.00')
        ->and($invoice->invoiceItems)->toHaveCount(2)
        ->and($invoice->notes)->toBe('Finalize immediately');
});

it('renders the invoice view page contract with alerts histories and status-specific header actions', function () {
    $organization = Organization::factory()->create([
        'name' => 'Northwind Estates',
    ]);
    $building = Building::factory()->for($organization)->create([
        'name' => 'North Hall',
    ]);
    $property = Property::factory()->for($organization)->for($building)->create([
        'name' => 'Apartment 4B',
    ]);
    $tenant = User::factory()->tenant()->create([
        'organization_id' => $organization->id,
        'name' => 'Taylor Tenant',
        'email' => 'taylor@example.test',
    ]);
    $admin = User::factory()->admin()->create([
        'organization_id' => $organization->id,
    ]);

    $draftInvoice = Invoice::factory()
        ->for($organization)
        ->for($property)
        ->for($tenant, 'tenant')
        ->create([
            'invoice_number' => 'INV-DRAFT-VIEW-001',
            'status' => InvoiceStatus::DRAFT,
            'billing_period_start' => now()->startOfMonth(),
            'billing_period_end' => now()->endOfMonth(),
            'total_amount' => '88.20',
            'amount_paid' => '0.00',
            'paid_amount' => '0.00',
            'items' => [[
                'description' => 'Water usage',
                'period' => now()->startOfMonth()->format('F Y').' - '.now()->endOfMonth()->format('F Y'),
                'quantity' => '12.000',
                'unit' => 'm3',
                'unit_price' => '4.1000',
                'total' => '49.20',
            ], [
                'description' => 'Maintenance fee',
                'period' => now()->startOfMonth()->format('F Y').' - '.now()->endOfMonth()->format('F Y'),
                'quantity' => '1.000',
                'unit' => 'month',
                'unit_price' => '39.0000',
                'total' => '39.00',
            ]],
            'snapshot_data' => [[
                'description' => 'Water usage',
                'period' => now()->startOfMonth()->format('F Y').' - '.now()->endOfMonth()->format('F Y'),
                'quantity' => '12.000',
                'unit' => 'm3',
                'unit_price' => '4.1000',
                'total' => '49.20',
            ], [
                'description' => 'Maintenance fee',
                'period' => now()->startOfMonth()->format('F Y').' - '.now()->endOfMonth()->format('F Y'),
                'quantity' => '1.000',
                'unit' => 'month',
                'unit_price' => '39.0000',
                'total' => '39.00',
            ]],
        ]);

    $overdueInvoice = Invoice::factory()
        ->for($organization)
        ->for($property)
        ->for($tenant, 'tenant')
        ->create([
            'invoice_number' => 'INV-OVERDUE-VIEW-001',
            'status' => InvoiceStatus::FINALIZED,
            'billing_period_start' => now()->subMonth()->startOfMonth(),
            'billing_period_end' => now()->subMonth()->endOfMonth(),
            'finalized_at' => now()->subMonth()->endOfDay(),
            'due_date' => now()->subDays(6)->toDateString(),
            'total_amount' => '120.00',
            'amount_paid' => '20.00',
            'paid_amount' => '20.00',
            'payment_reference' => 'INV-REF-7781',
            'items' => [[
                'description' => 'Heating usage',
                'period' => now()->subMonth()->startOfMonth()->format('F Y').' - '.now()->subMonth()->endOfMonth()->format('F Y'),
                'quantity' => '30.000',
                'unit' => 'kWh',
                'unit_price' => '4.0000',
                'total' => '120.00',
            ]],
            'snapshot_data' => [[
                'description' => 'Heating usage',
                'period' => now()->subMonth()->startOfMonth()->format('F Y').' - '.now()->subMonth()->endOfMonth()->format('F Y'),
                'quantity' => '30.000',
                'unit' => 'kWh',
                'unit_price' => '4.0000',
                'total' => '120.00',
            ]],
        ]);

    InvoicePayment::factory()
        ->for($overdueInvoice)
        ->for($organization)
        ->for($admin, 'recordedBy')
        ->create([
            'amount' => '20.00',
            'reference' => 'PAY-7781',
            'paid_at' => now()->subDays(2),
        ]);

    InvoiceEmailLog::factory()
        ->for($overdueInvoice)
        ->for($organization)
        ->for($admin, 'sentBy')
        ->create([
            'recipient_email' => 'taylor@example.test',
            'sent_at' => now()->subDay(),
        ]);

    InvoiceReminderLog::factory()
        ->for($overdueInvoice)
        ->for($organization)
        ->for($admin, 'sentBy')
        ->create([
            'recipient_email' => 'taylor@example.test',
            'sent_at' => now()->subHours(8),
        ]);

    $overdueInvoice->forceFill([
        'last_reminder_sent_at' => now()->subHours(8),
    ])->save();

    $this->actingAs($admin)
        ->get(route('filament.admin.resources.invoices.view', $draftInvoice))
        ->assertSuccessful()
        ->assertSeeText($draftInvoice->invoice_number)
        ->assertSeeText($tenant->name)
        ->assertSeeText('This is a draft invoice. It has not been sent to the tenant. Finalize it when ready.')
        ->assertSeeText('Charges')
        ->assertSeeText('Payment History')
        ->assertSeeText('Email History')
        ->assertSeeText('No payments recorded yet.')
        ->assertSeeText('Invoice not yet sent.')
        ->assertSeeText('Water usage')
        ->assertSeeText('Maintenance fee')
        ->assertSeeText('Subtotal')
        ->assertSeeText('Total');

    $this->actingAs($admin)
        ->get(route('filament.admin.resources.invoices.view', $overdueInvoice))
        ->assertSuccessful()
        ->assertSeeText($overdueInvoice->invoice_number)
        ->assertSeeText('This invoice is overdue.')
        ->assertSeeText('Last reminder sent')
        ->assertSeeText('Heating usage')
        ->assertSeeText('PAY-7781')
        ->assertSeeText('taylor@example.test')
        ->assertSeeText('Payment History')
        ->assertSeeText('Email History');

    $this->actingAs($admin);

    Livewire::test(ViewInvoice::class, ['record' => $draftInvoice->getRouteKey()])
        ->assertActionExists('edit')
        ->assertActionExists('finalize')
        ->assertActionExists('delete')
        ->assertActionDoesNotExist('processPayment')
        ->assertActionDoesNotExist('sendEmail')
        ->assertActionDoesNotExist('sendReminder')
        ->assertActionDoesNotExist('downloadPdf');

    Livewire::test(ViewInvoice::class, ['record' => $overdueInvoice->getRouteKey()])
        ->assertActionDoesNotExist('edit')
        ->assertActionDoesNotExist('finalize')
        ->assertActionDoesNotExist('delete')
        ->assertActionExists('processPayment')
        ->assertActionExists('sendEmail')
        ->assertActionExists('sendReminder')
        ->assertActionExists('downloadPdf');
});

it('records payments and email logs from the invoice view page actions', function () {
    $organization = Organization::factory()->create();
    $building = Building::factory()->for($organization)->create();
    $property = Property::factory()->for($organization)->for($building)->create();
    $tenant = User::factory()->tenant()->create([
        'organization_id' => $organization->id,
        'email' => 'tenant@example.test',
    ]);
    $admin = User::factory()->admin()->create([
        'organization_id' => $organization->id,
    ]);

    $invoice = Invoice::factory()
        ->for($organization)
        ->for($property)
        ->for($tenant, 'tenant')
        ->create([
            'invoice_number' => 'INV-VIEW-ACTION-001',
            'status' => InvoiceStatus::FINALIZED,
            'total_amount' => '145.30',
            'amount_paid' => '0.00',
            'paid_amount' => '0.00',
            'billing_period_start' => now()->startOfMonth(),
            'billing_period_end' => now()->endOfMonth(),
            'items' => [[
                'description' => 'Electricity usage',
                'period' => now()->startOfMonth()->format('F Y').' - '.now()->endOfMonth()->format('F Y'),
                'quantity' => '12.000',
                'unit' => 'kWh',
                'unit_price' => '12.1083',
                'total' => '145.30',
            ]],
            'snapshot_data' => [[
                'description' => 'Electricity usage',
                'period' => now()->startOfMonth()->format('F Y').' - '.now()->endOfMonth()->format('F Y'),
                'quantity' => '12.000',
                'unit' => 'kWh',
                'unit_price' => '12.1083',
                'total' => '145.30',
            ]],
        ]);

    $this->actingAs($admin);

    Livewire::test(ViewInvoice::class, ['record' => $invoice->getRouteKey()])
        ->callAction('processPayment', data: [
            'amount_paid' => '145.30',
            'paid_at' => now()->toDateString(),
            'method' => 'cash',
            'payment_reference' => 'PAY-VIEW-1453',
        ])
        ->assertHasNoActionErrors();

    Livewire::test(ViewInvoice::class, ['record' => $invoice->getRouteKey()])
        ->callAction('sendEmail', data: [
            'recipient_email' => 'tenant@example.test',
            'personal_message' => 'Please review and settle this invoice.',
        ])
        ->assertHasNoActionErrors();

    expect($invoice->fresh()->status)->toBe(InvoiceStatus::PAID)
        ->and(InvoicePayment::query()->where('invoice_id', $invoice->id)->latest('id')->first())
        ->not->toBeNull()
        ->and(InvoiceEmailLog::query()->where('invoice_id', $invoice->id)->latest('id')->first()?->recipient_email)
        ->toBe('tenant@example.test');
});
