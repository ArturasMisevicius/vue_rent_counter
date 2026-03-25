<?php

use App\Enums\LanguageStatus;
use App\Filament\Actions\Superadmin\Languages\DeleteLanguageAction;
use App\Filament\Actions\Superadmin\Languages\SetDefaultLanguageAction;
use App\Filament\Actions\Superadmin\Languages\ToggleLanguageStatusAction;
use App\Models\Language;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

it('shows language management pages only to superadmins', function () {
    $superadmin = User::factory()->superadmin()->create();
    $language = Language::factory()->create([
        'code' => 'de',
        'name' => 'German',
        'native_name' => 'Deutsch',
    ]);

    $organization = Organization::factory()->create();
    $admin = User::factory()->admin()->create([
        'organization_id' => $organization->id,
    ]);

    $this->actingAs($superadmin)
        ->get(route('filament.admin.resources.languages.index'))
        ->assertSuccessful()
        ->assertSeeText(__('superadmin.languages_resource.plural'))
        ->assertSeeText($language->name);

    $this->actingAs($superadmin)
        ->get(route('filament.admin.resources.languages.create'))
        ->assertSuccessful()
        ->assertSeeText(__('superadmin.languages_resource.fields.code'))
        ->assertSeeText(__('superadmin.languages_resource.fields.name'))
        ->assertSeeText(__('superadmin.languages_resource.fields.native_name'));

    $this->actingAs($superadmin)
        ->get(route('filament.admin.resources.languages.edit', $language))
        ->assertSuccessful()
        ->assertSeeText('Save changes');

    $this->actingAs($admin)
        ->get(route('filament.admin.resources.languages.index'))
        ->assertForbidden();
});

it('sets defaults toggles statuses and blocks deleting languages in use', function () {
    $english = Language::factory()->create([
        'code' => 'en',
        'is_default' => true,
        'status' => LanguageStatus::ACTIVE,
    ]);

    $lithuanian = Language::factory()->create([
        'code' => 'lt',
        'is_default' => false,
        'status' => LanguageStatus::ACTIVE,
    ]);

    $defaultLanguage = app(SetDefaultLanguageAction::class)->handle($lithuanian);
    $toggled = app(ToggleLanguageStatusAction::class)->handle($english->fresh());

    expect($defaultLanguage->fresh()->is_default)->toBeTrue()
        ->and($english->fresh()->is_default)->toBeFalse()
        ->and($toggled->status)->toBe(LanguageStatus::INACTIVE);

    User::factory()->admin()->create([
        'locale' => 'lt',
    ]);

    expect(fn () => app(DeleteLanguageAction::class)->handle($lithuanian->fresh()))
        ->toThrow(ValidationException::class);

    $deletable = Language::factory()->create([
        'code' => 'de',
        'status' => LanguageStatus::INACTIVE,
    ]);

    app(DeleteLanguageAction::class)->handle($deletable);

    expect(Language::query()->whereKey($deletable->id)->exists())->toBeFalse();
});
