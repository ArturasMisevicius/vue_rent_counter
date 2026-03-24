<?php

use App\Enums\ServiceType;
use App\Filament\Actions\Admin\Providers\CreateProviderAction;
use App\Filament\Actions\Admin\Providers\DeleteProviderAction;
use App\Filament\Actions\Admin\Providers\UpdateProviderAction;
use App\Filament\Resources\Providers\Pages\ListProviders;
use App\Models\Organization;
use App\Models\Provider;
use App\Models\Tariff;
use App\Models\User;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

uses(RefreshDatabase::class);

it('shows organization-scoped provider resource pages to admin and manager users', function () {
    $organization = Organization::factory()->create();
    $provider = Provider::factory()
        ->forOrganization($organization)
        ->create([
            'name' => 'Ignitis',
            'service_type' => ServiceType::ELECTRICITY,
            'contact_info' => [
                'email' => 'billing@ignitis.example',
                'phone' => '+37060000000',
            ],
        ]);

    $otherOrganization = Organization::factory()->create();
    $otherProvider = Provider::factory()->forOrganization($otherOrganization)->create([
        'name' => 'Hidden Provider',
    ]);

    $admin = User::factory()->admin()->create([
        'organization_id' => $organization->id,
    ]);

    $manager = User::factory()->manager()->create([
        'organization_id' => $organization->id,
    ]);

    $superadmin = User::factory()->superadmin()->create();

    actingAs($admin);

    get(route('filament.admin.resources.providers.index'))
        ->assertSuccessful()
        ->assertSeeText('Providers')
        ->assertSeeText('New Provider')
        ->assertSeeText('Provider Name')
        ->assertSeeText('Code')
        ->assertSeeText('Type')
        ->assertSeeText('Contact Email')
        ->assertSeeText('Tariff Count')
        ->assertSeeText($provider->name)
        ->assertSeeText('Electricity')
        ->assertSeeText('billing@ignitis.example')
        ->assertDontSeeText('+37060000000')
        ->assertDontSeeText($otherProvider->name);

    actingAs($admin);

    get(route('filament.admin.resources.providers.create'))
        ->assertSuccessful()
        ->assertSeeText('Name')
        ->assertSeeText('Service Type')
        ->assertSeeText('Website');

    actingAs($admin);

    get(route('filament.admin.resources.providers.view', $provider))
        ->assertSuccessful()
        ->assertSeeText('Provider Details')
        ->assertSeeText($provider->name)
        ->assertSeeText('billing@ignitis.example');

    actingAs($admin);

    get(route('filament.admin.resources.providers.edit', $provider))
        ->assertSuccessful()
        ->assertSeeText('Save changes')
        ->assertSeeText($provider->name);

    actingAs($manager);

    get(route('filament.admin.resources.providers.index'))
        ->assertSuccessful()
        ->assertSeeText($provider->name);

    actingAs($admin);

    get(route('filament.admin.resources.providers.view', $otherProvider))
        ->assertNotFound();

    actingAs($admin);

    get(route('filament.admin.resources.providers.edit', $otherProvider))
        ->assertNotFound();

    actingAs($superadmin);

    get(route('filament.admin.resources.providers.index'))
        ->assertSuccessful()
        ->assertSeeText($provider->name)
        ->assertSeeText($otherProvider->name);
});

it('creates, updates, and blocks deletion of providers with related tariffs', function () {
    $organization = Organization::factory()->create();

    $created = app(CreateProviderAction::class)->handle($organization, [
        'name' => 'Vilniaus Vandenys',
        'service_type' => ServiceType::WATER,
        'contact_info' => [
            'phone' => '+37061111111',
            'email' => 'hello@vv.example',
            'website' => 'https://vv.example',
        ],
    ]);

    expect($created)
        ->organization_id->toBe($organization->id)
        ->name->toBe('Vilniaus Vandenys')
        ->service_type->toBe(ServiceType::WATER)
        ->and($created->contact_info['email'])->toBe('hello@vv.example');

    $updated = app(UpdateProviderAction::class)->handle($created, [
        'name' => 'Vilniaus Vandenys Updated',
        'service_type' => ServiceType::HEATING,
        'contact_info' => [
            'phone' => '+37062222222',
            'email' => 'updated@vv.example',
            'website' => 'https://updated-vv.example',
        ],
    ]);

    expect($updated)
        ->name->toBe('Vilniaus Vandenys Updated')
        ->service_type->toBe(ServiceType::HEATING)
        ->and($updated->contact_info['website'])->toBe('https://updated-vv.example');

    Tariff::factory()->for($updated)->create();

    expect(fn () => app(DeleteProviderAction::class)->handle($updated))
        ->toThrow(ValidationException::class);

    expect(Provider::query()->whereKey($updated->id)->exists())->toBeTrue();

    $deletableProvider = Provider::factory()->forOrganization($organization)->create();

    app(DeleteProviderAction::class)->handle($deletableProvider);

    expect(Provider::query()->whereKey($deletableProvider->id)->exists())->toBeFalse();
});

