<?php

use App\Enums\AuditLogAction;
use App\Enums\InvoiceStatus;
use App\Filament\Actions\Superadmin\Organizations\WriteOffOrganizationInvoicesAction;
use App\Filament\Resources\Organizations\Pages\ViewOrganization;
use App\Models\AuditLog;
use App\Models\Building;
use App\Models\Invoice;
use App\Models\Organization;
use App\Models\OrganizationInvoiceWriteOff;
use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('requires a reason note before writing off outstanding invoices', function () {
    [$organization] = seedOrganizationForWriteOffs();

    expect(fn () => app(WriteOffOrganizationInvoicesAction::class)->handle(
        $organization->fresh(),
        '',
    ))->toThrow(ValidationException::class);
});

it('writes off outstanding invoices and clears unpaid deletion blockers without deleting invoices', function () {
    $superadmin = User::factory()->superadmin()->create();
    [$organization, $outstandingInvoices] = seedOrganizationForWriteOffs();

    $this->actingAs($superadmin);

    expect(Invoice::query()->forOrganization($organization->id)->outstanding()->count())->toBe(2)
        ->and(Invoice::query()->forOrganization($organization->id)->count())->toBe(3);

    $writtenOffCount = app(WriteOffOrganizationInvoicesAction::class)->handle(
        $organization->fresh(),
        'Support approved write-off',
    );

    $auditLog = AuditLog::query()
        ->where('organization_id', $organization->id)
        ->where('action', AuditLogAction::UPDATED)
        ->latest('id')
        ->first();

    expect($writtenOffCount)->toBe(2)
        ->and(OrganizationInvoiceWriteOff::query()->forOrganization($organization->id)->count())->toBe(2)
        ->and(Invoice::query()->forOrganization($organization->id)->outstanding()->count())->toBe(0)
        ->and(Invoice::query()->forOrganization($organization->id)->count())->toBe(3)
        ->and(OrganizationInvoiceWriteOff::query()->pluck('invoice_id')->all())->toMatchArray($outstandingInvoices->pluck('id')->all())
        ->and($auditLog)->not->toBeNull()
        ->and($auditLog?->actor_user_id)->toBe($superadmin->id)
        ->and($auditLog?->metadata)->toMatchArray([
            'reason' => 'Support approved write-off',
            'written_off_invoice_count' => 2,
        ]);
});

it('writes off outstanding invoices from the organization view page action', function () {
    $superadmin = User::factory()->superadmin()->create();
    [$organization] = seedOrganizationForWriteOffs();

    $this->actingAs($superadmin);

    Livewire::test(ViewOrganization::class, ['record' => $organization->getRouteKey()])
        ->assertActionExists('writeOffInvoices')
        ->callAction('writeOffInvoices', data: [
            'reason' => 'Owner requested closure support.',
        ]);

    expect(OrganizationInvoiceWriteOff::query()->forOrganization($organization->id)->count())->toBe(2);
});

function seedOrganizationForWriteOffs(): array
{
    $organization = Organization::factory()->create([
        'name' => 'Northwind Towers',
        'slug' => 'northwind-towers',
    ]);

    $owner = User::factory()->admin()->create([
        'organization_id' => $organization->id,
        'name' => 'Olivia Owner',
        'email' => 'owner@northwind.test',
        'email_verified_at' => now(),
    ]);

    $organization->forceFill([
        'owner_user_id' => $owner->id,
    ])->save();

    $tenant = User::factory()->tenant()->create([
        'organization_id' => $organization->id,
        'email_verified_at' => now(),
    ]);

    $building = Building::factory()->create([
        'organization_id' => $organization->id,
    ]);

    $property = Property::factory()->create([
        'organization_id' => $organization->id,
        'building_id' => $building->id,
    ]);

    $outstandingInvoices = Invoice::factory()->count(2)->create([
        'organization_id' => $organization->id,
        'property_id' => $property->id,
        'tenant_user_id' => $tenant->id,
        'status' => InvoiceStatus::FINALIZED,
        'total_amount' => 150,
        'amount_paid' => 0,
        'due_date' => now()->subDays(5)->toDateString(),
    ]);

    Invoice::factory()->create([
        'organization_id' => $organization->id,
        'property_id' => $property->id,
        'tenant_user_id' => $tenant->id,
        'status' => InvoiceStatus::PAID,
        'total_amount' => 200,
        'amount_paid' => 200,
        'paid_at' => now()->subDay(),
    ]);

    return [$organization->fresh(), $outstandingInvoices];
}
