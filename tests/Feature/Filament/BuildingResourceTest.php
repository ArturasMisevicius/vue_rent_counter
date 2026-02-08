<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Filament\Resources\BuildingResource;
use App\Models\Building;
use App\Models\User;
use Filament\Actions\DeleteAction;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->superadmin = User::factory()->create(['role' => UserRole::SUPERADMIN]);
    $this->admin = User::factory()->create(['role' => UserRole::ADMIN]);
    $this->manager = User::factory()->create(['role' => UserRole::MANAGER]);
    $this->tenant = User::factory()->create(['role' => UserRole::TENANT]);
});

describe('BuildingResource Navigation', function () {
    test('superadmin can see buildings navigation', function () {
        actingAs($this->superadmin);

        expect(BuildingResource::shouldRegisterNavigation())->toBeTrue();
    });

    test('admin can see buildings navigation', function () {
        actingAs($this->admin);

        expect(BuildingResource::shouldRegisterNavigation())->toBeTrue();
    });

    test('manager can see buildings navigation', function () {
        actingAs($this->manager);

        expect(BuildingResource::shouldRegisterNavigation())->toBeTrue();
    });

    test('tenant cannot see buildings navigation', function () {
        actingAs($this->tenant);

        expect(BuildingResource::shouldRegisterNavigation())->toBeFalse();
    });

    test('guest cannot see buildings navigation', function () {
        expect(BuildingResource::shouldRegisterNavigation())->toBeFalse();
    });
});

describe('BuildingResource Authorization - View Any', function () {
    test('superadmin can view any buildings', function () {
        actingAs($this->superadmin);

        expect(BuildingResource::canViewAny())->toBeTrue();
    });

    test('admin can view any buildings', function () {
        actingAs($this->admin);

        expect(BuildingResource::canViewAny())->toBeTrue();
    });

    test('manager can view any buildings', function () {
        actingAs($this->manager);

        expect(BuildingResource::canViewAny())->toBeTrue();
    });

    test('tenant cannot view any buildings', function () {
        actingAs($this->tenant);

        expect(BuildingResource::canViewAny())->toBeFalse();
    });

    test('guest cannot view any buildings', function () {
        expect(BuildingResource::canViewAny())->toBeFalse();
    });
});

describe('BuildingResource Authorization - Create', function () {
    test('superadmin can create buildings', function () {
        actingAs($this->superadmin);

        expect(BuildingResource::canCreate())->toBeTrue();
    });

    test('admin can create buildings', function () {
        actingAs($this->admin);

        expect(BuildingResource::canCreate())->toBeTrue();
    });

    test('manager can create buildings', function () {
        actingAs($this->manager);

        expect(BuildingResource::canCreate())->toBeTrue();
    });

    test('tenant cannot create buildings', function () {
        actingAs($this->tenant);

        expect(BuildingResource::canCreate())->toBeFalse();
    });

    test('guest cannot create buildings', function () {
        expect(BuildingResource::canCreate())->toBeFalse();
    });
});

describe('BuildingResource Authorization - Edit', function () {
    test('superadmin can edit any building', function () {
        actingAs($this->superadmin);
        $building = Building::factory()->create();

        expect(BuildingResource::canEdit($building))->toBeTrue();
    });

    test('admin can edit any building', function () {
        actingAs($this->admin);
        $building = Building::factory()->create();

        expect(BuildingResource::canEdit($building))->toBeTrue();
    });

    test('manager can edit buildings in their tenant', function () {
        actingAs($this->manager);
        $building = Building::factory()->create(['tenant_id' => $this->manager->tenant_id]);

        expect(BuildingResource::canEdit($building))->toBeTrue();
    });

    test('manager cannot edit buildings from other tenants', function () {
        actingAs($this->manager);
        $otherTenant = User::factory()->create(['role' => UserRole::ADMIN]);
        $building = Building::factory()->create(['tenant_id' => $otherTenant->tenant_id]);

        expect(BuildingResource::canEdit($building))->toBeFalse();
    });

    test('tenant cannot edit buildings', function () {
        actingAs($this->tenant);
        $building = Building::factory()->create(['tenant_id' => $this->tenant->tenant_id]);

        expect(BuildingResource::canEdit($building))->toBeFalse();
    });

    test('guest cannot edit buildings', function () {
        $building = Building::factory()->create();

        expect(BuildingResource::canEdit($building))->toBeFalse();
    });
});