it('shows organization context on the providers list for superadmins while keeping admins scoped', function () {
    $organizationA = Organization::factory()->create([
        'name' => 'Northwind Estates',
    ]);
    $organizationB = Organization::factory()->create([
        'name' => 'Aurora Towers',
    ]);

    $providerA = Provider::factory()->forOrganization($organizationA)->create([
        'name' => 'Ignitis',
        'service_type' => ServiceType::ELECTRICITY,
        'contact_info' => [
            'email' => 'billing@ignitis.example',
            'phone' => '+37060000001',
        ],
    ]);

    $providerB = Provider::factory()->forOrganization($organizationB)->create([
        'name' => 'Elektrum',
        'service_type' => ServiceType::WATER,
    ]);

    $admin = User::factory()->admin()->create([
        'organization_id' => $organizationA->id,
    ]);
    $superadmin = User::factory()->superadmin()->create();

    Tariff::factory()->for($providerA)->create();
    $deletableProvider = Provider::factory()->forOrganization($organizationA)->create([
        'name' => 'Vilniaus Energija',
        'service_type' => ServiceType::HEATING,
        'contact_info' => [
            'email' => 'support@ve.example',
        ],
    ]);

    $this->actingAs($admin)
        ->get(route('filament.admin.resources.providers.index'))
        ->assertSuccessful()
        ->assertSeeText('Providers')
        ->assertSeeText($providerA->name)
        ->assertDontSeeText($providerB->name);

    $this->actingAs($superadmin)
        ->get(route('filament.admin.resources.providers.index'))
        ->assertSuccessful()
        ->assertSeeText('Providers')
        ->assertSeeText($providerA->name)
        ->assertSeeText($providerB->name)
        ->assertSeeText($organizationA->name)
        ->assertSeeText($organizationB->name);

    $this->actingAs($superadmin);

    Livewire::test(ListProviders::class)
        ->assertActionVisible('create')
        ->assertTableColumnExists('organization.name', fn (TextColumn $column): bool => $column->getLabel() === 'Organization')
        ->assertTableColumnExists('name', fn (TextColumn $column): bool => $column->getLabel() === 'Provider Name')
        ->assertTableColumnExists('provider_code', fn (TextColumn $column): bool => $column->getLabel() === 'Code')
        ->assertTableColumnExists('service_type', fn (TextColumn $column): bool => $column->getLabel() === 'Type')
        ->assertTableColumnExists('contact_info.email', fn (TextColumn $column): bool => $column->getLabel() === 'Contact Email')
        ->assertTableColumnExists('tariffs_count', fn (TextColumn $column): bool => $column->getLabel() === 'Tariff Count')
        ->assertTableFilterExists('organization', fn (SelectFilter $filter): bool => $filter->getLabel() === 'Organization')
        ->assertTableColumnStateSet('organization.name', $organizationA->name, $providerA)
        ->assertTableColumnStateSet('organization.name', $organizationB->name, $providerB)
        ->assertTableColumnStateSet('service_type', ServiceType::ELECTRICITY->getLabel(), $providerA)
        ->assertTableColumnStateSet('contact_info.email', 'billing@ignitis.example', $providerA)
        ->assertCanSeeTableRecords([$providerA, $providerB])
        ->assertTableActionExists('view', record: $providerA)
        ->assertTableActionExists('edit', record: $providerA)
        ->assertTableActionDisabled('delete', record: $providerA)
        ->assertTableActionEnabled('delete', record: $deletableProvider)
        ->filterTable('organization', (string) $organizationA->getKey())
        ->assertCanSeeTableRecords([$providerA])
        ->assertCanNotSeeTableRecords([$providerB]);
});
