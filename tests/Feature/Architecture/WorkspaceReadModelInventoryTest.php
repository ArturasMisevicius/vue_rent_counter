<?php

declare(strict_types=1);

use App\Livewire\Shell\GlobalSearch;
use App\Models\Building;
use App\Models\Invoice;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Models\Organization;
use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('keeps high-risk read surfaces delegated to shared builder classes', function (): void {
    $inventory = [
        'app/Livewire/Pages/Reports/ReportsPage.php' => [
            'ConsumptionReportBuilder',
            'RevenueReportBuilder',
            'OutstandingBalancesReportBuilder',
            'MeterComplianceReportBuilder',
        ],
        'app/Livewire/Shell/GlobalSearch.php' => [
            'GlobalSearchRegistry',
        ],
        'app/Livewire/Tenant/InvoiceHistory.php' => [
            'TenantInvoiceIndexQuery',
            'PaymentInstructionsResolver',
        ],
        'app/Filament/Resources/Invoices/InvoiceResource.php' => [
            'WorkspaceResolver',
        ],
    ];

    foreach ($inventory as $path => $dependencies) {
        $contents = file_get_contents(base_path($path));

        expect($contents)->not->toBeFalse();

        foreach ($dependencies as $dependency) {
            expect(Str::contains((string) $contents, $dependency))
                ->toBeTrue("Expected {$path} to depend on {$dependency}.");
        }
    }
});

it('uses dedicated resource views for superadmin global-search results instead of organization-tab deep links', function (): void {
    $organization = Organization::factory()->create([
        'name' => 'Northwind Towers',
    ]);
    $superadmin = User::factory()->superadmin()->create();
    $tenant = User::factory()->tenant()->create([
        'organization_id' => $organization->id,
        'name' => 'Property Scope Tenant',
        'email' => 'scope-tenant@example.test',
    ]);
    $building = Building::factory()->for($organization)->create([
        'name' => 'Scope Building',
    ]);
    $property = Property::factory()->for($organization)->for($building)->create([
        'name' => 'Scope Property',
    ]);
    $invoice = Invoice::factory()
        ->for($organization)
        ->for($property)
        ->for($tenant, 'tenant')
        ->create([
            'invoice_number' => 'Scope-INV-001',
        ]);
    $meter = Meter::factory()->for($organization)->for($property)->create([
        'name' => 'Scope Meter',
        'identifier' => 'SCOPE-001',
    ]);
    $reading = MeterReading::factory()->for($organization)->for($property)->for($meter)->create();

    $component = Livewire::actingAs($superadmin)
        ->test(GlobalSearch::class)
        ->set('query', 'Scope');

    $results = $component->instance()->results();

    expect(collect($results['buildings'] ?? [])->firstWhere('title', 'Scope Building')['url'] ?? null)
        ->toBe(route('filament.admin.resources.buildings.view', $building))
        ->and(collect($results['properties'] ?? [])->firstWhere('title', 'Scope Property')['url'] ?? null)
        ->toBe(route('filament.admin.resources.properties.view', $property))
        ->and(collect($results['tenants'] ?? [])->firstWhere('title', 'Property Scope Tenant')['url'] ?? null)
        ->toBe(route('filament.admin.resources.users.view', $tenant))
        ->and(collect($results['invoices'] ?? [])->firstWhere('title', 'Scope-INV-001')['url'] ?? null)
        ->toBe(route('filament.admin.resources.invoices.view', $invoice))
        ->and(collect($results['readings'] ?? [])->firstWhere('title', 'Scope Meter')['url'] ?? null)
        ->toBe(route('filament.admin.resources.meter-readings.view', $reading));
});
