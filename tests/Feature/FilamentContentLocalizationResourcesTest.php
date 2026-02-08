<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Filament\Resources\FaqResource;
use App\Filament\Resources\LanguageResource;
use App\Filament\Resources\TranslationResource;
use App\Models\Faq;
use App\Models\Language;
use App\Models\Translation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Create a superadmin user for testing
    $this->superadmin = User::factory()->create([
        'role' => UserRole::SUPERADMIN,
        'email' => 'superadmin@test.com',
    ]);
});

test('FaqResource can be instantiated and has correct model', function () {
    expect(FaqResource::getModel())->toBe(Faq::class);
});

test('FaqResource is only visible to superadmin users', function () {
    // Test superadmin can see it
    $this->actingAs($this->superadmin);
    expect(FaqResource::shouldRegisterNavigation())->toBeTrue();
    expect(FaqResource::canViewAny())->toBeTrue();
    expect(FaqResource::canCreate())->toBeTrue();

    // Test admin cannot see it
    $admin = User::factory()->create(['role' => UserRole::ADMIN]);
    $this->actingAs($admin);
    expect(FaqResource::shouldRegisterNavigation())->toBeFalse();
    expect(FaqResource::canViewAny())->toBeFalse();

    // Test manager cannot see it
    $manager = User::factory()->create(['role' => UserRole::MANAGER]);
    $this->actingAs($manager);
    expect(FaqResource::shouldRegisterNavigation())->toBeFalse();
    expect(FaqResource::canViewAny())->toBeFalse();

    // Test tenant cannot see it
    $tenant = User::factory()->create(['role' => UserRole::TENANT]);
    $this->actingAs($tenant);
    expect(FaqResource::shouldRegisterNavigation())->toBeFalse();
    expect(FaqResource::canViewAny())->toBeFalse();
});

test('FaqResource form schema is properly configured', function () {
    $this->actingAs($this->superadmin);
    
    // In Filament v4, form() returns a Schema with components
    // We verify the resource has a form method and returns the expected type
    $livewire = Mockery::mock(\Filament\Resources\Pages\CreateRecord::class);
    $livewire->shouldReceive('getRecord')->andReturn(null);
    $livewire->shouldReceive('getOperation')->andReturn('create');
    
    $schema = \Filament\Schemas\Schema::make($livewire);
    $result = FaqResource::form($schema);
    
    expect($result)->toBeInstanceOf(\Filament\Schemas\Schema::class);
});

test('FaqResource table is properly configured', function () {
    $this->actingAs($this->superadmin);
    
    // In Filament v4, Table requires a Livewire component with HasTable contract
    $livewire = Mockery::mock(\Filament\Tables\Contracts\HasTable::class);
    $livewire->shouldReceive('getTableQueryStringIdentifier')->andReturn('');
    $livewire->shouldReceive('getIdentifiedTableQueryStringPropertyNameFor')->andReturn('');
    
    $table = \Filament\Tables\Table::make($livewire);
    $result = FaqResource::table($table);
    
    expect($result)->toBeInstanceOf(\Filament\Tables\Table::class);
});

test('LanguageResource can be instantiated and has correct model', function () {
    expect(LanguageResource::getModel())->toBe(Language::class);
});

test('LanguageResource is only visible to superadmin users', function () {
    // Test superadmin can see it
    $this->actingAs($this->superadmin);
    expect(LanguageResource::shouldRegisterNavigation())->toBeTrue();
    expect(LanguageResource::canViewAny())->toBeTrue();
    expect(LanguageResource::canCreate())->toBeTrue();

    // Test admin cannot see it
    $admin = User::factory()->create(['role' => UserRole::ADMIN]);
    $this->actingAs($admin);
    expect(LanguageResource::shouldRegisterNavigation())->toBeFalse();
    expect(LanguageResource::canViewAny())->toBeFalse();

    // Test manager cannot see it
    $manager = User::factory()->create(['role' => UserRole::MANAGER]);
    $this->actingAs($manager);
    expect(LanguageResource::shouldRegisterNavigation())->toBeFalse();
    expect(LanguageResource::canViewAny())->toBeFalse();

    // Test tenant cannot see it
    $tenant = User::factory()->create(['role' => UserRole::TENANT]);
    $this->actingAs($tenant);
    expect(LanguageResource::shouldRegisterNavigation())->toBeFalse();
    expect(LanguageResource::canViewAny())->toBeFalse();
});

test('LanguageResource form schema is properly configured', function () {
    $this->actingAs($this->superadmin);
    
    // In Filament v4, form() returns a Schema with components
    $livewire = Mockery::mock(\Filament\Resources\Pages\CreateRecord::class);
    $livewire->shouldReceive('getRecord')->andReturn(null);
    $livewire->shouldReceive('getOperation')->andReturn('create');
    
    $schema = \Filament\Schemas\Schema::make($livewire);
    $result = LanguageResource::form($schema);
    
    expect($result)->toBeInstanceOf(\Filament\Schemas\Schema::class);
});