describe('BuildingResource Authorization - Delete', function () {
    test('superadmin can delete any building', function () {
        actingAs($this->superadmin);
        $building = Building::factory()->create();

        expect(BuildingResource::canDelete($building))->toBeTrue();
    });

    test('admin can delete any building', function () {
        actingAs($this->admin);
        $building = Building::factory()->create();

        expect(BuildingResource::canDelete($building))->toBeTrue();
    });

    test('manager cannot delete buildings', function () {
        actingAs($this->manager);
        $building = Building::factory()->create(['tenant_id' => $this->manager->tenant_id]);

        expect(BuildingResource::canDelete($building))->toBeFalse();
    });

    test('tenant cannot delete buildings', function () {
        actingAs($this->tenant);
        $building = Building::factory()->create(['tenant_id' => $this->tenant->tenant_id]);

        expect(BuildingResource::canDelete($building))->toBeFalse();
    });

    test('guest cannot delete buildings', function () {
        $building = Building::factory()->create();

        expect(BuildingResource::canDelete($building))->toBeFalse();
    });
});

describe('BuildingResource Configuration', function () {
    test('has correct model class', function () {
        expect(BuildingResource::getModel())->toBe(Building::class);
    });

    test('has correct navigation icon', function () {
        expect(BuildingResource::getNavigationIcon())->toBe('heroicon-o-building-office-2');
    });

    test('has correct navigation sort order', function () {
        $reflection = new ReflectionClass(BuildingResource::class);
        $property = $reflection->getProperty('navigationSort');
        $property->setAccessible(true);

        expect($property->getValue())->toBe(4);
    });

    test('navigation label is translatable', function () {
        expect(BuildingResource::getNavigationLabel())->toBe(__('app.nav.buildings'));
    });

    test('navigation group is translatable', function () {
        expect(BuildingResource::getNavigationGroup())->toBe(__('app.nav_groups.operations'));
    });
});

describe('BuildingResource Form Schema', function () {
    test('form schema includes name field', function () {
        $schema = BuildingResource::form(\Filament\Schemas\Schema::make());
        $components = $schema->getComponents();

        $nameField = collect($components)->first(fn ($component) => $component->getName() === 'name');

        expect($nameField)->not->toBeNull();
        expect($nameField->isRequired())->toBeTrue();
    });

    test('form schema includes address field', function () {
        $schema = BuildingResource::form(\Filament\Schemas\Schema::make());
        $components = $schema->getComponents();

        $addressField = collect($components)->first(fn ($component) => $component->getName() === 'address');

        expect($addressField)->not->toBeNull();
        expect($addressField->isRequired())->toBeTrue();
    });

    test('form schema includes total_apartments field', function () {
        $schema = BuildingResource::form(\Filament\Schemas\Schema::make());
        $components = $schema->getComponents();

        $totalApartmentsField = collect($components)->first(fn ($component) => $component->getName() === 'total_apartments');

        expect($totalApartmentsField)->not->toBeNull();
        expect($totalApartmentsField->isRequired())->toBeTrue();
    });
});

describe('BuildingResource Table Configuration', function () {
    test('table has correct default sort', function () {
        actingAs($this->admin);
        $table = BuildingResource::table(\Filament\Tables\Table::make(BuildingResource::class));

        expect($table->getDefaultSortColumn())->toBe('address');
        expect($table->getDefaultSortDirection())->toBe('asc');
    });

    test('table includes name column', function () {
        actingAs($this->admin);
        $table = BuildingResource::table(\Filament\Tables\Table::make(BuildingResource::class));
        $columns = $table->getColumns();

        $nameColumn = collect($columns)->first(fn ($column) => $column->getName() === 'name');

        expect($nameColumn)->not->toBeNull();
        expect($nameColumn->isSortable())->toBeTrue();
        expect($nameColumn->isSearchable())->toBeTrue();
    });

    test('table includes address column', function () {
        actingAs($this->admin);
        $table = BuildingResource::table(\Filament\Tables\Table::make(BuildingResource::class));
        $columns = $table->getColumns();

        $addressColumn = collect($columns)->first(fn ($column) => $column->getName() === 'address');

        expect($addressColumn)->not->toBeNull();
        expect($addressColumn->isSortable())->toBeTrue();
        expect($addressColumn->isSearchable())->toBeTrue();
    });

    test('table includes properties count column', function () {
        actingAs($this->admin);
        $table = BuildingResource::table(\Filament\Tables\Table::make(BuildingResource::class));
        $columns = $table->getColumns();

        $propertiesCountColumn = collect($columns)->first(fn ($column) => $column->getName() === 'properties_count');

        expect($propertiesCountColumn)->not->toBeNull();
        expect($propertiesCountColumn->isSortable())->toBeTrue();
    });
});

describe('BuildingResource Relations', function () {
    test('has properties relation manager', function () {
        $relations = BuildingResource::getRelations();

        expect($relations)->toContain(\App\Filament\Resources\BuildingResource\RelationManagers\PropertiesRelationManager::class);
    });
});

describe('BuildingResource Pages', function () {
    test('has list page', function () {
        $pages = BuildingResource::getPages();

        expect($pages)->toHaveKey('index');
    });

    test('has create page', function () {
        $pages = BuildingResource::getPages();

        expect($pages)->toHaveKey('create');
    });

    test('has edit page', function () {
        $pages = BuildingResource::getPages();

        expect($pages)->toHaveKey('edit');
    });
});
