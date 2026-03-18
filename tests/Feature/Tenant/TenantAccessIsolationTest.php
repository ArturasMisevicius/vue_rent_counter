<?php

use App\Enums\MeterReadingSubmissionMethod;
use App\Livewire\Tenant\SubmitReadingPage;
use App\Models\Invoice;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Models\Organization;
use App\Models\Property;
use App\Models\PropertyAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\Support\TenantPortalFactory;

uses(RefreshDatabase::class);

it('returns not found for the property page when the tenant has no assigned property', function () {
    $tenant = TenantPortalFactory::new()->create();

    $this->actingAs($tenant->user)
        ->get(route('filament.admin.pages.tenant-property-details'))
        ->assertNotFound();
});

it('does not list invoices that belong to a different tenant', function () {
    $tenant = TenantPortalFactory::new()
        ->withAssignedProperty()
        ->withUnpaidInvoices(1)
        ->create();

    $foreignOrganization = Organization::factory()->create();
    $foreignTenant = User::factory()->tenant()->create([
        'organization_id' => $foreignOrganization->id,
    ]);
    $foreignProperty = Property::factory()->create([
        'organization_id' => $foreignOrganization->id,
    ]);

    Invoice::factory()->for($foreignTenant, 'tenant')->for($foreignProperty)->create([
        'organization_id' => $foreignOrganization->id,
        'invoice_number' => 'FOREIGN-001',
    ]);

    $this->actingAs($tenant->user)
        ->get(route('filament.admin.pages.tenant-invoice-history'))
        ->assertSuccessful()
        ->assertSeeText('UNPAID-001')
        ->assertDontSeeText('FOREIGN-001');
});

it('rejects meter submissions outside the tenants assigned property', function () {
    $tenant = TenantPortalFactory::new()
        ->withAssignedProperty()
        ->withMeters(1)
        ->create();

    $foreignMeter = Meter::factory()->create([
        'name' => 'Foreign Meter',
    ]);

    Livewire::actingAs($tenant->user)
        ->test(SubmitReadingPage::class)
        ->set('meterId', (string) $foreignMeter->id)
        ->set('readingValue', '55.000')
        ->set('readingDate', now()->toDateString())
        ->call('submit')
        ->assertHasErrors(['meterId']);

    expect(
        MeterReading::query()
            ->where('meter_id', $foreignMeter->id)
            ->where('submitted_by_user_id', $tenant->user->id)
            ->where('submission_method', MeterReadingSubmissionMethod::TENANT_PORTAL)
            ->exists()
    )->toBeFalse();
});

it('forbids invoice downloads outside the tenant boundary', function () {
    Storage::fake(config('filesystems.default', 'local'));

    $tenant = TenantPortalFactory::new()
        ->withAssignedProperty()
        ->withUnpaidInvoices(1)
        ->create();

    $foreignTenant = User::factory()->tenant()->create();
    $foreignInvoice = Invoice::factory()->for($foreignTenant, 'tenant')->create([
        'document_path' => 'tenant-invoices/forbidden.pdf',
    ]);

    Storage::disk(config('filesystems.default', 'local'))
        ->put('tenant-invoices/forbidden.pdf', 'pdf-content');

    $this->actingAs($tenant->user)
        ->get(route('tenant.invoices.download', $foreignInvoice))
        ->assertForbidden();
});

it('does not list malformed invoices from another organization even if they reference the same tenant user', function () {
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
            'invoice_number' => 'MALFORMED-CROSS-ORG-001',
        ]);

    $this->actingAs($tenant->user)
        ->get(route('filament.admin.pages.tenant-invoice-history'))
        ->assertSuccessful()
        ->assertSeeText('UNPAID-001')
        ->assertDontSeeText('MALFORMED-CROSS-ORG-001');
});

it('forbids downloading malformed invoices from another organization even if they reference the same tenant user', function () {
    Storage::fake(config('filesystems.default', 'local'));

    $tenant = TenantPortalFactory::new()
        ->withAssignedProperty()
        ->withUnpaidInvoices(1)
        ->create();

    $foreignOrganization = Organization::factory()->create();
    $foreignProperty = Property::factory()->create([
        'organization_id' => $foreignOrganization->id,
    ]);

    $foreignInvoice = Invoice::factory()
        ->for($tenant->user, 'tenant')
        ->for($foreignProperty)
        ->create([
            'organization_id' => $foreignOrganization->id,
            'document_path' => 'tenant-invoices/malformed-cross-org.pdf',
        ]);

    Storage::disk(config('filesystems.default', 'local'))
        ->put('tenant-invoices/malformed-cross-org.pdf', 'pdf-content');

    $this->actingAs($tenant->user)
        ->get(route('tenant.invoices.download', $foreignInvoice))
        ->assertForbidden();
});

it('treats malformed cross-organization property assignments as unavailable to the tenant portal', function () {
    $tenant = TenantPortalFactory::new()->create();

    $foreignOrganization = Organization::factory()->create();
    $foreignProperty = Property::factory()->create([
        'organization_id' => $foreignOrganization->id,
    ]);

    PropertyAssignment::factory()
        ->for($foreignOrganization)
        ->for($foreignProperty)
        ->for($tenant->user, 'tenant')
        ->create([
            'assigned_at' => now()->subMonth(),
            'unassigned_at' => null,
        ]);

    $this->actingAs($tenant->user)
        ->get(route('filament.admin.pages.tenant-property-details'))
        ->assertNotFound();

    $this->actingAs($tenant->user)
        ->get(route('filament.admin.pages.tenant-submit-meter-reading'))
        ->assertSuccessful()
        ->assertSeeText(__('tenant.messages.no_meters_assigned'))
        ->assertDontSeeText($foreignProperty->name);
});
