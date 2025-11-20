<?php

use App\Enums\ServiceType;
use App\Filament\Resources\ProviderResource;
use App\Models\Provider;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('ProviderResource can be instantiated', function () {
    expect(class_exists(ProviderResource::class))->toBeTrue();
});

test('ProviderResource has correct model', function () {
    expect(ProviderResource::getModel())->toBe(Provider::class);
});

test('ProviderResource has correct navigation configuration', function () {
    expect(ProviderResource::getNavigationIcon())->toBe('heroicon-o-building-library');
    expect(ProviderResource::getNavigationLabel())->toBe('Providers');
    expect(ProviderResource::getNavigationGroup())->toBe('Configuration');
});

test('ProviderResource has tariffs relationship manager registered', function () {
    $relations = ProviderResource::getRelations();
    
    expect($relations)->toContain(\App\Filament\Resources\ProviderResource\RelationManagers\TariffsRelationManager::class);
});

test('ProviderResource has pages configured', function () {
    $pages = ProviderResource::getPages();
    
    expect($pages)->toHaveKey('index');
    expect($pages)->toHaveKey('create');
    expect($pages)->toHaveKey('edit');
});

test('TariffsRelationManager exists', function () {
    expect(class_exists(\App\Filament\Resources\ProviderResource\RelationManagers\TariffsRelationManager::class))->toBeTrue();
});

test('Provider model can be created with service type', function () {
    $provider = Provider::factory()->create([
        'name' => 'Test Provider',
        'service_type' => ServiceType::ELECTRICITY,
    ]);
    
    expect($provider->name)->toBe('Test Provider');
    expect($provider->service_type)->toBe(ServiceType::ELECTRICITY);
});

test('Provider model has tariffs relationship', function () {
    $provider = Provider::factory()->create();
    
    expect($provider->tariffs())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class);
});
