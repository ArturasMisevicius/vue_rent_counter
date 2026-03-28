<?php

use App\Models\Invoice;
use App\Models\MeterReading;
use App\Models\Organization;
use App\Models\Property;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\TenantPortalFactory;

uses(RefreshDatabase::class);

it('shows the tenant greeting, outstanding balance, and recent readings', function () {
    $tenant = TenantPortalFactory::new()
        ->withUserName('Taylor Tenant')
        ->withUnpaidInvoices()
        ->withMeters()
        ->withReadings()
        ->create();

    $this->actingAs($tenant->user)
        ->get(route('filament.admin.pages.tenant-dashboard'))
        ->assertSuccessful()
        ->assertSeeText('Taylor')
        ->assertSeeText('Outstanding Balance')
        ->assertSeeText('This Month')
        ->assertSeeText('All current')
        ->assertSeeText('Recent Readings')
        ->assertSeeText('Submit New Reading');
});

it('shows all paid up copy when no unpaid invoices exist', function () {
    $tenant = TenantPortalFactory::new()
        ->withPaidInvoices()
        ->withMeters()
        ->withReadings()
        ->create();

    $this->actingAs($tenant->user)
        ->get(route('filament.admin.pages.tenant-dashboard'))
        ->assertSuccessful()
        ->assertSeeText('All paid up');
});

it('shows the combined unpaid invoice count copy', function () {
    $tenant = TenantPortalFactory::new()
        ->withUnpaidInvoices(2)
        ->withMeters()
        ->withReadings()
        ->create();

    $this->actingAs($tenant->user)
        ->get(route('filament.admin.pages.tenant-dashboard'))
        ->assertSuccessful()
        ->assertSeeText('Across 2 invoices');
});

it('uses the tenants outstanding invoice currency on the home balance card', function () {
    $tenant = TenantPortalFactory::new()
        ->withUnpaidInvoices(1)
        ->withMeters()
        ->withReadings()
        ->create();

    $tenant->invoices->each(function ($invoice): void {
        $invoice->forceFill([
            'currency' => 'USD',
        ])->save();
    });

    $this->actingAs($tenant->user)
        ->get(route('filament.admin.pages.tenant-dashboard'))
        ->assertSuccessful()
        ->assertSeeText('USD 75.00')
        ->assertDontSeeText('EUR 75.00');
});

it('shows no reading this month when a meter is missing a current-month reading', function () {
    $tenant = TenantPortalFactory::new()
        ->withAssignedProperty()
        ->withMeters(1)
        ->create();

    $this->actingAs($tenant->user)
        ->get(route('filament.admin.pages.tenant-dashboard'))
        ->assertSuccessful()
        ->assertSeeText('1 pending')
        ->assertSeeText('No reading this month');
});

it('shows the my property link on the tenant home screen', function () {
    $tenant = TenantPortalFactory::new()
        ->withAssignedProperty()
        ->withMeters()
        ->withReadings()
        ->create();

    $this->actingAs($tenant->user)
        ->get(route('filament.admin.pages.tenant-dashboard'))
        ->assertSuccessful()
        ->assertSeeText('My Property')
        ->assertSee(route('filament.admin.pages.tenant-property-details'), false);
});

it('shows the tenant phone in the tenant home summary', function () {
    $tenant = TenantPortalFactory::new()
        ->withAssignedProperty()
        ->create();

    $tenant->user->forceFill([
        'phone' => '+37063334444',
    ])->save();

    $this->actingAs($tenant->user->fresh())
        ->get(route('filament.admin.pages.tenant-dashboard'))
        ->assertSuccessful()
        ->assertSeeText('+37063334444');
});

it('builds payment guidance from organization billing contact settings when no instructions are configured', function () {
    $tenant = TenantPortalFactory::new()
        ->withAssignedProperty()
        ->withUnpaidInvoices()
        ->withoutPaymentInstructions()
        ->withBillingContact(
            name: 'Updated Billing Team',
            email: 'billing@example.com',
            phone: '+37060000000',
        )
        ->create();

    $this->actingAs($tenant->user)
        ->get(route('filament.admin.pages.tenant-dashboard'))
        ->assertSuccessful()
        ->assertSeeText('Updated Billing Team')
        ->assertSeeText('billing@example.com')
        ->assertSeeText('+37060000000')
        ->assertDontSeeText('Contact your building manager for payment instructions.');
});

it('does not include malformed cross-organization invoices in the home balance summary', function () {
    $tenant = TenantPortalFactory::new()
        ->withAssignedProperty()
        ->withUnpaidInvoices(1)
        ->create();

    $foreignOrganization = Organization::factory()->create();
    $foreignProperty = Property::factory()->create([
        'organization_id' => $foreignOrganization->id,
    ]);

    Invoice::factory()
        ->for($tenant->user, 'tenant')
        ->for($foreignProperty)
        ->create([
            'organization_id' => $foreignOrganization->id,
            'currency' => 'EUR',
            'total_amount' => 999.00,
            'amount_paid' => 0,
        ]);

    $this->actingAs($tenant->user)
        ->get(route('filament.admin.pages.tenant-dashboard'))
        ->assertSuccessful()
        ->assertSeeText('EUR 75.00')
        ->assertDontSeeText('EUR 1,074.00');
});

it('does not include malformed cross-organization readings in the home activity summary', function () {
    $tenant = TenantPortalFactory::new()
        ->withAssignedProperty()
        ->withMeters(1)
        ->withReadings()
        ->create();

    $meter = $tenant->meters->firstOrFail();
    $foreignOrganization = Organization::factory()->create();

    MeterReading::factory()
        ->for($foreignOrganization)
        ->create([
            'property_id' => $tenant->property->id,
            'meter_id' => $meter->id,
            'reading_value' => 999.999,
        ]);

    $this->actingAs($tenant->user)
        ->get(route('filament.admin.pages.tenant-dashboard'))
        ->assertSuccessful()
        ->assertDontSeeText('999.999');
});

it('renders the tenant home copy in lithuanian for lithuanian tenants', function () {
    $tenant = TenantPortalFactory::new()
        ->withAssignedProperty()
        ->withUnpaidInvoices()
        ->withMeters()
        ->withReadings()
        ->create();

    $tenant->user->forceFill([
        'locale' => 'lt',
    ])->save();

    $this->actingAs($tenant->user->fresh())
        ->get(route('filament.admin.pages.tenant-dashboard'))
        ->assertSuccessful()
        ->assertSeeText('Nuomininko suvestinė')
        ->assertSeeText('Pateikti naują rodmenį')
        ->assertSeeText('Naujausi rodmenys');
});

it('falls back to english when a tenant has an unsupported locale', function () {
    $tenant = TenantPortalFactory::new()
        ->withAssignedProperty()
        ->withUnpaidInvoices()
        ->withMeters()
        ->withReadings()
        ->create();

    $tenant->user->forceFill([
        'locale' => 'de',
    ])->save();

    $this->actingAs($tenant->user->fresh())
        ->get(route('filament.admin.pages.tenant-dashboard'))
        ->assertSuccessful()
        ->assertSeeText('Tenant Summary')
        ->assertSeeText('Submit New Reading')
        ->assertSeeText('Recent Readings');
});
