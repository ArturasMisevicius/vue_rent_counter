<?php

use App\Enums\LanguageStatus;
use App\Filament\Resources\Languages\Pages\ListLanguages;
use App\Models\Language;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('only allows superadmins to reach language control-plane pages', function () {
    $superadmin = User::factory()->superadmin()->create();
    $admin = User::factory()->admin()->create([
        'organization_id' => Organization::factory(),
    ]);

    $this->actingAs($superadmin)
        ->get(route('filament.admin.resources.languages.index'))
        ->assertOk();

    $this->actingAs($admin)
        ->get(route('filament.admin.resources.languages.index'))
        ->assertForbidden();
});

it('manages default, activation, and deletion constraints for languages', function () {
    $superadmin = User::factory()->superadmin()->create();

    $english = Language::factory()->create([
        'code' => 'en',
        'name' => 'English',
        'native_name' => 'English',
        'status' => LanguageStatus::ACTIVE,
        'is_default' => true,
    ]);
    $lithuanian = Language::factory()->create([
        'code' => 'lt',
        'name' => 'Lithuanian',
        'native_name' => 'Lietuviu',
        'status' => LanguageStatus::ACTIVE,
        'is_default' => false,
    ]);
    $spanish = Language::factory()->create([
        'code' => 'es',
        'name' => 'Spanish',
        'native_name' => 'Espanol',
        'status' => LanguageStatus::INACTIVE,
        'is_default' => false,
    ]);
    $russian = Language::factory()->create([
        'code' => 'ru',
        'name' => 'Russian',
        'native_name' => 'Russkii',
        'status' => LanguageStatus::ACTIVE,
        'is_default' => false,
    ]);
    $french = Language::factory()->create([
        'code' => 'fr',
        'name' => 'French',
        'native_name' => 'Francais',
        'status' => LanguageStatus::INACTIVE,
        'is_default' => false,
    ]);

    User::factory()->admin()->create([
        'organization_id' => Organization::factory(),
        'locale' => 'ru',
    ]);

    $this->actingAs($superadmin);

    Livewire::test(ListLanguages::class)
        ->assertCanSeeTableRecords([$english, $lithuanian, $spanish, $russian, $french])
        ->assertTableColumnExists('code')
        ->assertTableColumnExists('name')
        ->assertTableColumnExists('native_name')
        ->assertTableColumnExists('status')
        ->assertTableColumnExists('is_default')
        ->assertTableFilterExists('status')
        ->assertTableActionVisible('setDefault', $lithuanian)
        ->assertTableActionVisible('deactivate', $lithuanian)
        ->assertTableActionVisible('activate', $spanish)
        ->assertTableActionDisabled('deactivate', $english)
        ->assertTableActionDisabled('delete', $english)
        ->assertTableActionDisabled('delete', $russian);

    Livewire::test(ListLanguages::class)
        ->mountTableAction('setDefault', $lithuanian)
        ->callMountedTableAction();

    expect($english->fresh()->is_default)->toBeFalse()
        ->and($lithuanian->fresh()->is_default)->toBeTrue()
        ->and($lithuanian->status)->toBe(LanguageStatus::ACTIVE);

    Livewire::test(ListLanguages::class)
        ->mountTableAction('deactivate', $english)
        ->callMountedTableAction();

    expect($english->refresh()->status)->toBe(LanguageStatus::INACTIVE);

    Livewire::test(ListLanguages::class)
        ->mountTableAction('activate', $spanish)
        ->callMountedTableAction();

    expect($spanish->refresh()->status)->toBe(LanguageStatus::ACTIVE);

    Livewire::test(ListLanguages::class)
        ->mountTableAction('delete', $french)
        ->callMountedTableAction();

    expect(Language::query()->whereKey($french->id)->exists())->toBeFalse();
});
