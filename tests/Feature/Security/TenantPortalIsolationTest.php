<?php

declare(strict_types=1);

use App\Filament\Actions\Tenant\Readings\SubmitTenantReadingAction;
use App\Livewire\Tenant\InvoiceHistory;
use App\Livewire\Tenant\SubmitMeterReading;
use App\Models\Invoice;
use App\Models\Meter;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\Support\TenantPortalFactory;

uses(RefreshDatabase::class);

it('keeps the filament tenant portal and its actions isolated per tenant account', function () {
    $tenantA = TenantPortalFactory::new()
        ->withUserName('Tenant A')
        ->withAssignedProperty()
        ->withMeters(1)
        ->withReadings()
        ->withUnpaidInvoices(1)
        ->create();

    $tenantB = TenantPortalFactory::new()
        ->withUserName('Tenant B')
        ->withAssignedProperty()
        ->create();

    $tenantB->building->forceFill([
        'address_line_1' => '999 Rival Street',
        'city' => 'Kaunas',
    ])->save();

    $tenantB->property->forceFill([
        'name' => 'Apartment 99',
        'unit_number' => '99',
    ])->save();

    $foreignMeter = Meter::factory()
        ->for($tenantB->organization)
        ->for($tenantB->property->fresh())
        ->create([
            'name' => 'Foreign Meter',
            'identifier' => 'TEN-FOREIGN-PORTAL-001',
        ]);

    $foreignInvoice = Invoice::factory()
        ->for($tenantB->organization)
        ->for($tenantB->property->fresh())
        ->for($tenantB->user, 'tenant')
        ->create([
            'invoice_number' => 'TEN-FOREIGN-INV-001',
        ]);

    $this->actingAs($tenantA->user)
        ->get(route('filament.admin.pages.tenant-dashboard'))
        ->assertSuccessful()
        ->assertSeeText($tenantA->property->name)
        ->assertSeeText($tenantA->building->address_line_1)
        ->assertDontSeeText('Apartment 99')
        ->assertDontSeeText('999 Rival Street');

    $this->actingAs($tenantA->user)
        ->get(route('filament.admin.pages.tenant-submit-meter-reading'))
        ->assertSuccessful()
        ->assertSeeText($tenantA->meters->firstOrFail()->identifier)
        ->assertDontSeeText($foreignMeter->identifier);

    $this->actingAs($tenantA->user)
        ->get(route('filament.admin.pages.tenant-invoice-history'))
        ->assertSuccessful()
        ->assertSeeText($tenantA->invoices->firstOrFail()->invoice_number)
        ->assertDontSeeText($foreignInvoice->invoice_number);

    $this->actingAs($tenantA->user)
        ->get(route('filament.admin.pages.tenant-property-details'))
        ->assertSuccessful()
        ->assertSeeText($tenantA->property->name)
        ->assertSeeText($tenantA->meters->firstOrFail()->identifier)
        ->assertDontSeeText('Apartment 99')
        ->assertDontSeeText($foreignMeter->identifier);

    Livewire::actingAs($tenantA->user)
        ->test(InvoiceHistory::class)
        ->call('downloadPdf', $foreignInvoice->id)
        ->assertForbidden();

    expect(fn () => app(SubmitTenantReadingAction::class)->handle(
        tenant: $tenantA->user,
        meterId: $foreignMeter->id,
        readingValue: '245.125',
        readingDate: now()->toDateString(),
        notes: null,
    ))->toThrow(AuthorizationException::class);

    Livewire::actingAs($tenantA->user)
        ->test(SubmitMeterReading::class)
        ->set('meterId', (string) $foreignMeter->id)
        ->set('readingValue', '245.125')
        ->set('readingDate', now()->toDateString())
        ->call('submit')
        ->assertHasErrors(['meterId']);

    Storage::fake(config('filesystems.default', 'local'));
    $foreignInvoice->forceFill(['document_path' => 'tenant-invoices/forbidden-invoice.pdf']);
    $foreignInvoice->save();

    Storage::disk(config('filesystems.default', 'local'))->put('tenant-invoices/forbidden-invoice.pdf', 'pdf-content');

    $this->actingAs($tenantA->user)
        ->get(route('tenant.invoices.download', $foreignInvoice))
        ->assertForbidden();
});

it('forbids non-tenant accounts from opening tenant portal filament pages', function () {
    $organization = Organization::factory()->create();
    $admin = User::factory()->admin()->create([
        'organization_id' => $organization->id,
    ]);

    foreach ([
        'filament.admin.pages.tenant-dashboard',
        'filament.admin.pages.tenant-submit-meter-reading',
        'filament.admin.pages.tenant-invoice-history',
        'filament.admin.pages.tenant-property-details',
    ] as $routeName) {
        $this->actingAs($admin)
            ->get(route($routeName))
            ->assertForbidden();
    }
});
