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
    
    $schema = FaqResource::form(\Filament\Schemas\Schema::make());
    
    expect($schema)->toBeInstanceOf(\Filament\Schemas\Schema::class);
    expect($schema->getComponents())->not->toBeEmpty();
});

test('FaqResource table is properly configured', function () {
    $this->actingAs($this->superadmin);
    
    $table = FaqResource::table(\Filament\Tables\Table::make(FaqResource::class));
    
    expect($table)->toBeInstanceOf(\Filament\Tables\Table::class);
    expect($table->getColumns())->not->toBeEmpty();
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
    
    $schema = LanguageResource::form(\Filament\Schemas\Schema::make());
    
    expect($schema)->toBeInstanceOf(\Filament\Schemas\Schema::class);
    expect($schema->getComponents())->not->toBeEmpty();
});

test('LanguageResource table is properly configured', function () {
    $this->actingAs($this->superadmin);
    
    $table = LanguageResource::table(\Filament\Tables\Table::make(LanguageResource::class));
    
    expect($table)->toBeInstanceOf(\Filament\Tables\Table::class);
    expect($table->getColumns())->not->toBeEmpty();
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
    
    $schema = TranslationResource::form(\Filament\Schemas\Schema::make());
    
    expect($schema)->toBeInstanceOf(\Filament\Schemas\Schema::class);
    expect($schema->getComponents())->not->toBeEmpty();
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
    
    $table = TranslationResource::table(\Filament\Tables\Table::make(TranslationResource::class));
    
    expect($table)->toBeInstanceOf(\Filament\Tables\Table::class);
    expect($table->getColumns())->not->toBeEmpty();
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
