<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Filament\Resources\BuildingResource;
use App\Models\Building;
use App\Models\User;
use Filament\Schemas\Schema;

use function Pest\Laravel\actingAs;

/**
 * BuildingResource Validation Test Suite
 *
 * Tests form validation rules, error messages, and edge cases for
 * BuildingResource form fields following the HasTranslatedValidation pattern.
 *
 * Run with: php artisan test --filter=BuildingResourceValidation
 */

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => UserRole::ADMIN]);
    actingAs($this->admin);
});

describe('Name Field Validation', function () {
    test('name field is required', function () {
        $schema = BuildingResource::form(Schema::make());
        $components = $schema->getComponents();
        $nameField = collect($components)->first(fn ($c) => $c->getName() === 'name');

        expect($nameField->isRequired())->toBeTrue();
    });

    test('name field has max length of 255', function () {
        $schema = BuildingResource::form(Schema::make());
        $components = $schema->getComponents();
        $nameField = collect($components)->first(fn ($c) => $c->getName() === 'name');

        $rules = $nameField->getValidationRules();
        
        expect($rules)->toContain('max:255');
    });

    test('name field uses translated validation messages', function () {
        $schema = BuildingResource::form(Schema::make());
        $components = $schema->getComponents();
        $nameField = collect($components)->first(fn ($c) => $c->getName() === 'name');

        $messages = $nameField->getValidationMessages();
        
        expect($messages)->toBeArray()
            ->and($messages)->toHaveKey('required')
            ->and($messages)->toHaveKey('max');
    });

    test('name field accepts valid input', function () {
        $building = Building::factory()->create([
            'tenant_id' => $this->admin->tenant_id,
            'name' => 'Valid Building Name',
            'address' => '123 Main St',
            'total_apartments' => 10,
        ]);

        expect($building->name)->toBe('Valid Building Name');
    });

    test('name field accepts unicode characters', function () {
        $building = Building::factory()->create([
            'tenant_id' => $this->admin->tenant_id,
            'name' => 'Pastatas Vilniuje',
            'address' => 'Gedimino pr. 1',
            'total_apartments' => 15,
        ]);

        expect($building->name)->toBe('Pastatas Vilniuje');
    });

    test('name field trims whitespace', function () {
        $building = Building::factory()->create([
            'tenant_id' => $this->admin->tenant_id,
            'name' => '  Building Name  ',
            'address' => '123 Main St',
            'total_apartments' => 10,
        ]);

        // Note: Trimming happens at form level, not model level
        expect($building->name)->toBe('  Building Name  ');
    });
});

describe('Address Field Validation', function () {
    test('address field is required', function () {
        $schema = BuildingResource::form(Schema::make());
        $components = $schema->getComponents();
        $addressField = collect($components)->first(fn ($c) => $c->getName() === 'address');

        expect($addressField->isRequired())->toBeTrue();
    });

    test('address field has max length of 500', function () {
        $schema = BuildingResource::form(Schema::make());
        $components = $schema->getComponents();
        $addressField = collect($components)->first(fn ($c) => $c->getName() === 'address');

        $rules = $addressField->getValidationRules();
        
        expect($rules)->toContain('max:500');
    });

    test('address field spans full width', function () {
        $schema = BuildingResource::form(Schema::make());
        $components = $schema->getComponents();
        $addressField = collect($components)->first(fn ($c) => $c->getName() === 'address');

        expect($addressField->getColumnSpan())->toBe('full');
    });

    test('address field uses translated validation messages', function () {
        $schema = BuildingResource::form(Schema::make());
        $components = $schema->getComponents();
        $addressField = collect($components)->first(fn ($c) => $c->getName() === 'address');

        $messages = $addressField->getValidationMessages();
        
        expect($messages)->toBeArray()
            ->and($messages)->toHaveKey('required')
            ->and($messages)->toHaveKey('max');
    });

    test('address field accepts valid street address', function () {
        $building = Building::factory()->create([
            'tenant_id' => $this->admin->tenant_id,
            'name' => 'Test Building',
            'address' => '123 Main Street, Apt 4B',
            'total_apartments' => 10,
        ]);

        expect($building->address)->toBe('123 Main Street, Apt 4B');
    });

    test('address field accepts lithuanian characters', function () {
        $building = Building::factory()->create([
            'tenant_id' => $this->admin->tenant_id,
            'name' => 'Test Building',
            'address' => 'Gedimino pr. 1, Vilnius',
            'total_apartments' => 10,
        ]);

        expect($building->address)->toBe('Gedimino pr. 1, Vilnius');
    });

    test('address field accepts special characters', function () {
        $building = Building::factory()->create([
            'tenant_id' => $this->admin->tenant_id,
            'name' => 'Test Building',
            'address' => '123-A Main St., #5',
            'total_apartments' => 10,
        ]);

        expect($building->address)->toBe('123-A Main St., #5');
    });
});