test('LanguageResource table is properly configured', function () {
    $this->actingAs($this->superadmin);
    
    // In Filament v4, Table requires a Livewire component with HasTable contract
    $livewire = Mockery::mock(\Filament\Tables\Contracts\HasTable::class);
    $livewire->shouldReceive('getTableQueryStringIdentifier')->andReturn('');
    $livewire->shouldReceive('getIdentifiedTableQueryStringPropertyNameFor')->andReturn('');
    
    $table = \Filament\Tables\Table::make($livewire);
    $result = LanguageResource::table($table);
    
    expect($result)->toBeInstanceOf(\Filament\Tables\Table::class);
});

test('TranslationResource can be instantiated and has correct model', function () {
    expect(TranslationResource::getModel())->toBe(Translation::class);
});

test('TranslationResource is only visible to superadmin users', function () {
    // Test superadmin can see it
    $this->actingAs($this->superadmin);
    expect(TranslationResource::shouldRegisterNavigation())->toBeTrue();
    expect(TranslationResource::canViewAny())->toBeTrue();
    expect(TranslationResource::canCreate())->toBeTrue();

    // Test admin cannot see it
    $admin = User::factory()->create(['role' => UserRole::ADMIN]);
    $this->actingAs($admin);
    expect(TranslationResource::shouldRegisterNavigation())->toBeFalse();
    expect(TranslationResource::canViewAny())->toBeFalse();

    // Test manager cannot see it
    $manager = User::factory()->create(['role' => UserRole::MANAGER]);
    $this->actingAs($manager);
    expect(TranslationResource::shouldRegisterNavigation())->toBeFalse();
    expect(TranslationResource::canViewAny())->toBeFalse();

    // Test tenant cannot see it
    $tenant = User::factory()->create(['role' => UserRole::TENANT]);
    $this->actingAs($tenant);
    expect(TranslationResource::shouldRegisterNavigation())->toBeFalse();
    expect(TranslationResource::canViewAny())->toBeFalse();
});

test('TranslationResource form schema is properly configured', function () {
    $this->actingAs($this->superadmin);
    
    // Create a language first so the form can load properly
    Language::factory()->create([
        'code' => 'en',
        'name' => 'English',
        'is_active' => true,
        'display_order' => 0,
    ]);
    
    // In Filament v4, form() returns a Schema with components
    $livewire = Mockery::mock(\Filament\Resources\Pages\CreateRecord::class);
    $livewire->shouldReceive('getRecord')->andReturn(null);
    $livewire->shouldReceive('getOperation')->andReturn('create');
    
    $schema = \Filament\Schemas\Schema::make($livewire);
    $result = TranslationResource::form($schema);
    
    expect($result)->toBeInstanceOf(\Filament\Schemas\Schema::class);
});

test('TranslationResource table is properly configured', function () {
    $this->actingAs($this->superadmin);
    
    // Create a default language for the table
    Language::factory()->create([
        'code' => 'en',
        'name' => 'English',
        'is_default' => true,
        'is_active' => true,
    ]);
    
    // In Filament v4, Table requires a Livewire component with HasTable contract
    $livewire = Mockery::mock(\Filament\Tables\Contracts\HasTable::class);
    $livewire->shouldReceive('getTableQueryStringIdentifier')->andReturn('');
    $livewire->shouldReceive('getIdentifiedTableQueryStringPropertyNameFor')->andReturn('');
    
    $table = \Filament\Tables\Table::make($livewire);
    $result = TranslationResource::table($table);
    
    expect($result)->toBeInstanceOf(\Filament\Tables\Table::class);
});

test('FaqResource CRUD operations work correctly', function () {
    $this->actingAs($this->superadmin);
    
    // Create
    $faq = Faq::factory()->create([
        'question' => 'Test Question',
        'answer' => 'Test Answer',
        'is_published' => true,
        'display_order' => 1,
    ]);
    
    expect($faq)->toBeInstanceOf(Faq::class);
    expect($faq->question)->toBe('Test Question');
    
    // Update
    $faq->update(['question' => 'Updated Question']);
    expect($faq->fresh()->question)->toBe('Updated Question');
    
    // Delete
    $faq->delete();
    expect(Faq::find($faq->id))->toBeNull();
});

test('LanguageResource CRUD operations work correctly', function () {
    $this->actingAs($this->superadmin);

    // Ensure we don't violate "cannot delete last active language" constraint.
    Language::factory()->create([
        'code' => 'en',
        'name' => 'English',
        'is_active' => true,
        'is_default' => true,
    ]);

    // Create
    $language = Language::factory()->create([
        'code' => 'fr',
        'name' => 'French',
        'is_active' => true,
    ]);
    
    expect($language)->toBeInstanceOf(Language::class);
    expect($language->code)->toBe('fr');
    
    // Update
    $language->update(['name' => 'Français']);
    expect($language->fresh()->name)->toBe('Français');
    
    // Delete
    $language->delete();
    expect(Language::find($language->id))->toBeNull();
});

test('TranslationResource CRUD operations work correctly', function () {
    $this->actingAs($this->superadmin);
    
    // Create
    $translation = Translation::factory()->create([
        'group' => 'test',
        'key' => 'test.key',
        'values' => ['en' => 'Test Value'],
    ]);
    
    expect($translation)->toBeInstanceOf(Translation::class);
    expect($translation->group)->toBe('test');
    
    // Update
    $translation->update(['key' => 'test.updated']);
    expect($translation->fresh()->key)->toBe('test.updated');
    
    // Delete
    $translation->delete();
    expect(Translation::find($translation->id))->toBeNull();
});
