<?php

use App\Filament\Resources\Buildings\BuildingResource;
use App\Filament\Resources\Invoices\InvoiceResource;
use App\Filament\Resources\Meters\MeterResource;
use App\Filament\Resources\Properties\PropertyResource;
use App\Filament\Resources\Tenants\TenantResource;
use App\Models\Building;
use App\Models\Invoice;
use App\Models\Meter;
use App\Models\Organization;
use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\TenantPortalFactory;

uses(RefreshDatabase::class);

function breadcrumb_items(string $html): array
{
    $dom = new DOMDocument;

    libxml_use_internal_errors(true);
    $dom->loadHTML($html);
    libxml_clear_errors();

    $xpath = new DOMXPath($dom);
    $items = $xpath->query("//nav[contains(@class, 'fi-breadcrumbs')]//li[contains(@class, 'fi-breadcrumbs-item')]");

    if ($items === false) {
        return [];
    }

    return collect(iterator_to_array($items))
        ->map(function ($itemNode) use ($xpath): array {
            $anchor = $xpath->query('.//a', $itemNode);

            return [
                'text' => trim((string) $itemNode->textContent),
                'href' => $anchor !== false && $anchor->length > 0
                    ? $anchor->item(0)?->getAttribute('href')
                    : null,
            ];
        })
        ->all();
}

it('renders breadcrumbs on tenant non-dashboard pages', function () {
    $tenant = TenantPortalFactory::new()
        ->withAssignedProperty()
        ->withPaidInvoices()
        ->create();

    $propertyResponse = $this->actingAs($tenant->user)
        ->get(route('tenant.property.show'))
        ->assertSuccessful()
        ->assertSee('fi-breadcrumbs', false);

    $propertyBreadcrumbs = breadcrumb_items($propertyResponse->getContent());

    expect($propertyBreadcrumbs)->toHaveCount(2)
        ->and($propertyBreadcrumbs[0]['href'])->toBe(route('tenant.home'))
        ->and($propertyBreadcrumbs[1]['text'])->toBe('My Property')
        ->and($propertyBreadcrumbs[1]['href'])->toBeNull();

    $invoiceResponse = $this->actingAs($tenant->user)
        ->get(route('tenant.invoices.index'))
        ->assertSuccessful()
        ->assertSee('fi-breadcrumbs', false);

    $invoiceBreadcrumbs = breadcrumb_items($invoiceResponse->getContent());

    expect($invoiceBreadcrumbs)->toHaveCount(2)
        ->and($invoiceBreadcrumbs[0]['href'])->toBe(route('tenant.home'))
        ->and($invoiceBreadcrumbs[1]['text'])->toBe('Invoices')
        ->and($invoiceBreadcrumbs[1]['href'])->toBeNull();
});

it('does not render breadcrumbs on dashboard pages', function () {
    $tenant = User::factory()->tenant()->create();
    $admin = User::factory()->admin()->create([
        'organization_id' => Organization::factory(),
    ]);

    $this->actingAs($tenant)
        ->get(route('tenant.home'))
        ->assertSuccessful()
        ->assertDontSee('fi-breadcrumbs', false);

    $this->actingAs($admin)
        ->get(route('filament.admin.pages.organization-dashboard'))
        ->assertSuccessful()
        ->assertDontSee('fi-breadcrumbs', false);
});

it('renders breadcrumbs on admin resource view pages with a plain-text current crumb', function (string $resourceClass, string $expectedIndexLabel, Closure $recordFactory) {
    $organization = Organization::factory()->create();
    $admin = User::factory()->admin()->create([
        'organization_id' => $organization->id,
    ]);
    $record = $recordFactory($organization);

    $response = $this->actingAs($admin)
        ->get($resourceClass::getUrl('view', ['record' => $record]))
        ->assertSuccessful()
        ->assertSee('fi-breadcrumbs', false)
        ->assertSeeText($expectedIndexLabel)
        ->assertSeeText('View');

    $breadcrumbs = breadcrumb_items($response->getContent());

    expect($breadcrumbs)->not->toBeEmpty()
        ->and($breadcrumbs[0]['href'])->toBe($resourceClass::getUrl())
        ->and($breadcrumbs[array_key_last($breadcrumbs)]['text'])->toBe('View')
        ->and($breadcrumbs[array_key_last($breadcrumbs)]['href'])->toBeNull();
})->with([
    'building' => [
        BuildingResource::class,
        'Buildings',
        fn (Organization $organization) => Building::factory()->for($organization)->create(),
    ],
    'property' => [
        PropertyResource::class,
        'Properties',
        fn (Organization $organization) => Property::factory()
            ->for($organization)
            ->for(Building::factory()->for($organization))
            ->create(),
    ],
    'tenant' => [
        TenantResource::class,
        'Tenants',
        fn (Organization $organization) => User::factory()->tenant()->create([
            'organization_id' => $organization->id,
        ]),
    ],
    'meter' => [
        MeterResource::class,
        'Meters',
        fn (Organization $organization) => Meter::factory()
            ->for($organization)
            ->for(Property::factory()->for($organization)->for(Building::factory()->for($organization)))
            ->create(),
    ],
    'invoice' => [
        InvoiceResource::class,
        'Invoices',
        fn (Organization $organization) => Invoice::factory()
            ->for($organization)
            ->for(Property::factory()->for($organization)->for(Building::factory()->for($organization)))
            ->for(User::factory()->tenant()->create(['organization_id' => $organization->id]), 'tenant')
            ->create(),
    ],
]);