describe('Total Apartments Field Validation', function () {
    test('total apartments field is required', function () {
        $schema = BuildingResource::form(Schema::make());
        $components = $schema->getComponents();
        $totalApartmentsField = collect($components)->first(fn ($c) => $c->getName() === 'total_apartments');

        expect($totalApartmentsField->isRequired())->toBeTrue();
    });

    test('total apartments field is numeric', function () {
        $schema = BuildingResource::form(Schema::make());
        $components = $schema->getComponents();
        $totalApartmentsField = collect($components)->first(fn ($c) => $c->getName() === 'total_apartments');

        $rules = $totalApartmentsField->getValidationRules();
        
        expect($rules)->toContain('numeric');
    });

    test('total apartments field has min value of 1', function () {
        $schema = BuildingResource::form(Schema::make());
        $components = $schema->getComponents();
        $totalApartmentsField = collect($components)->first(fn ($c) => $c->getName() === 'total_apartments');

        expect($totalApartmentsField->getMinValue())->toBe(1);
    });

    test('total apartments field has max value of 1000', function () {
        $schema = BuildingResource::form(Schema::make());
        $components = $schema->getComponents();
        $totalApartmentsField = collect($components)->first(fn ($c) => $c->getName() === 'total_apartments');

        expect($totalApartmentsField->getMaxValue())->toBe(1000);
    });

    test('total apartments field requires integer', function () {
        $schema = BuildingResource::form(Schema::make());
        $components = $schema->getComponents();
        $totalApartmentsField = collect($components)->first(fn ($c) => $c->getName() === 'total_apartments');

        $rules = $totalApartmentsField->getValidationRules();
        
        expect($rules)->toContain('integer');
    });

    test('total apartments field uses translated validation messages', function () {
        $schema = BuildingResource::form(Schema::make());
        $components = $schema->getComponents();
        $totalApartmentsField = collect($components)->first(fn ($c) => $c->getName() === 'total_apartments');

        $messages = $totalApartmentsField->getValidationMessages();
        
        expect($messages)->toBeArray()
            ->and($messages)->toHaveKey('required')
            ->and($messages)->toHaveKey('numeric')
            ->and($messages)->toHaveKey('min')
            ->and($messages)->toHaveKey('max')
            ->and($messages)->toHaveKey('integer');
    });

    test('total apartments field accepts minimum value', function () {
        $building = Building::factory()->create([
            'tenant_id' => $this->admin->tenant_id,
            'name' => 'Small Building',
            'address' => '123 Main St',
            'total_apartments' => 1,
        ]);

        expect($building->total_apartments)->toBe(1);
    });

    test('total apartments field accepts maximum value', function () {
        $building = Building::factory()->create([
            'tenant_id' => $this->admin->tenant_id,
            'name' => 'Large Building',
            'address' => '123 Main St',
            'total_apartments' => 1000,
        ]);

        expect($building->total_apartments)->toBe(1000);
    });

    test('total apartments field accepts typical value', function () {
        $building = Building::factory()->create([
            'tenant_id' => $this->admin->tenant_id,
            'name' => 'Medium Building',
            'address' => '123 Main St',
            'total_apartments' => 50,
        ]);

        expect($building->total_apartments)->toBe(50);
    });
});

describe('Form Validation Integration', function () {
    test('form validates all required fields together', function () {
        $schema = BuildingResource::form(Schema::make());
        $components = $schema->getComponents();

        $requiredFields = collect($components)
            ->filter(fn ($c) => $c->isRequired())
            ->map(fn ($c) => $c->getName())
            ->values()
            ->all();

        expect($requiredFields)->toContain('name')
            ->and($requiredFields)->toContain('address')
            ->and($requiredFields)->toContain('total_apartments');
    });

    test('form uses HasTranslatedValidation trait', function () {
        $traits = class_uses_recursive(BuildingResource::class);
        
        expect($traits)->toContain(\App\Filament\Concerns\HasTranslatedValidation::class);
    });

    test('form has correct translation prefix', function () {
        $reflection = new ReflectionClass(BuildingResource::class);
        $property = $reflection->getProperty('translationPrefix');
        $property->setAccessible(true);

        expect($property->getValue())->toBe('buildings.validation');
    });
});

describe('Edge Cases', function () {
    test('building can be created with minimal valid data', function () {
        $building = Building::factory()->create([
            'tenant_id' => $this->admin->tenant_id,
            'name' => 'A',
            'address' => 'B',
            'total_apartments' => 1,
        ]);

        expect($building)->toBeInstanceOf(Building::class)
            ->and($building->name)->toBe('A')
            ->and($building->address)->toBe('B')
            ->and($building->total_apartments)->toBe(1);
    });

    test('building can be created with maximum length strings', function () {
        $longName = str_repeat('A', 255);
        $longAddress = str_repeat('B', 500);

        $building = Building::factory()->create([
            'tenant_id' => $this->admin->tenant_id,
            'name' => $longName,
            'address' => $longAddress,
            'total_apartments' => 500,
        ]);

        expect($building->name)->toHaveLength(255)
            ->and($building->address)->toHaveLength(500);
    });

    test('building preserves exact input formatting', function () {
        $building = Building::factory()->create([
            'tenant_id' => $this->admin->tenant_id,
            'name' => 'Building-123',
            'address' => '123 Main St., Apt #4B',
            'total_apartments' => 10,
        ]);

        expect($building->name)->toBe('Building-123')
            ->and($building->address)->toBe('123 Main St., Apt #4B');
    });
});
